<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Services\OrderService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function calculateShipping(Request $request, ShippingService $shippingService)
    {
        $clientId = $request->client_id;
        $providerId = $request->shipping_provider_id;
        $itemsData = $request->items ?? [];

        if (!$clientId || !$providerId || empty($itemsData)) {
            return response()->json(['error' => 'Select a client, shipping provider, and add items.'], 400);
        }

        $client = Client::find($clientId);
        if (!$client || !$client->country) {
            $msg = !$client ? "Client not found." : "Client '{$client->name}' has no country set in their profile. Shipping calculation requires a destination.";
            return response()->json(['error' => $msg], 400);
        }

        $items = [];
        foreach ($itemsData as $i) {
            if (empty($i['quantity']) || $i['quantity'] < 1)
                continue;

            $product = new Product();
            $product->weight = floatval($i['weight_kg'] ?? 0);
            $product->length = floatval($i['length'] ?? 0);
            $product->width = floatval($i['width'] ?? 0);
            $product->height = floatval($i['height'] ?? 0);

            $items[] = [
                'product' => $product,
                'quantity' => intval($i['quantity'])
            ];
        }

        if (empty($items)) {
            return response()->json(['error' => 'No items with valid quantities to calculate shipping for.'], 400);
        }

        $rates = $shippingService->calculateShipping($items, $client->country);

        $provider = ShippingProvider::find($providerId);
        if (!$provider) {
            return response()->json(['error' => 'Invalid shipping provider.'], 400);
        }

        $selectedRate = collect($rates)->firstWhere('provider_name', $provider->name);

        if (!$selectedRate) {
            $overWeight = $shippingService->checkOverWeight($items, $client->country);
            if ($overWeight['over_weight']) {
                return response()->json(['error' => 'Over Weight'], 422);
            }

            $msg = empty($rates)
                ? "No shipping service found delivering to '{$client->country}'."
                : "The provider '{$provider->name}' does not have a rate for this weight/zone.";
            return response()->json(['error' => $msg], 404);
        }

        return response()->json(['cost' => $selectedRate['price']]);
    }

    public function index(Request $request)
    {
        $query = Order::with(['client', 'creator', 'latestInvoice', 'shippingProvider', 'items'])
            ->whereNull('merged_into_order_id');

        // Filters
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($cq) => $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('buyer_id', 'like', "%{$search}%"));
            });
        }
        if ($from = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $statuses = Order::STATUS_LABELS;
        $statusColors = Order::STATUS_COLORS;

        return view('admin.orders.index', compact('orders', 'statuses', 'statusColors'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        ShippingProvider::ensureDefaultProvider();
        $shippingProviders = ShippingProvider::orderBy('name')->get();

        return view('admin.orders.create', compact('clients', 'products', 'shippingProviders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:inquiry,order',
            'client_id' => 'nullable|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight_kg' => 'required|numeric|min:0',
            'order_discount_type' => 'nullable|in:percent,fixed,none',
            'order_discount_value' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Build items
        $items = [];
        foreach ($request->items as $item) {
            if (empty($item['unit_price']) && empty($item['product_id']))
                continue;
            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'product_sku' => $item['product_sku'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'weight_kg' => (float) ($item['weight_kg'] ?? 0),
                'item_discount_type' => $item['item_discount_type'] ?? 'none',
                'item_discount_value' => (float) ($item['item_discount_value'] ?? 0),
            ];
        }

        $data = array_merge($request->only([
            'type',
            'client_id',
            'order_discount_type',
            'order_discount_value',
            'shipping_cost',
            'notes',
            'delivery_period_days',
        ]), ['items' => $items]);

        $data['order_discount_type'] = $data['order_discount_type'] ?? 'none';
        $data['order_discount_value'] = $data['order_discount_value'] ?? 0;
        $data['shipping_cost'] = $data['shipping_cost'] ?? 0;
        $data['shipping_provider_id'] = $request->filled('shipping_provider_id')
            ? $request->shipping_provider_id
            : ShippingProvider::getDefaultProviderId();

        $order = $this->orderService->createOrder($data, auth()->user());

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order #{$order->order_number} created successfully.");
    }

    public function show(Order $order)
    {
        $order->load([
            'client',
            'creator',
            'items.product',
            'invoices',
            'shippingProvider',
            'auditLogs.user',
            'cancelledBy',
            'convertedOrder',
            'sourceInquiry',
        ]);

        ShippingProvider::ensureDefaultProvider();
        $shippingProviders = ShippingProvider::orderBy('name')->get();
        $statusColors = Order::STATUS_COLORS;
        $allowedTransitions = Order::ALLOWED_TRANSITIONS[$order->status] ?? [];

        return view('admin.orders.show', compact(
            'order',
            'shippingProviders',
            'statusColors',
            'allowedTransitions'
        ));
    }

    public function edit(Order $order)
    {
        if ($order->isFinanciallyLocked()) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'This order is financially locked. You cannot edit it.');
        }

        $clients = Client::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        ShippingProvider::ensureDefaultProvider();
        $shippingProviders = ShippingProvider::orderBy('name')->get();
        $order->load('items.product', 'client');

        return view('admin.orders.edit', compact('order', 'clients', 'products', 'shippingProviders'));
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'type' => 'required|in:inquiry,order',
            'client_id' => 'nullable|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight_kg' => 'required|numeric|min:0',
            'order_discount_type' => 'nullable|in:percent,fixed,none',
            'order_discount_value' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        $items = [];
        foreach ($request->items as $item) {
            if (empty($item['unit_price']) && empty($item['product_id']))
                continue;
            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'product_sku' => $item['product_sku'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'weight_kg' => (float) ($item['weight_kg'] ?? 0),
                'item_discount_type' => $item['item_discount_type'] ?? 'none',
                'item_discount_value' => (float) ($item['item_discount_value'] ?? 0),
            ];
        }

        $data = array_merge($request->only([
            'type',
            'client_id',
            'order_discount_type',
            'order_discount_value',
            'shipping_cost',
            'notes',
            'delivery_period_days',
        ]), ['items' => $items]);

        $data['order_discount_type'] = $data['order_discount_type'] ?? 'none';
        $data['order_discount_value'] = $data['order_discount_value'] ?? 0;
        $data['shipping_provider_id'] = $request->filled('shipping_provider_id')
            ? $request->shipping_provider_id
            : ShippingProvider::getDefaultProviderId();

        $this->orderService->updateOrder($order, $data, auth()->user());

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order #{$order->order_number} updated successfully.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::STATUSES), Rule::notIn([Order::STATUS_CONVERTED])],
            'tracking_number' => 'nullable|string',
            'shipping_provider_id' => 'nullable|exists:shipping_providers,id',
            'dispatched_at' => 'nullable|date',
            'override' => 'nullable|boolean',
        ], [
            'status.not_in' => 'The "Converted to Order" status is set automatically and cannot be chosen manually.',
        ]);

        $user = auth()->user();
        $override = $request->boolean('override') && $user->hasAnyRole(['super_admin', 'admin']);

        try {
            $this->orderService->changeStatus(
                $order,
                $request->status,
                $user,
                $request->only(['tracking_number', 'shipping_provider_id', 'dispatched_at', 'cancellation_reason']),
                $override
            );

            return back()->with('success', 'Order status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markPaid(Request $request, Order $order)
    {
        if (!auth()->user()->hasAnyPermission(['manage_orders', 'manage_invoices'])) {
            abort(403);
        }

        try {
            $this->orderService->markAsPaid($order, auth()->user());
            return back()->with('success', 'Order marked as paid. Financials are now locked.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order)
    {
        $request->validate(['cancellation_reason' => 'required|string|min:5']);

        try {
            $this->orderService->cancelOrder($order, auth()->user(), $request->cancellation_reason);
            return back()->with('success', 'Order cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function merge(Request $request)
    {
        $request->validate([
            'source_order_ids' => 'required|array|min:1',
            'target_order_id' => 'required|exists:orders,id',
        ]);

        if (!auth()->user()->hasPermission('merge_orders')) {
            abort(403, 'You do not have permission to merge orders.');
        }

        $targetOrder = Order::findOrFail($request->target_order_id);

        try {
            $this->orderService->mergeOrders(
                $request->source_order_ids,
                $targetOrder,
                auth()->user()
            );
            return redirect()->route('admin.orders.show', $targetOrder)
                ->with('success', 'Orders merged successfully into #' . $targetOrder->order_number);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function generateCheckoutLink(Order $order)
    {
        if ($order->type !== Order::TYPE_INQUIRY) {
            return back()->with('error', 'Checkout links can only be generated for inquiry-type orders.');
        }

        if ($order->is_paid) {
            return back()->with('error', 'This order is already paid. No link needed.');
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            return back()->with('error', 'Cannot generate a link for a cancelled order.');
        }

        $token = Str::random(48);
        $order->update(['checkout_token' => $token]);

        $link = url('/checkout/' . $token);

        return back()
            ->with('checkout_link', $link)
            ->with('success', 'Payment link generated. Copy it and share with the customer.');
    }

    public function returnItem(Request $request, Order $order, \App\Models\OrderItem $item)
    {
        if (!auth()->user()->hasPermission('manage_orders')) {
            abort(403);
        }

        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->orderService->markItemReturned($item, (int) $request->quantity, $request->reason, auth()->user());
            return back()->with('success', 'Item return recorded.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markAllReturned(Order $order)
    {
        if (!auth()->user()->hasPermission('manage_orders')) {
            abort(403);
        }

        try {
            $this->orderService->markAllItemsReturned($order, auth()->user());
            return back()->with('success', 'All order items marked as returned.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function clearAllReturns(Order $order)
    {
        if (!auth()->user()->hasPermission('manage_orders')) {
            abort(403);
        }

        try {
            $this->orderService->clearAllItemReturns($order, auth()->user());
            return back()->with('success', 'All return records cleared.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function undoItemReturn(Order $order, \App\Models\OrderItem $item)
    {
        if (!auth()->user()->hasPermission('manage_orders')) {
            abort(403);
        }

        if ($item->order_id !== $order->id) {
            abort(404);
        }

        try {
            $this->orderService->undoItemReturn($item, auth()->user());
            return back()->with('success', 'Item return reversed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403);
        }

        $this->orderService->deleteOrder($order, auth()->user());

        return redirect()->route('admin.orders.index')
            ->with('success', "Order #{$order->order_number} deleted.");
    }
}
