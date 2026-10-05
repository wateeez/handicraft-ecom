<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'buyer_id',
        'name',
        'email',
        'phone',
        'company_name',
        'address_line',
        'city',
        'state',
        'zip_code',
        'country',
        'notes',
        'created_by',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function financeInvoices(): HasMany
    {
        return $this->hasMany(FinanceInvoice::class);
    }

    public function loyaltyAssignments(): HasMany
    {
        return $this->hasMany(ClientLoyaltyAssignment::class);
    }

    public function currentLoyaltyAssignment(): HasOne
    {
        return $this->hasOne(ClientLoyaltyAssignment::class)->whereNull('ended_at')->latestOfMany('effective_at');
    }

    /**
     * Generate the next buyer ID in format BYR-00001
     */
    public static function generateBuyerId(): string
    {
        $last = static::withTrashed()->orderByDesc('id')->first();
        $nextNum = $last ? ((int) substr($last->buyer_id, 4)) + 1 : 1;
        return 'BYR-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get full address as a single string
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country_name,
        ])->filter()->implode(', ');
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
