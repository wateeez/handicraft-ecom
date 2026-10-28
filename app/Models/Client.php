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
     * Normalise a country name or alias ("Nepal", "uk") to the ISO code used for shipping.
     */
    public static function normalizeCountry(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $map = config('countries.map', []);
        $upper = strtoupper($value);
        $aliases = ['UK' => 'GB', 'UAE' => 'AE', 'USA' => 'US', 'KSA' => 'SA'];

        if (isset($aliases[$upper])) {
            return $aliases[$upper];
        }
        if (isset($map[$upper])) {
            return $upper;
        }
        foreach ($map as $code => $name) {
            if (strcasecmp($name, $value) === 0) {
                return $code;
            }
        }

        return $value;
    }

    /**
     * Fill only the profile fields that are still empty, so repeat inquiries
     * complete a client's details without overwriting what we already hold.
     */
    public function fillMissing(array $data): self
    {
        if (array_key_exists('country', $data)) {
            $data['country'] = static::normalizeCountry($data['country']);
        }

        $missing = collect($data)
            ->only(['name', 'phone', 'company_name', 'address_line', 'city', 'state', 'zip_code', 'country'])
            ->filter(fn ($v, $k) => filled($v) && blank($this->{$k}))
            ->all();

        if ($missing) {
            $this->fill($missing)->save();
        }

        return $this;
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
