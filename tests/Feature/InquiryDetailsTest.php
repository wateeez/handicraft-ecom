<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_stores_all_contact_details_and_completes_existing_client(): void
    {
        $product = Product::factory()->create();
        Client::create(['buyer_id' => 'BYR-00001', 'name' => 'Old', 'email' => 'a@b.com']);

        $this->post(route('inquiry.store', $product), [
            'name' => 'Ann', 'email' => 'a@b.com', 'phone' => '+977 9800000000',
            'country' => 'Nepal', 'address_line' => '1 Main St', 'city' => 'Kathmandu',
            'zip_code' => '44600', 'product_id' => $product->id, 'message' => 'Hi',
        ])->assertSessionHasNoErrors();

        $client = Client::where('email', 'a@b.com')->first();
        $this->assertSame('+977 9800000000', $client->phone);
        $this->assertSame('NP', $client->country);
        $this->assertSame('Kathmandu', $client->city);
        $this->assertSame('Old', $client->name);

        $inquiry = Order::where('type', 'inquiry')->first();
        $this->assertStringStartsWith('INQ-', $inquiry->order_number);
        $this->assertSame('NP', $inquiry->client_snapshot['country']);
    }

    public function test_checkout_link_prefills_details_from_client(): void
    {
        $product = Product::factory()->create();
        $client = Client::create([
            'buyer_id' => 'BYR-00002', 'name' => 'Ann', 'email' => 'ann@b.com', 'phone' => '+977 98000',
            'address_line' => '1 Main St', 'city' => 'Kathmandu', 'zip_code' => '44600', 'country' => 'NP',
        ]);
        $order = Order::create(['order_number' => 'INQ-T-1', 'type' => 'inquiry', 'client_id' => $client->id, 'checkout_token' => 'tok123']);

        $this->get(route('checkout', 'tok123'))->assertOk()
            ->assertSee('ann@b.com')->assertSee('+977 98000')->assertSee('1 Main St')->assertSee('Kathmandu');
    }
}
