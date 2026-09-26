<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use Tests\TestCase;

class OrderReturnStatusTest extends TestCase
{
    public function test_order_returns_a_clear_return_summary_for_admin_visibility(): void
    {
        $order = new Order([
            'status' => Order::STATUS_DISPATCHED,
        ]);

        $order->setRelation('items', collect([
            new OrderItem(['return_status' => OrderItem::RETURN_STATUS_NONE]),
            new OrderItem(['return_status' => OrderItem::RETURN_STATUS_PARTIAL]),
            new OrderItem(['return_status' => OrderItem::RETURN_STATUS_RETURNED]),
        ]));

        $this->assertSame('Partially Returned', $order->return_summary_label);
        $this->assertSame('amber', $order->return_summary_color);
    }
}
