<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'payment_id', 'recorded_by', 'amount', 'currency', 'reporting_amount', 'reporting_exchange_rate', 'status', 'reason', 'confirmed_at'];
    protected $casts = ['amount' => 'decimal:2', 'reporting_amount' => 'decimal:2', 'reporting_exchange_rate' => 'decimal:8', 'confirmed_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
