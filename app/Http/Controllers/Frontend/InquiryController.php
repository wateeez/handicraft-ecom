<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Client;
use App\Models\Order;
use App\Services\OrderService;

use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'product_id' => 'required|exists:products,id',
            'message' => 'required|string'
        ]);

        $product = Product::findOrFail($request->product_id);


        // Find or create client
        $client = Client::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'buyer_id' => Client::generateBuyerId(),
            ]
        );

            $fromEmail = env('MAIL_FROM_ADDRESS');
            $fromName  = env('MAIL_FROM_APP_NAME');

            $toEmail = $request->email;
            $toName  = $request->name;

                $subject = "Inquiry Received for Product: {$product->name}";

                $body = "Dear {$request->name},<br><br>"
      . "Thank you for your inquiry about the product: {$product->name} .<br><br>"
      . "We will get back to you shortly.<br><br>"
      . "Best regards,<br>{$fromName}";

        Mail::html($body, function ($message) use ($toEmail, $toName, $fromEmail, $fromName, $subject) {
                $message->to($toEmail, $toName)
             ->from($fromEmail, $fromName)
             ->subject($subject);
});


        // Prepare order data
        $orderData = [
            'type' => Order::TYPE_INQUIRY,
            'client_id' => $client->id,
            'notes' => $request->message,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->effective_price ?? $product->price,
                    'weight_kg' => $product->weight,
                ]
            ]
        ];

        // Create the unified Inquiry (Order record)
        $this->orderService->createOrder($orderData, null);

        return back()->with('success', 'Your inquiry has been sent! We will contact you shortly.');
    }
}

