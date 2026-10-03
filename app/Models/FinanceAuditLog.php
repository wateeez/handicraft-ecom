<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceAuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['order_id', 'client_id', 'invoice_id', 'user_id', 'subject_type', 'subject_id', 'action_type', 'description', 'old_values', 'new_values', 'ip_address'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subject(): MorphTo { return $this->morphTo(); }

    public function scopeForTimeline($query, ?int $orderId = null)
    {
        return $query->when($orderId, fn ($builder) => $builder->where('order_id', $orderId))
            ->orderByDesc('created_at');
    }
}
