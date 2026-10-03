<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributorPayoutItem extends Model
{
    protected $fillable = ['distributor_payout_batch_id', 'distributor_payable_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function batch(): BelongsTo { return $this->belongsTo(DistributorPayoutBatch::class, 'distributor_payout_batch_id'); }
    public function payable(): BelongsTo { return $this->belongsTo(DistributorPayable::class, 'distributor_payable_id'); }
}
