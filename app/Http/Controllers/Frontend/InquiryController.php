<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Client;
use App\Models\Order;
use App\Services\OrderService;

class InquiryController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'country' => 'required|string|max:100',
            'address_line' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'product_id' => 'required|exists:products,id',
            'message' => 'nullable|string|max:5000',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Find or create the client, then complete any details we were missing
        $client = Client::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'buyer_id' => Client::generateBuyerId(),
            ]
        );
        $client->fillMissing($validated);

        // Create the inquiry first; its confirmation/admin emails are dispatched by OrderService after commit.
        $this->orderService->createOrder([
            'type' => Order::TYPE_INQUIRY,
            'client_id' => $client->id,
            'notes' => $validated['message'] ?? null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->effective_price ?? $product->price,
                    'weight_kg' => $product->weight,
                ]
            ]
        ], null);

        return back()->with('success', 'Your inquiry has been sent! We will contact you shortly.');
    }
}
