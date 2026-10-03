<?php

namespace App\Services;

use App\Models\FinancialLedgerEntry;
use App\Models\Order;

class FinanceReportService
{
    public function receiptsSummary(?string $from = null, ?string $to = null): array
    {
        $entries = FinancialLedgerEntry::query()
            ->when($from, fn ($query) => $query->whereDate('occurred_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('occurred_at', '<=', $to));

        return [
            'receipts' => (float) (clone $entries)->where('entry_type', 'payment_confirmed')->sum('reporting_amount'),
            'refunds' => abs((float) (clone $entries)->where('entry_type', 'refund_confirmed')->sum('reporting_amount')),
            'reversals' => abs((float) (clone $entries)->where('entry_type', 'payment_reversed')->sum('reporting_amount')),
        ];
    }

    public function salesDetails(int $limit = 8): array
    {
        $sales = Order::query()
            ->where('type', Order::TYPE_ORDER)
            ->where('is_paid', true)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED]);

        $recentOrders = (clone $sales)
            ->with('client:id,name')
            ->latest()
            ->limit($limit)
            ->get();

        return [
            'count' => (clone $sales)->count(),
            'gross_sales' => (float) (clone $sales)->sum('grand_total'),
            'average_sale' => (float) (clone $sales)->avg('grand_total'),
            'recent_orders' => $recentOrders,
        ];
    }
}
