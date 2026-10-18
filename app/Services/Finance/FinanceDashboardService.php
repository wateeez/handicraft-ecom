<?php

namespace App\Services\Finance;

use App\Models\FinancialLedgerEntry;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\PayoutPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Read-only figures behind the Finance overview charts and detail tabs.
 * Receipts are in the reporting currency; profit and payouts are in NPR.
 */
class FinanceDashboardService
{
    private const DAILY_LIMIT_DAYS = 62;

    public function build(?string $from, ?string $to): array
    {
        $snapshots = $this->latestPaidSnapshots();

        return [
            'receipts' => $this->receiptsSeries($from, $to),
            'profit' => $this->profit($snapshots),
            'recentPayouts' => PayoutPayment::query()
                ->with(['payable.order:id,order_number', 'recordedBy:id,name'])
                ->latest('paid_on')->latest('id')->limit(8)->get(),
        ];
    }

    /**
     * Receipts vs refunds + reversals per day (or per month for long ranges).
     * With no filter this shows the last 30 days.
     */
    public function receiptsSeries(?string $from, ?string $to): array
    {
        $end = $this->parseDate($to) ?? CarbonImmutable::today();
        $start = $this->parseDate($from) ?? $end->subDays(29);
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $monthly = $start->diffInDays($end) > self::DAILY_LIMIT_DAYS;
        $format = $monthly ? 'Y-m' : 'Y-m-d';

        $buckets = [];
        $cursor = $monthly ? $start->startOfMonth() : $start;
        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets[$cursor->format($format)] = [
                'label' => $monthly ? $cursor->format('M Y') : $cursor->format('M j'),
                'receipts' => '0',
                'outflow' => '0',
            ];
            $cursor = $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        FinancialLedgerEntry::query()
            ->whereIn('entry_type', ['payment_confirmed', 'refund_confirmed', 'payment_reversed'])
            ->where('occurred_at', '>=', $start->startOfDay())
            ->where('occurred_at', '<=', $end->endOfDay())
            ->get(['entry_type', 'reporting_amount', 'occurred_at'])
            ->each(function ($entry) use (&$buckets, $format) {
                $key = $entry->occurred_at->format($format);
                if (!isset($buckets[$key])) {
                    return;
                }
                $field = $entry->entry_type === 'payment_confirmed' ? 'receipts' : 'outflow';
                $buckets[$key][$field] = bcadd($buckets[$key][$field], bcmul(ltrim((string) $entry->reporting_amount, '-'), '1', 2), 2);
            });

        $rows = collect($buckets)->map(fn ($b) => $b + ['net' => bcsub($b['receipts'], $b['outflow'], 2)])->values();

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'granularity' => $monthly ? 'month' : 'day',
            'filtered' => $from !== null || $to !== null,
            'rows' => $rows,
            'labels' => $rows->pluck('label')->all(),
            'receipts' => $rows->pluck('receipts')->map('floatval')->all(),
            'outflow' => $rows->pluck('outflow')->map('floatval')->all(),
            'net' => $rows->pluck('net')->map('floatval')->all(),
        ];
    }

    /**
     * Latest calculation per paid, non-cancelled, non-refunded order.
     */
    private function latestPaidSnapshots(): Collection
    {
        return OrderFinanceSnapshot::query()
            ->whereIn('id', OrderFinanceSnapshot::selectRaw('MAX(id)')->groupBy('order_id'))
            ->whereHas('order', fn ($q) => $q->where('is_paid', true)->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED]))
            ->with('order:id,order_number')
            ->orderBy('id')
            ->get();
    }

    private function profit(Collection $snapshots): array
    {
        $sum = fn (callable $fn) => $snapshots->reduce(fn (string $carry, $s) => bcadd($carry, (string) $fn($s), 2), '0');

        $gateway = $sum(fn ($s) => bcmul((string) $s->gateway_fee_usd, (string) $s->forex_rate, 2));
        $parts = [
            'vendor' => $sum(fn ($s) => $s->vendor_total_npr),
            'csr' => $sum(fn ($s) => $s->csr_npr),
            'data_entry' => $sum(fn ($s) => $s->data_entry_npr),
            'gateway' => $gateway,
            'shipping' => $sum(fn ($s) => $s->actual_shipping_cost_npr),
            'net' => $sum(fn ($s) => $s->net_profit_npr),
        ];

        $byOrder = $snapshots->take(-12)->values()->map(fn ($s) => [
            'label' => $s->order?->order_number ?? '#' . $s->order_id,
            'order_id' => $s->order_id,
            'net' => (float) $s->net_profit_npr,
            'received' => (float) $s->received_npr,
        ]);

        $months = collect(range(5, 0))->map(fn (int $ago) => CarbonImmutable::today()->startOfMonth()->subMonths($ago));
        $byMonth = $months->map(fn (CarbonImmutable $m) => [
            'label' => $m->format('M'),
            'net' => (float) $snapshots
                ->filter(fn ($s) => $s->created_at->format('Y-m') === $m->format('Y-m'))
                ->reduce(fn (string $c, $s) => bcadd($c, (string) $s->net_profit_npr, 2), '0'),
        ]);

        $vendors = $snapshots->groupBy(fn ($s) => $s->vendor_name ?? 'Unassigned')->map(fn (Collection $rows, string $name) => [
            'name' => $name,
            'orders' => $rows->count(),
            'received' => $rows->reduce(fn (string $c, $s) => bcadd($c, (string) $s->received_npr, 2), '0'),
            'net' => $rows->reduce(fn (string $c, $s) => bcadd($c, (string) $s->net_profit_npr, 2), '0'),
        ])->sortByDesc(fn ($v) => (float) $v['net'])->values();

        $count = $snapshots->count();

        return [
            'parts' => $parts,
            'gross' => array_reduce(array_keys($parts), fn (string $c, string $k) => bcadd($c, bccomp($parts[$k], '0', 2) > 0 ? $parts[$k] : '0', 2), '0'),
            'by_order' => $byOrder,
            'by_month' => $byMonth,
            'vendors' => $vendors,
            'count' => $count,
            'average' => $count > 0 ? bcdiv($parts['net'], (string) $count, 2) : '0.00',
            'received' => $sum(fn ($s) => $s->received_npr),
        ];
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
