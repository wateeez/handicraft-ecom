<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'invoice_id', 'client_id', 'recorded_by', 'confirmed_by', 'reversal_of_payment_id', 'amount', 'currency', 'reporting_amount', 'reporting_exchange_rate', 'method', 'reference', 'received_on', 'status', 'confirmed_at', 'notes'];
    protected $casts = ['amount' => 'decimal:2', 'reporting_amount' => 'decimal:2', 'reporting_exchange_rate' => 'decimal:8', 'received_on' => 'date', 'confirmed_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function confirmedBy(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function reversalOf(): BelongsTo { return $this->belongsTo(self::class, 'reversal_of_payment_id'); }
    public function reversals(): HasMany { return $this->hasMany(self::class, 'reversal_of_payment_id'); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class); }
}
