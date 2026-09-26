<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\CartQuoteMail;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\ShippingZone;
use App\Services\OrderService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CartController extends Controller
{
    protected $shippingService;
    protected $orderService;

    public function __construct(ShippingService $shippingService, OrderService $orderService)
    {
        $this->shippingService = $shippingService;
        $this->orderService = $orderService;
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quantity = max((int) ($validated['quantity'] ?? 1), (int) ($product->min_quantity ?? 1));

        $lineKey = $this->makeCartLineKey($product->id);
        $cart = $this->getCartLines();

        if (isset($cart[$lineKey])) {
            $cart[$lineKey]['quantity'] += $quantity;
        } else {
            $cart[$lineKey] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Product added to cart!');
    }

    public function index()
    {
        $cartItems = $this->buildSessionCartItems();
        $subtotal = collect($cartItems)->sum('subtotal');

        return view('frontend.cart.index', compact('cartItems', 'subtotal'));
    }

    public function checkout($token = null)
    {
        $inquiry = null;
        $items = [];
        $subtotal = 0;

        if ($token) {
            $omsOrder = Order::where('checkout_token', $token)->where('type', 'inquiry')->first();

            if ($omsOrder) {
                $omsOrder->load('items.product');
                foreach ($omsOrder->items as $item) {
                    $items[] = [
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->line_total,
                    ];
                    $subtotal += (float) $item->line_total;
                }
                $inquiry = $omsOrder;
            } else {
                $inquiry = Inquiry::where('checkout_token', $token)->firstOrFail();

                if ($inquiry->product) {
                    $qty = $inquiry->product->min_quantity ?? 1;
                    $unitPrice = (float) $inquiry->product->effective_price;
                    $items[] = [
                        'product' => $inquiry->product,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $unitPrice * $qty,
                    ];
                    $subtotal += ($unitPrice * $qty);
                }
            }
        } else {
            $items = $this->buildSessionCartItems();
            if (empty($items)) {
                return redirect()->route('home');
            }
            $subtotal = collect($items)->sum('subtotal');
        }

        $rawCountries = ShippingZone::all()
            ->pluck('countries')
            ->flatten()
            ->filter()
            ->map(function ($c) {
                return is_string($c) ? $c : strval($c);
            })
            ->unique()
            ->values();

        $map = config('countries.map', []);
        $availableCountriesOptions = $rawCountries
            ->map(function ($raw) use ($map) {
                $upper = strtoupper(trim($raw));
                $label = $map[$upper] ?? ucwords(strtolower($raw));
                return ['value' => $raw, 'label' => $label];
            })
            ->sortBy('label')
            ->values()
            ->all();

        return view('frontend.cart.checkout', compact('items', 'subtotal', 'inquiry', 'token', 'availableCountriesOptions'));
    }

    public function calculateShipping(Request $request)
    {
        $country = $request->country;
        $token = $request->token;

        $items = [];
        if ($token) {
            $omsOrder = Order::where('checkout_token', $token)->where('type', 'inquiry')->first();
            if ($omsOrder) {
                $omsOrder->load('items.product');
                foreach ($omsOrder->items as $item) {
                    if ($item->product) {
                        $items[] = ['product' => $item->product, 'quantity' => $item->quantity];
                    }
                }
            } else {
                $inquiry = Inquiry::where('checkout_token', $token)->first();
                if ($inquiry && $inquiry->product) {
                    $items[] = ['product' => $inquiry->product, 'quantity' => $inquiry->product->min_quantity];
                }
            }
        } else {
            foreach ($this->buildSessionCartItems() as $item) {
                $items[] = ['product' => $item['product'], 'quantity' => $item['quantity']];
            }
        }

        if (empty($items)) {
            return response()->json(['error' => 'No items to calculate shipping for.'], 400);
        }

        $rates = $this->shippingService->calculateShipping($items, $country);
        $overWeight = $this->shippingService->checkOverWeight($items, $country);

        return response()->json(['rates' => $rates, 'over_weight' => $overWeight['over_weight']]);
    }

    public function updateQuantity(Request $request)
    {
        $validated = $request->validate([
            'line_key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getCartLines();
        $line = $cart[$validated['line_key']] ?? null;

        if ($line) {
            $product = Product::find($line['product_id']);
            $minQuantity = $product?->min_quantity ?? 1;
            $cart[$validated['line_key']]['quantity'] = max($minQuantity, (int) $validated['quantity']);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated!');
    }

    public function removeItem(Request $request)
    {
        $validated = $request->validate([
            'line_key' => ['required', 'string'],
        ]);

        $cart = $this->getCartLines();

        if (isset($cart[$validated['line_key']])) {
            unset($cart[$validated['line_key']]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from cart!');
    }

    public function initOrder(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:10'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'token' => ['nullable', 'string'],
        ]);

        $token = $validated['token'] ?? null;
        $items = [];
        $sourceInquiryId = null;

        if ($token) {
            $omsOrder = Order::where('checkout_token', $token)->where('type', 'inquiry')->first();

            if ($omsOrder) {
                $sourceInquiryId = $omsOrder->id;
                $omsOrder->load('items.product');
                foreach ($omsOrder->items as $item) {
                    $items[] = [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'weight_kg' => $item->weight_kg ?? ($item->product?->weight ?? 0),
                        'item_discount_type' => $item->item_discount_type ?? 'none',
                        'item_discount_value' => $item->item_discount_value ?? 0,
                    ];
                }

                $omsOrder->checkout_token = null;
                $omsOrder->save();
            } else {
                $inquiry = Inquiry::where('checkout_token', $token)->first();
                if ($inquiry && $inquiry->product) {
                    $product = $inquiry->product;
                    $qty = $product->min_quantity ?? 1;
                    $unitPrice = (float) $product->effective_price;
                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'weight_kg' => $product->weight ?? 0,
                        'item_discount_type' => 'none',
                        'item_discount_value' => 0,
                    ];
                }
            }
        } else {
            foreach ($this->getCartLines() as $line) {
                $product = Product::find($line['product_id']);
                if (!$product) {
                    continue;
                }

                $unitPrice = $product->effective_price;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'weight_kg' => $product->weight ?? 0,
                    'item_discount_type' => 'none',
                    'item_discount_value' => 0,
                ];
            }
        }

        if (empty($items)) {
            return response()->json(['error' => 'No items found for this order.'], 422);
        }

        $client = Client::where('email', $validated['email'])->first();

        if (!$client) {
            $client = Client::create([
                'buyer_id' => Client::generateBuyerId(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address_line' => $validated['address'],
                'city' => $validated['city'],
                'zip_code' => $validated['zip_code'] ?? null,
                'country' => $validated['country'],
            ]);
        }

        $order = $this->orderService->createOrder([
            'type' => Order::TYPE_ORDER,
            'client_id' => $client->id,
            'shipping_cost' => $validated['shipping_cost'],
            'source_inquiry_id' => $sourceInquiryId,
            'items' => $items,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
        ]);
    }

    public function orderSuccess(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('is_paid', true)
            ->with(['client', 'items.product'])
            ->firstOrFail();

        return view('frontend.cart.order-success', compact('order'));
    }

    public function submitQuote(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'address'           => ['required', 'string', 'max:500'],
            'city'              => ['required', 'string', 'max:100'],
            'zip_code'          => ['nullable', 'string', 'max:20'],
            'country'           => ['required', 'string', 'max:10'],
            'shipping_cost'     => ['required', 'numeric', 'min:0'],
            'shipping_provider' => ['nullable', 'string', 'max:255'],
        ]);

        $cartItems = $this->buildSessionCartItems();

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal         = collect($cartItems)->sum('subtotal');
        $shippingCost     = (float) $validated['shipping_cost'];
        $shippingProvider = $validated['shipping_provider'] ?? '';
        $shippingProviderId = $shippingProvider !== ''
            ? ShippingProvider::where('name', $shippingProvider)->value('id')
            : null;

        // ── 1. Find or create the client profile ─────────────────────────────
        $client = Client::firstOrCreate(
            ['email' => $validated['email']],
            [
                'buyer_id'     => Client::generateBuyerId(),
                'name'         => $validated['name'],
                'phone'        => $validated['phone'] ?? null,
                'address_line' => $validated['address'],
                'city'         => $validated['city'],
                'zip_code'     => $validated['zip_code'] ?? null,
                'country'      => $validated['country'],
            ]
        );

        // ── 2. Build items array for OrderService ─────────────────────────────
        $items = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem['product'];
            $items[] = [
                'product_id'         => $product->id,
                'quantity'           => $cartItem['quantity'],
                'unit_price'         => $cartItem['unit_price'],
                'weight_kg'          => $product->weight ?? 0,
                'item_discount_type' => 'none',
                'item_discount_value'=> 0,
            ];
        }

        // ── 3. Save as an Inquiry order in the DB ─────────────────────────────
        $order = $this->orderService->createOrder([
            'type'                => Order::TYPE_INQUIRY,
            'client_id'           => $client->id,
            'shipping_cost'       => $shippingCost,
            'shipping_provider_id'=> $shippingProviderId,
            'notes'               => 'Cart quote request.',
            'items'               => $items,
        ]);

        // Admin and customer notification emails are automatically dispatched by OrderService via afterCommit hook.

        // ── 4. Clear the cart and redirect ────────────────────────────────────
        session()->forget('cart');

        return redirect()->route('quote.success');
    }

    protected function getCartLines(): array
    {
        $cart = session()->get('cart', []);
        $normalized = [];

        foreach ($cart as $key => $line) {
            if (is_numeric($line)) {
                $productId = (int) $key;
                $normalized[$this->makeCartLineKey($productId)] = [
                    'product_id' => $productId,
                    'quantity' => (int) $line,
                ];
                continue;
            }

            if (!is_array($line) || empty($line['product_id'])) {
                continue;
            }

            $normalized[$key] = [
                'product_id' => (int) $line['product_id'],
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
            ];
        }

        return $normalized;
    }

    protected function buildSessionCartItems(): array
    {
        $cart = $this->getCartLines();
        $products = Product::whereIn('id', collect($cart)->pluck('product_id')->unique()->all())->get()->keyBy('id');

        $items = [];
        foreach ($cart as $lineKey => $line) {
            $product = $products[$line['product_id']] ?? null;
            if (!$product) {
                continue;
            }

            $unitPrice = $product->effective_price;
            $quantity = max((int) ($line['quantity'] ?? 1), (int) ($product->min_quantity ?? 1));

            $items[] = [
                'line_key' => $lineKey,
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
            ];
        }

        return $items;
    }

    protected function makeCartLineKey(int $productId): string
    {
        return (string) $productId;
    }
}
