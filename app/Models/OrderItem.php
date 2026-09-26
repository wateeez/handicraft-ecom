<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    const RETURN_STATUS_NONE = 'none';
    const RETURN_STATUS_PARTIAL = 'partially_returned';
    const RETURN_STATUS_RETURNED = 'returned';

    const RETURN_STATUS_LABELS = [
        self::RETURN_STATUS_NONE => 'Not Returned',
        self::RETURN_STATUS_PARTIAL => 'Partially Returned',
        self::RETURN_STATUS_RETURNED => 'Returned',
    ];

    const RETURN_STATUS_COLORS = [
        self::RETURN_STATUS_NONE => 'gray',
        self::RETURN_STATUS_PARTIAL => 'amber',
        self::RETURN_STATUS_RETURNED => 'red',
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'product_snapshot',
        'quantity',
        'unit_price',
        'weight_kg',
        'item_discount_type',
        'item_discount_value',
        'item_discount_amount',
        'line_total',
        'return_status',
        'returned_quantity',
        'return_reason',
        'returned_at',
        'returned_by',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'item_discount_value' => 'decimal:2',
        'item_discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'returned_quantity' => 'integer',
        'returned_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * How many units of this line item have not (yet) been returned.
     */
    public function getReturnableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    public function getReturnStatusLabelAttribute(): string
    {
        return self::RETURN_STATUS_LABELS[$this->return_status] ?? ucfirst($this->return_status);
    }

    public function getReturnStatusColorAttribute(): string
    {
        return self::RETURN_STATUS_COLORS[$this->return_status] ?? 'gray';
    }

    /**
     * Calculate the item discount amount and line total
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->unit_price * $this->quantity;

        if ($this->item_discount_type === 'percent') {
            $this->item_discount_amount = round($subtotal * ($this->item_discount_value / 100), 2);
        } elseif ($this->item_discount_type === 'fixed') {
            $this->item_discount_amount = min($this->item_discount_value, $subtotal);
        } else {
            $this->item_discount_amount = 0;
        }

        $this->line_total = max(0, $subtotal - $this->item_discount_amount);
    }

    /**
     * Get product name from snapshot or product relation
     */
    public function getProductNameAttribute(): string
    {
        return $this->product_snapshot['name'] ?? ($this->product?->name ?? 'Unknown Product');
    }

    /**
     * Get product SKU from snapshot
     */
    public function getProductSkuAttribute(): string
    {
        return $this->product_snapshot['sku'] ?? '-';
    }


}
