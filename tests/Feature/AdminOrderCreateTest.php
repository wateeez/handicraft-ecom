<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    // Skips auth/permission/CSRF but keeps route model binding, which update() needs.
    private function asAdmin()
    {
        return $this->withoutMiddleware([
            \App\Http\Middleware\CheckPermission::class,
            \App\Http\Middleware\ViewerReadOnly::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ])->actingAs(User::factory()->create());
    }

    private function submit(array $payload)
    {
        return $this->asAdmin()->post(route('admin.orders.store'), $payload);
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

    public function test_promoted_inquiry_does_not_cause_duplicate_order_number(): void
    {
        $item = ['product_name' => 'Bowl', 'quantity' => 1, 'unit_price' => 10];

        $this->submit(['type' => 'inquiry', 'items' => [$item]]);
        $inquiry = Order::where('type', 'inquiry')->firstOrFail();
        $this->submit(['type' => 'order', 'items' => [$item]]);

        $this->asAdmin()
            ->put(route('admin.orders.update', $inquiry), ['type' => 'order', 'items' => [$item]])
            ->assertRedirect();

        $this->submit(['type' => 'order', 'items' => [$item]])->assertRedirect();

        $numbers = Order::where('type', 'order')->pluck('order_number');
        $this->assertCount(3, $numbers);
        $this->assertCount(3, $numbers->unique());
    }

    public function test_edit_keeps_zero_price_custom_items_and_tracking(): void
    {
        $this->submit(['type' => 'order', 'items' => [['product_name' => 'Bowl', 'quantity' => 1, 'unit_price' => 10]]]);
        $order = Order::firstOrFail();

        $this->asAdmin()
            ->put(route('admin.orders.update', $order), [
                'type' => 'order',
                'tracking_number' => 'TRK999',
                'items' => [
                    ['product_name' => 'Bowl', 'quantity' => 1, 'unit_price' => 10],
                    ['product_name' => 'Free sample', 'quantity' => 1, 'unit_price' => 0, 'length' => 4],
                ],
            ])->assertRedirect();

        $order->refresh()->load('items');
        $this->assertSame('TRK999', $order->tracking_number);
        $this->assertCount(2, $order->items);
        $this->assertEquals(4, $order->items->firstWhere('unit_price', 0)->product_snapshot['length']);
    }

    public function test_invalid_discounts_and_provider_are_rejected(): void
    {
        $item = ['product_name' => 'Bowl', 'quantity' => 1, 'unit_price' => 10];

        $this->submit(['type' => 'order', 'items' => [$item + ['item_discount_type' => 'fixed', 'item_discount_value' => -5]]])
            ->assertSessionHasErrors('items.0.item_discount_value');
        $this->submit(['type' => 'order', 'items' => [$item + ['item_discount_type' => 'percent', 'item_discount_value' => 150]]])
            ->assertSessionHasErrors('items.0.item_discount_value');
        $this->submit(['type' => 'order', 'order_discount_type' => 'percent', 'order_discount_value' => 120, 'items' => [$item]])
            ->assertSessionHasErrors('order_discount_value');
        $this->submit(['type' => 'order', 'shipping_provider_id' => 9999, 'items' => [$item]])
            ->assertSessionHasErrors('shipping_provider_id');
        $this->submit(['type' => 'order', 'delivery_period_days' => 'soon', 'items' => [$item]])
            ->assertSessionHasErrors('delivery_period_days');

        $this->assertSame(0, Order::count());
    }
}
