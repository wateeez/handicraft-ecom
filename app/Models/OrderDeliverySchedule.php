<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDeliverySchedule extends Model
{
    protected $fillable = ['order_id', 'expected_delivery_at', 'status', 'scheduled_at', 'completed_at', 'cancelled_at', 'cancellation_reason'];
    protected $casts = ['expected_delivery_at' => 'datetime', 'scheduled_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
