<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialLedgerEntry extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['order_id', 'client_id', 'source_type', 'source_id', 'entry_type', 'currency', 'amount', 'reporting_amount', 'reporting_exchange_rate', 'metadata', 'occurred_at'];
    protected $casts = ['amount' => 'decimal:2', 'reporting_amount' => 'decimal:2', 'reporting_exchange_rate' => 'decimal:8', 'metadata' => 'array', 'occurred_at' => 'datetime', 'created_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function source(): MorphTo { return $this->morphTo(); }
}
