<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable record of one profit calculation. Corrections are made by
 * creating a new version, never by editing a row.
 */
class OrderFinanceSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = ['include_shipping_in_commission' => 'boolean'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Finance snapshots are immutable; create a new version instead.'));
        static::deleting(fn () => throw new LogicException('Finance snapshots cannot be deleted.'));
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class)->withTrashed(); }
    public function calculatedBy(): BelongsTo { return $this->belongsTo(User::class, 'calculated_by'); }
}
