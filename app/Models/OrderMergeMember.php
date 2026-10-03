<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMergeMember extends Model
{
    protected $fillable = ['order_merge_id', 'source_order_id', 'source_order_number', 'financial_snapshot'];
    protected $casts = ['financial_snapshot' => 'array'];

    public function merge(): BelongsTo { return $this->belongsTo(OrderMerge::class, 'order_merge_id'); }
    public function sourceOrder(): BelongsTo { return $this->belongsTo(Order::class, 'source_order_id'); }
}
