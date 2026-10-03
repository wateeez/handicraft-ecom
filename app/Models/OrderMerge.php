<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderMerge extends Model
{
    protected $fillable = ['target_order_id', 'client_id', 'merged_by', 'reason', 'merged_at'];
    protected $casts = ['merged_at' => 'datetime'];

    public function targetOrder(): BelongsTo { return $this->belongsTo(Order::class, 'target_order_id'); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function mergedBy(): BelongsTo { return $this->belongsTo(User::class, 'merged_by'); }
    public function members(): HasMany { return $this->hasMany(OrderMergeMember::class); }
}
