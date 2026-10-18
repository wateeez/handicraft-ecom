<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceOrderSnapshot extends Model
{
    protected $fillable = ['order_id', 'vendor_id', 'currency', 'reporting_exchange_rate', 'vendor_share', 'payment_fee', 'direct_cost', 'commission_type', 'commission_value', 'commission_base', 'commission_amount', 'financial_snapshot', 'locked_at'];
    protected $casts = ['reporting_exchange_rate' => 'decimal:8', 'vendor_share' => 'decimal:2', 'payment_fee' => 'decimal:2', 'direct_cost' => 'decimal:2', 'commission_value' => 'decimal:2', 'commission_amount' => 'decimal:2', 'financial_snapshot' => 'array', 'locked_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class)->withTrashed(); }
}
