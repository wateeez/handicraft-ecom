<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country's full display name instead of its ISO code
     */
    public function getCountryNameAttribute(): ?string
    {
        return $this->country
            ? config('countries.map.' . strtoupper($this->country), $this->country)
            : null;
    }
}
