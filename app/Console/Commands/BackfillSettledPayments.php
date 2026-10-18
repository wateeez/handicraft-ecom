<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\FinanceService;
use Illuminate\Console\Command;

class BackfillSettledPayments extends Command
{
    protected $signature = 'finance:backfill-payments {--apply : Actually record the payments (default is a dry run)} {--method=cash : Payment method to record}';

    protected $description = 'Record the missing confirmed payment for paid orders that have none (left behind by failed mark-as-paid runs)';

    public function handle(FinanceService $finance): int
    {
        $orders = Order::query()
            ->where('is_paid', true)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'confirmed'))
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No paid orders are missing a payment.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Order', 'Status', 'Total', 'Currency'], $orders->map(fn ($o) => [$o->id, $o->order_number, $o->status, $o->grand_total, $o->currency])->all());

        if (!$this->option('apply')) {
            $this->warn('Dry run. Re-run with --apply to record a confirmed ' . $this->option('method') . ' payment for each order above.');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($orders as $order) {
            try {
                $payment = $finance->recordSettledPayment($order, (string) $this->option('method'));
                $this->line("{$order->order_number}: " . ($payment ? "recorded payment #{$payment->id}" : 'already covered, skipped'));
            } catch (\Throwable $e) {
                $failed++;
                report($e);
                $this->error("{$order->order_number}: " . $e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
