<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Product;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\OrderService;
use App\Services\ShippingService;

class CartController extends Controller
{
    protected $shippingService;
    protected $orderService;

    public function __construct(ShippingService $shippingService, OrderService $orderService)
    {
        $this->shippingService = $shippingService;
        $this->orderService = $orderService;
    }

    // Add to Cart (Session based for guests)
    public function add(Request $request)
    {
        $productId = $request->product_id;
        $quantity = $request->quantity ?? 1;
        $purchaseType = $request->purchase_type ?? 'Normal';
        $spiritualOption = $request->spiritual_option ?? null;
        $optionPrice = (float)($request->option_price ?? 0);

        // Validate product exists
        $product = Product::find($productId);
        if (!$product) {
            return redirect()->route('products.index')->with('error', 'Product not found.');
        }

        // Validate spiritual options if product has them
        if ($product->has_spiritual_options) {
            $validOptions = ['Filling Only', 'Blessing Only', 'Both Filling & Blessing'];
            
            // If a spiritual option is selected, validate it
            if ($spiritualOption && !in_array($spiritualOption, $validOptions)) {
                return redirect()->back()->with('error', 'Invalid spiritual option selected.');
            }

            // Validate option price matches the product's configured price
            if ($spiritualOption) {
                $expectedPrice = match($spiritualOption) {
                    'Filling Only' => (float)$product->price_filling_only,
                    'Blessing Only' => (float)$product->price_blessing_only,
                    'Both Filling & Blessing' => (float)$product->price_both,
                    default => 0
                };

                if (abs($optionPrice - $expectedPrice) > 0.01) { // Allow for floating point tolerance
                    return redirect()->back()->with('error', 'Invalid option price provided.');
                }
            }
        } else {
            // If product doesn't have spiritual options, reject any spiritual option
            if ($spiritualOption) {
                return redirect()->back()->with('error', 'This product does not support spiritual options.');
            }
            $optionPrice = 0;
        }

        // Validate purchase type
        $validPurchaseTypes = ['Normal', 'Sale'];
        if (!in_array($purchaseType, $validPurchaseTypes)) {
            return redirect()->back()->with('error', 'Invalid purchase type selected.');
        }

        // Build a unique cart key combining product_id + spiritual_option
        // This allows the same product with different options to be separate cart items
        $cartKey = $product->id . '_' . ($spiritualOption ? md5($spiritualOption) : 'none');

        $cart = session()->get('cart', []);

        // If item already in cart, update quantity; otherwise create new entry
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'purchase_type' => $purchaseType,
                'spiritual_option' => $spiritualOption,
                'option_price' => $optionPrice,
                'added_at' => now()->timestamp
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Product added to cart!');
    }

    public function index()
    {
        $cart = session()->get('cart', []);
        
        // Extract unique product IDs from cart
        $productIds = array_unique(array_column($cart, 'product_id') ?: []);
        $products = Product::whereIn('id', $productIds)->get();
        $productMap = $products->keyBy('id');

        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $cartKey => $cartItem) {
            $product = $productMap->get($cartItem['product_id']);
            if (!$product) continue;

            $itemPrice = $product->effective_price + $cartItem['option_price'];
            $itemSubtotal = $itemPrice * $cartItem['quantity'];

            $cartItems[] = [
                'cartKey' => $cartKey,
                'product' => $product,
                'quantity' => $cartItem['quantity'],
                'purchase_type' => $cartItem['purchase_type'] ?? 'Normal',
                'spiritual_option' => $cartItem['spiritual_option'] ?? null,
                'option_price' => $cartItem['option_price'] ?? 0,
                'item_price' => $itemPrice,
                'subtotal' => $itemSubtotal
            ];
            $subtotal += $itemSubtotal;
        }

