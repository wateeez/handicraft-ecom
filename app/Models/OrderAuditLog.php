<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderAuditLog extends Model
{
    const UPDATED_AT = null; // Only created_at

    protected $fillable = [
        'order_id',
        'invoice_id',
        'subject_type',
        'subject_id',
        'user_id',
        'action_type',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable action type label
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'order_created' => 'Order Created',
            'order_updated' => 'Order Updated',
            'status_changed' => 'Status Changed',
            'item_added' => 'Item Added',
            'item_updated' => 'Item Updated',
            'item_removed' => 'Item Removed',
            'discount_changed' => 'Discount Updated',
            'tracking_updated' => 'Tracking Updated',
            'order_dispatched' => 'Order Dispatched',
            'order_delivered' => 'Order Delivered',
            'order_cancelled' => 'Order Cancelled',
            'order_merged' => 'Orders Merged',
            'marked_paid' => 'Marked as Paid',
            'invoice_generated' => 'Invoice Generated',
            'invoice_issued' => 'Invoice Issued',
            'invoice_voided' => 'Invoice Voided',
            'auto_delivered' => 'Auto-marked Delivered',
            'client_updated' => 'Client Updated',
            'item_returned' => 'Item Returned',
            'item_return_undone' => 'Item Return Reversed',
            default => ucwords(str_replace('_', ' ', $this->action_type)),
        };
    }

    /**
     * Get icon for action type
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action_type) {
            'order_created' => '📦',
            'status_changed',
            'order_dispatched',
            'order_delivered',
            'order_cancelled' => '🔄',
            'item_added',
            'item_updated',
            'item_removed' => '🛍️',
            'discount_changed' => '💰',
            'tracking_updated' => '🚚',
            'order_merged' => '🔗',
            'marked_paid' => '✅',
            'invoice_generated',
            'invoice_issued',
            'invoice_voided' => '🧾',
            'auto_delivered' => '🤖',
            'item_returned',
            'item_return_undone' => '↩️',
            default => '📝',
        };
    }
}
