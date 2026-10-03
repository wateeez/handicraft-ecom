<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributorPayable extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'distributor_id', 'currency', 'original_amount', 'adjusted_amount', 'paid_amount', 'status'];
    protected $casts = ['original_amount' => 'decimal:2', 'adjusted_amount' => 'decimal:2', 'paid_amount' => 'decimal:2'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function distributor(): BelongsTo { return $this->belongsTo(Distributor::class); }
    public function payoutItems(): HasMany { return $this->hasMany(DistributorPayoutItem::class); }
}
