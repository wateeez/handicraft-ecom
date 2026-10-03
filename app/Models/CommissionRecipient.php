<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRecipient extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'payout_details', 'is_active'];
    protected $casts = ['payout_details' => 'array', 'is_active' => 'boolean'];

    public function entries(): HasMany { return $this->hasMany(CommissionEntry::class, 'recipient_id'); }
    public function payoutBatches(): HasMany { return $this->hasMany(CommissionPayoutBatch::class, 'recipient_id'); }
}
