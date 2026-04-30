<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
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
        'spiritual_option',
        'option_price',
        // 'purchase_type' removed as it is no longer needed
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'item_discount_value' => 'decimal:2',
        'item_discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'option_price' => 'decimal:2',
        'spiritual_option' => 'string',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
