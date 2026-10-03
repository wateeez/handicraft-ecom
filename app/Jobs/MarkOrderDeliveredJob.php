<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderDeliverySchedule;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MarkOrderDeliveredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Do not retry this job. Auto-delivery is idempotent only when tried once;
     * retries risk sending duplicate "delivered" notifications.
     */
    public int $tries = 1;

    public function __construct(public int $orderId)
    {
    }

    public function handle(NotificationService $notificationService): void
    {
        // Use a DB transaction with a row-level lock to ensure only one job
        // instance can transition this order, even if the queue delivers it twice.
        $shouldNotify = DB::transaction(function () {
            $order = Order::lockForUpdate()->find($this->orderId);

            if (!$order) {
                return null; // Order deleted
            }

            // Only auto-deliver if still dispatched
            $schedule = OrderDeliverySchedule::lockForUpdate()
                ->where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();

            if (!$schedule || $order->status !== Order::STATUS_DISPATCHED || $schedule->expected_delivery_at->isFuture()) {
                return null; // Already transitioned by another process
            }

            $order->status = Order::STATUS_DELIVERED;
            $order->delivered_at = now();
            $order->save();
            $schedule->update(['status' => 'completed', 'completed_at' => now()]);

            AuditLogService::log(
                'auto_delivered',
                $order,
                null,
                ['status' => Order::STATUS_DISPATCHED],
                ['status' => Order::STATUS_DELIVERED],
                null,
                "Order #{$order->order_number} automatically marked as Delivered after delivery period.",
                null
            );

            return $order;
        });

        if ($shouldNotify) {
            $notificationService->notify($shouldNotify, Order::STATUS_DELIVERED);
        }
    }
}
