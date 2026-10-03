<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\MarkOrderDeliveredJob;
use App\Models\OrderDeliverySchedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    OrderDeliverySchedule::query()
        ->where('status', 'pending')
        ->where('expected_delivery_at', '<=', now())
        ->orderBy('id')
        ->eachById(fn (OrderDeliverySchedule $schedule) => MarkOrderDeliveredJob::dispatch($schedule->order_id));
})->name('dispatch-due-order-deliveries')->everyFiveMinutes()->withoutOverlapping();
