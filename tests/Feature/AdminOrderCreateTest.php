<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    private function submit(array $payload)
    {
        return $this->withoutMiddleware()
            ->actingAs(User::factory()->create())
            ->post(route('admin.orders.store'), $payload);
    }

    public function test_zero_price_custom_item_dimensions_and_tracking_are_saved(): void
    {
        $this->submit([
            'type' => 'order',
            'tracking_number' => 'TRK123',
            'items' => [[
                'product_name' => 'Free sample', 'quantity' => 1, 'unit_price' => 0,
                'weight_kg' => '', 'length' => 10, 'width' => 5, 'height' => 2,
            ]],
        ])->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('TRK123', $order->tracking_number);
        $this->assertCount(1, $order->items);
        $this->assertEquals(10, $order->items[0]->product_snapshot['length']);
    }

    public function test_unknown_product_id_is_rejected_and_blank_rows_are_ignored(): void
    {
        $this->submit(['type' => 'order', 'items' => [['product_id' => 9999, 'quantity' => 1, 'unit_price' => 5]]])
            ->assertSessionHasErrors('items.0.product_id');

        $this->submit(['type' => 'order', 'items' => [['product_name' => '', 'quantity' => 1, 'unit_price' => 0]]])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Order::count());
    }
}
