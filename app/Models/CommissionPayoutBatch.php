<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPayoutBatch extends Model
{
    use SoftDeletes;

    protected $fillable = ['recipient_id', 'recorded_by', 'approved_by', 'paid_on', 'currency', 'amount', 'method', 'reference', 'status'];
    protected $casts = ['paid_on' => 'date', 'amount' => 'decimal:2'];

    public function recipient(): BelongsTo { return $this->belongsTo(CommissionRecipient::class, 'recipient_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(CommissionPayoutItem::class); }
}
