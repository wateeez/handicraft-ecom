<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment made to a vendor, CSR or data entry payee (NPR). Append-only.
 */
class PayoutPayment extends Model
{
    protected $fillable = ['order_payable_id', 'amount', 'paid_on', 'method', 'reference', 'notes', 'recorded_by'];

    protected $casts = ['paid_on' => 'date'];

    public function payable(): BelongsTo { return $this->belongsTo(OrderPayable::class, 'order_payable_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
