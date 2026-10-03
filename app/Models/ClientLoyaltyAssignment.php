<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLoyaltyAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = ['client_id', 'loyalty_tier_id', 'assigned_by', 'source', 'reason', 'effective_at', 'ended_at'];
    protected $casts = ['effective_at' => 'datetime', 'ended_at' => 'datetime'];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function loyaltyTier(): BelongsTo { return $this->belongsTo(LoyaltyTier::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
