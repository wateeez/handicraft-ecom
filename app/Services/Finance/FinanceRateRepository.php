<?php

namespace App\Services\Finance;

use App\Models\FinanceRateSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Cached key/value store for the profit & commission rates.
 * Percentages are in percent units ("14" = 14%).
 */
class FinanceRateRepository
{
    public const CACHE_KEY = 'finance_rate_settings';

    public const DEFAULTS = [
        'gateway_fee_pct' => '14',
        'csr_pct' => '4',
        'data_entry_pct' => '10',
        'de_fixed_rate' => '150',
        'de_threshold_rate' => '200',
        'include_shipping_in_commission' => '1',
    ];

    /** Remembers the last forex rate an admin confirmed; not an editable rate. */
    public const LAST_FOREX_KEY = 'last_forex_rate';

    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => FinanceRateSetting::pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    public function includeShippingInCommission(): bool
    {
        return (bool) (int) $this->get('include_shipping_in_commission');
    }

    public function set(string $key, string $value): void
    {
        FinanceRateSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            FinanceRateSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
