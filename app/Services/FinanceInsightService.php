<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;

class FinanceInsightService
{
    public function clientSummary(Client $client): array
    {
        $confirmedPayments = Payment::where('client_id', $client->id)->where('status', 'confirmed');
        $gross = (float) (clone $confirmedPayments)->sum('reporting_amount');
        $refunds = (float) Refund::whereIn('payment_id', (clone $confirmedPayments)->select('id'))
            ->where('status', 'confirmed')->sum('reporting_amount');
        $orders = Order::where('client_id', $client->id);
        $paidOrders = (clone $orders)->where('is_paid', true);

        return [
            'lifetime_spend' => $gross - $refunds,
            'total_orders' => (clone $paidOrders)->count(),
            'cancelled_orders' => (clone $orders)->where('status', Order::STATUS_CANCELLED)->count(),
            'average_order_value' => (float) (clone $paidOrders)->avg('grand_total') ?: 0,
            'first_purchase_at' => (clone $paidOrders)->min('created_at'),
            'last_purchase_at' => (clone $paidOrders)->max('created_at'),
        ];
    }

    public function outstandingAging(): array
    {
        return Order::query()
            ->where('type', Order::TYPE_ORDER)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->selectRaw('orders.id, orders.created_at, orders.grand_total,
                COALESCE((SELECT SUM(payments.amount) FROM payments WHERE payments.order_id = orders.id AND payments.status = "confirmed" AND payments.deleted_at IS NULL), 0)
                - COALESCE((SELECT SUM(refunds.amount) FROM refunds WHERE refunds.order_id = orders.id AND refunds.status = "confirmed" AND refunds.deleted_at IS NULL), 0) AS net_paid')
            ->get()
            ->filter(fn (Order $order) => (float) $order->net_paid < (float) $order->grand_total)
            ->groupBy(fn (Order $order) => match (true) {
                $order->created_at->diffInDays(now()) <= 7 => '0_7',
                $order->created_at->diffInDays(now()) <= 30 => '8_30',
                default => '31_plus',
            })
            ->map(fn ($orders) => [
                'count' => $orders->count(),
                'balance' => $orders->sum(fn (Order $order) => (float) $order->grand_total - (float) $order->net_paid),
            ])->all();
    }
}
