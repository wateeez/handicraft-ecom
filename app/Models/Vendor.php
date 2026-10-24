<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'address', 'cost_price_pct', 'commission_pct', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(OrderPayable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
