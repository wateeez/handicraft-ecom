<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPayoutItem extends Model
{
    protected $fillable = ['commission_payout_batch_id', 'commission_entry_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function batch(): BelongsTo { return $this->belongsTo(CommissionPayoutBatch::class, 'commission_payout_batch_id'); }
    public function commissionEntry(): BelongsTo { return $this->belongsTo(CommissionEntry::class); }
}
