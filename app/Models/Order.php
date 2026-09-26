<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_UNPROCESSED = 'unprocessed';
    const STATUS_QUOTATION_SENT = 'quotation_sent';
    const STATUS_PROCESSED = 'processed';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    // System-only status: set automatically when a quote inquiry is checked out and paid as a real order.
    const STATUS_CONVERTED = 'converted';

    const TYPE_INQUIRY = 'inquiry';
    const TYPE_ORDER = 'order';

    const STATUSES = [
        self::STATUS_UNPROCESSED,
        self::STATUS_QUOTATION_SENT,
        self::STATUS_PROCESSED,
        self::STATUS_DISPATCHED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
        self::STATUS_CONVERTED,
    ];

    const STATUS_LABELS = [
        self::STATUS_UNPROCESSED => 'Unprocessed',
        self::STATUS_QUOTATION_SENT => 'Quotation Sent',
        self::STATUS_PROCESSED => 'Processed',
        self::STATUS_DISPATCHED => 'Dispatched',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_CONVERTED => 'Converted to Order',
    ];

    const STATUS_COLORS = [
        self::STATUS_UNPROCESSED => 'gray',
        self::STATUS_QUOTATION_SENT => 'blue',
        self::STATUS_PROCESSED => 'yellow',
        self::STATUS_DISPATCHED => 'purple',
        self::STATUS_DELIVERED => 'green',
        self::STATUS_CANCELLED => 'red',
        self::STATUS_CONVERTED => 'indigo',
    ];

    // Allowed forward transitions (excluding override)
    const ALLOWED_TRANSITIONS = [
        self::STATUS_UNPROCESSED => [self::STATUS_QUOTATION_SENT, self::STATUS_PROCESSED, self::STATUS_CANCELLED],
        self::STATUS_QUOTATION_SENT => [self::STATUS_PROCESSED, self::STATUS_CANCELLED],
        self::STATUS_PROCESSED => [self::STATUS_DISPATCHED, self::STATUS_CANCELLED],
        self::STATUS_DISPATCHED => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
        // Converted is only ever set automatically (via override) when an inquiry's checkout is paid.
        self::STATUS_CONVERTED => [],
    ];

    protected $fillable = [
        'order_number',
        'type',
        'checkout_token',
        'client_id',
        'created_by',
        'status',
        'subtotal',
        'item_discount_total',
        'order_discount_type',
        'order_discount_value',
        'order_discount_amount',
        'shipping_cost',
        'total_weight_kg',
        'grand_total',
        'is_paid',
        'financial_locked_at',
        'shipping_provider_id',
        'tracking_number',
        'dispatched_at',
        'expected_delivery_at',
        'delivery_period_days',
        'client_snapshot',
        'merged_into_order_id',
        'is_merged',
        'merged_order_ids',
        'source_inquiry_id',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'client_snapshot' => 'array',
        'merged_order_ids' => 'array',
        'is_paid' => 'boolean',
        'is_merged' => 'boolean',
        'financial_locked_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'expected_delivery_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'item_discount_total' => 'decimal:2',
        'order_discount_value' => 'decimal:2',
        'order_discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_weight_kg' => 'decimal:3',
        'grand_total' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(OrderAuditLog::class)->orderByDesc('created_at');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(OrderNotification::class);
    }

    public function shippingProvider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function mergedIntoOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'merged_into_order_id');
    }

    /**
     * The inquiry this order was checked out / converted from, if any.
     */
    public function sourceInquiry(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'source_inquiry_id');
    }

    /**
     * The order that was created when this inquiry was checked out, if any.
     */
    public function convertedOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'source_inquiry_id');
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Generate next order number: ORD-YYYY-NNNNN
     */
    public static function generateOrderNumber(): string
    {
        // lockForUpdate() serialises concurrent inserts under InnoDB (gap lock on last row).
        // Must be called within an open DB::transaction.
        $year = now()->year;
        $last = static::withTrashed()
            ->where('order_number', 'like', "ORD-{$year}-%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        $nextNum = $last ? ((int) substr($last->order_number, -5)) + 1 : 1;
        return "ORD-{$year}-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if financials are locked (order marked as paid)
     */
    public function isFinanciallyLocked(): bool
    {
        return (bool) $this->is_paid;
    }

    /**
     * Whether goods on this order have actually shipped, meaning individual line
     * items can be marked as returned. Deliberately based on fulfilment status
     * rather than `type` — admins routinely track and fulfil real sales as
     * "inquiry" records without ever running them through online checkout.
     */
    public function isReturnEligible(): bool
    {
        return in_array($this->status, [self::STATUS_DISPATCHED, self::STATUS_DELIVERED], true);
    }

    /**
     * Check if a status transition is allowed
     */
    public function canTransitionTo(string $newStatus): bool
    {
        if ($this->status === $newStatus) {
            return false;
        }
        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Check if order can be cancelled
     */
    public function isCancellable(): bool
    {
        return !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED, self::STATUS_CONVERTED]);
    }

    /**
     * Check if order can be merged (not cancelled, delivered, or has issued invoice)
     */
    public function canBeMerged(): bool
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DELIVERED, self::STATUS_CONVERTED])) {
            return false;
        }
        if ($this->is_merged) {
            return false;
        }
        // Check for issued invoice
        if ($this->invoices()->where('status', 'issued')->exists()) {
            return false;
        }
        return true;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color class (Tailwind)
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Whether any line item on this order has an active return recorded against it.
     */
    public function hasReturnedItems(): bool
    {
        return $this->items->contains(fn (OrderItem $item) => $item->return_status !== OrderItem::RETURN_STATUS_NONE);
    }

    /**
     * Scope: only orders (not inquiries)
     */
    public function scopeOrders($query)
    {
        return $query->where('type', self::TYPE_ORDER);
    }

    /**
     * Scope: only inquiries
     */
    public function scopeInquiries($query)
    {
        return $query->where('type', self::TYPE_INQUIRY);
    }

    /**
     * Scope: filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if order has an active (non-voided) invoice
     */
    public function hasActiveInvoice(): bool
    {
        return $this->invoices()->whereIn('status', ['draft', 'issued'])->exists();
    }

    /**
     * Check if order has issued invoice
     */
    public function hasIssuedInvoice(): bool
    {
        return $this->invoices()->where('status', 'issued')->exists();
    }
    /**
     * Virtual attributes to proxy client_snapshot for frontend consistency
     * Falls back to the live client relation when no snapshot has been captured yet.
     */
    public function getNameAttribute()
    {
        return $this->client_snapshot['name'] ?? $this->client?->name;
    }
    public function getEmailAttribute()
    {
        return $this->client_snapshot['email'] ?? $this->client?->email;
    }
    public function getPhoneAttribute()
    {
        return $this->client_snapshot['phone'] ?? $this->client?->phone;
    }
    public function getCityAttribute()
    {
        return $this->client_snapshot['city'] ?? $this->client?->city;
    }
    public function getAddressLineAttribute()
    {
        return $this->client_snapshot['address'] ?? $this->client?->address_line;
    }
    public function getZipCodeAttribute()
    {
        return $this->client_snapshot['zip_code'] ?? $this->client?->zip_code;
    }
    public function getCountryAttribute()
    {
        return $this->client_snapshot['country'] ?? $this->client?->country;
    }

    /**
     * Shipping destination country as its full display name (customer-provided, via snapshot or live client)
     */
    public function getShippingCountryAttribute()
    {
        return country_name($this->getCountryAttribute());
    }

    /**
     * Full shipping address string (customer-provided, via snapshot or live client)
     */
    public function getShippingAddressAttribute()
    {
        return collect([
            $this->getAddressLineAttribute(),
            $this->getCityAttribute(),
            $this->client_snapshot['state'] ?? $this->client?->state,
            $this->getZipCodeAttribute(),
        ])->filter()->implode(', ');
    }

    /**
     * Name of the shipping provider selected/assigned for this order, if any
     */
    public function getShippingProviderNameAttribute(): ?string
    {
        return $this->shippingProvider?->name;
    }
}