        return view('frontend.cart.index', compact('cartItems', 'subtotal'));
    }

    // Checkout Page (Handles both Cart and Inquiry Link)
    public function checkout($token = null)
    {
        $inquiry = null;
        $items = [];
        $subtotal = 0;

        if ($token) {
            // CheckOMS Order flow first
            $omsOrder = Order::where('checkout_token', $token)->where('type', 'inquiry')->first();

            if ($omsOrder) {
                // Return entirely different view or load items
                $omsOrder->load('items.product');
                foreach ($omsOrder->items as $item) {
                    $items[] = [
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->line_total
                    ];
                    $subtotal += $item->line_total;
                }
                $inquiry = $omsOrder; // Pass OMS order as inquiry variable for now
            } else {
                // Inquiry Checkout Flow (Legacy)
                $inquiry = Inquiry::where('checkout_token', $token)->firstOrFail();

                if ($inquiry->product) {
                    $qty = $inquiry->product->min_quantity ?? 1; // Default to min quantity or 1?
                    $items[] = [
                        'product' => $inquiry->product,
                        'quantity' => $qty,
                        'subtotal' => $inquiry->product->effective_price * $qty
                    ];
                    $subtotal += ($inquiry->product->effective_price * $qty);
                }
            }
        } else {
            // Standard Cart Flow
            $cart = session()->get('cart', []);
            if (empty($cart))
                return redirect()->route('home');

            // Extract unique product IDs from cart
            $productIds = array_unique(array_column($cart, 'product_id') ?: []);
            $products = Product::whereIn('id', $productIds)->get();
            $productMap = $products->keyBy('id');

            foreach ($cart as $cartKey => $cartItem) {
                $product = $productMap->get($cartItem['product_id']);
                if (!$product) continue;

                $itemPrice = $product->effective_price + $cartItem['option_price'];
                $itemSubtotal = $itemPrice * $cartItem['quantity'];

                $items[] = [
                    'cartKey' => $cartKey,
                    'product' => $product,
                    'quantity' => $cartItem['quantity'],
                    'purchase_type' => $cartItem['purchase_type'] ?? 'Normal',
                    'spiritual_option' => $cartItem['spiritual_option'] ?? null,
                    'option_price' => $cartItem['option_price'] ?? 0,
                    'item_price' => $itemPrice,
                    'subtotal' => $itemSubtotal
                ];
                $subtotal += $itemSubtotal;
            }
        }

        // Build available countries options (value: raw from DB; label: friendly name)
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

    // AJAX Shipping Calculation
    public function calculateShipping(Request $request)
    {
        $country = $request->country;
        $token = $request->token;

        // Reconstruct Items
        $items = [];
        if ($token) {
            // Try OMS order first, then legacy inquiry
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
            $cart = session()->get('cart', []);
            $products = Product::whereIn('id', array_keys($cart))->get();
            foreach ($products as $product) {
                $items[] = ['product' => $product, 'quantity' => $cart[$product->id]];
            }
        }

        if (empty($items)) {
            return response()->json(['error' => 'No items to calculate shipping for.'], 400);
        }

        $rates = $this->shippingService->calculateShipping($items, $country);

        return response()->json(['rates' => $rates]);
    }

    // Update cart quantity
    public function updateQuantity(Request $request)
    {
        $cartKey = $request->cart_key;
        $quantity = max(1, (int) $request->quantity); // Ensure at least 1

        $cart = session()->get('cart', []);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = $quantity;
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated!');
    }

    // Remove item from cart
    public function removeItem(Request $request)
    {
        $cartKey = $request->cart_key;
        $cart = session()->get('cart', []);

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed from cart!');
    }

    /**
     * Called by the checkout page JS before PayPal is initialised.
     * Creates a pending (unpaid) order in the DB so the authoritative
     * amount lives on the server, not in the browser.
     * Returns JSON { order_id, grand_total }.
     */
    public function initOrder(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'address'       => ['required', 'string', 'max:500'],
            'city'          => ['required', 'string', 'max:100'],
            'zip_code'      => ['nullable', 'string', 'max:20'],
            'country'       => ['required', 'string', 'max:10'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'token'         => ['nullable', 'string'],
        ]);

        $token = $validated['token'] ?? null;

        // ----- Build item list -----
        $items = [];

        if ($token) {
            $omsOrder = Order::where('checkout_token', $token)->where('type', 'inquiry')->first();

            if ($omsOrder) {
                $omsOrder->load('items.product');
                foreach ($omsOrder->items as $item) {
                    $items[] = [
                        'product_id'        => $item->product_id,
                        'quantity'          => $item->quantity,
                        'unit_price'        => $item->unit_price,
                        'weight_kg'         => $item->weight_kg ?? ($item->product?->weight ?? 0),
                        'item_discount_type' => $item->item_discount_type ?? 'none',
                        'item_discount_value' => $item->item_discount_value ?? 0,
                    ];
                }

                // Invalidate the token so it cannot be reused to place duplicate orders.
                $omsOrder->checkout_token = null;
                $omsOrder->save();
            } else {
                // Legacy Inquiry flow (single product)
                $inquiry = Inquiry::where('checkout_token', $token)->first();
                if ($inquiry && $inquiry->product) {
                    $product = $inquiry->product;
                    $qty = $product->min_quantity ?? 1;
                    $items[] = [
                        'product_id'        => $product->id,
                        'quantity'          => $qty,
                        'unit_price'        => $product->effective_price,
                        'weight_kg'         => $product->weight ?? 0,
                        'item_discount_type' => 'none',
                        'item_discount_value' => 0,
                    ];
                }
            }
        } else {
            $cart = session()->get('cart', []);
            if (empty($cart)) {
                return response()->json(['error' => 'Your cart is empty.'], 422);
            }
            
            // Extract unique product IDs from cart
            $productIds = array_unique(array_column($cart, 'product_id') ?: []);
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            
            foreach ($cart as $cartKey => $cartItem) {
                $product = $products[$cartItem['product_id']] ?? null;
                if (!$product) continue;
                
                $items[] = [
                    'product_id'        => $product->id,
                    'purchase_type'     => $cartItem['purchase_type'] ?? 'Normal',
                    'quantity'          => $cartItem['quantity'],
                    'unit_price'        => $product->effective_price,
                    'weight_kg'         => $product->weight ?? 0,
                    'item_discount_type' => 'none',
                    'item_discount_value' => 0,
                    'spiritual_option'  => $cartItem['spiritual_option'] ?? null,
                    'option_price'      => $cartItem['option_price'] ?? 0,
                ];
            }
        }

        if (empty($items)) {
            return response()->json(['error' => 'No items found for this order.'], 422);
        }

        // ----- Find or create Client -----
        $client = Client::where('email', $validated['email'])->first();

        if (!$client) {
            $client = Client::create([
                'buyer_id'     => Client::generateBuyerId(),
                'name'         => $validated['name'],
                'email'        => $validated['email'],
                'phone'        => $validated['phone'] ?? null,
                'address_line' => $validated['address'],
                'city'         => $validated['city'],
                'zip_code'     => $validated['zip_code'] ?? null,
                'country'      => $validated['country'],
            ]);
        }

        // ----- Create pending order -----
        $order = $this->orderService->createOrder([
            'type'          => Order::TYPE_ORDER,
            'client_id'     => $client->id,
            'shipping_cost' => $validated['shipping_cost'],
            'items'         => $items,
        ]);

        return response()->json([
            'order_id'    => $order->id,
            'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
        ]);
    }

    /**
     * Show the order success / thank-you page after PayPal payment.
     */
    public function orderSuccess(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('is_paid', true)
            ->firstOrFail();

        return view('frontend.cart.order-success', compact('order'));
    }
}
