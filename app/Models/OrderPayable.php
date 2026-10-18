<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What we owe one payee (vendor, CSR or data entry) for a paid order, in NPR.
 */
class OrderPayable extends Model
{
    public const TYPE_VENDOR = 'vendor';
    public const TYPE_CSR = 'csr';
    public const TYPE_DATA_ENTRY = 'data_entry';

    public const TYPES = [
        self::TYPE_VENDOR => 'Vendor',
        self::TYPE_CSR => 'CSR',
        self::TYPE_DATA_ENTRY => 'Data entry',
    ];

    protected $fillable = ['order_id', 'payee_type', 'vendor_id', 'amount', 'paid_amount', 'status'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class)->withTrashed(); }
    public function payments(): HasMany { return $this->hasMany(PayoutPayment::class)->latest('paid_on')->latest('id'); }

    public function getLabelAttribute(): string
    {
        return self::TYPES[$this->payee_type] ?? $this->payee_type;
    }

    public function getBalanceAttribute(): string
    {
        return bcsub((string) $this->amount, (string) $this->paid_amount, 2);
    }
}
