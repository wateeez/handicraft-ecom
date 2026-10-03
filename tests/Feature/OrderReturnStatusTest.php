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

    public function test_returned_and_refunded_are_available_terminal_order_statuses(): void
    {
        $this->assertContains(Order::STATUS_RETURNED, Order::STATUSES);
        $this->assertContains(Order::STATUS_REFUNDED, Order::STATUSES);
        $this->assertSame('Returned', Order::STATUS_LABELS[Order::STATUS_RETURNED]);
        $this->assertSame('Refunded', Order::STATUS_LABELS[Order::STATUS_REFUNDED]);

        $deliveredOrder = new Order(['status' => Order::STATUS_DELIVERED]);
        $returnedOrder = new Order(['status' => Order::STATUS_RETURNED]);
        $cancelledOrder = new Order(['status' => Order::STATUS_CANCELLED]);
        $refundedOrder = new Order(['status' => Order::STATUS_REFUNDED]);

        $this->assertTrue($deliveredOrder->canTransitionTo(Order::STATUS_RETURNED));
        $this->assertTrue($deliveredOrder->canTransitionTo(Order::STATUS_REFUNDED));
        $this->assertTrue($returnedOrder->canTransitionTo(Order::STATUS_REFUNDED));
        $this->assertTrue($cancelledOrder->canTransitionTo(Order::STATUS_REFUNDED));
        $this->assertFalse($refundedOrder->canTransitionTo(Order::STATUS_RETURNED));
        $this->assertFalse($refundedOrder->isCancellable());
        $this->assertFalse($refundedOrder->canBeMerged());
    }
}
