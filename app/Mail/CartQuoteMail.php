<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CartQuoteMail extends Mailable
{


    /**
     * @param  array  $customerData  { name, email, phone, address, city, zip_code, country }
     * @param  array  $cartItems     Each: { product, quantity, unit_price, subtotal }
     * @param  float  $subtotal
     * @param  float  $shippingCost
     * @param  string $shippingProvider
     */
    public function __construct(
        public array $customerData,
        public array $cartItems,
        public float $subtotal,
        public float $shippingCost,
        public string $shippingProvider,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Quote Request from ' . $this->customerData['name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cart-quote',
        );
    }
}
