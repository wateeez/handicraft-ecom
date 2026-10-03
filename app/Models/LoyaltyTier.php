<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $fillable = ['name', 'rank', 'qualification_basis', 'threshold_amount', 'threshold_order_count', 'suggested_discount_percent', 'is_vip', 'is_active'];
    protected $casts = ['rank' => 'integer', 'threshold_amount' => 'decimal:2', 'threshold_order_count' => 'integer', 'suggested_discount_percent' => 'decimal:2', 'is_vip' => 'boolean', 'is_active' => 'boolean'];

    public function assignments(): HasMany { return $this->hasMany(ClientLoyaltyAssignment::class); }
}
