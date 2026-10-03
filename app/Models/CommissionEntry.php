<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionEntry extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'recipient_id', 'currency', 'original_amount', 'adjusted_amount', 'paid_amount', 'basis_snapshot', 'status', 'earned_at', 'paid_at'];
    protected $casts = ['original_amount' => 'decimal:2', 'adjusted_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'basis_snapshot' => 'array', 'earned_at' => 'datetime', 'paid_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function recipient(): BelongsTo { return $this->belongsTo(CommissionRecipient::class, 'recipient_id'); }
    public function payoutItems(): HasMany { return $this->hasMany(CommissionPayoutItem::class); }
}
