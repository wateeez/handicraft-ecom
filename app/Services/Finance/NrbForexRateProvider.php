<?php

namespace App\Services\Finance;

use App\Contracts\ForexRateProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fetches the official Nepal Rastra Bank USD sell rate (NPR per USD, cached),
 * falling back to the last rate an admin confirmed if NRB is unreachable.
 * NRB publishes once a day and skips weekends/holidays, so the latest day with a USD rate in the past week is used.
 */
class NrbForexRateProvider implements ForexRateProvider
{
    private const CACHE_KEY = 'forex_rate_usd_npr_nrb';

    public function __construct(private readonly FinanceRateRepository $rates)
    {
    }

    public function currentRate(): ?string
    {
        $live = Cache::remember(self::CACHE_KEY, (int) config('services.forex.cache_seconds', 3600), fn () => $this->fetch());

        return $live ?? $this->rates->get(FinanceRateRepository::LAST_FOREX_KEY);
    }

    public function refresh(): ?string
    {
        Cache::forget(self::CACHE_KEY);

        return $this->currentRate();
    }

    public function remember(string $rate): void
    {
        $this->rates->set(FinanceRateRepository::LAST_FOREX_KEY, $rate);
    }

    private function fetch(): ?string
    {
        try {
            $response = Http::timeout(10)->get(config('services.forex.nrb_url'), [
                'from' => now()->subDays(7)->toDateString(),
                'to' => now()->toDateString(),
                'per_page' => 100,
                'page' => 1,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $days = collect($response->json('data.payload') ?? [])->sortByDesc('date');

            foreach ($days as $day) {
                $usd = collect($day['rates'] ?? [])->firstWhere('currency.iso3', 'USD');
                $unit = (float) ($usd['currency']['unit'] ?? 1) ?: 1.0;
                $sell = $usd['sell'] ?? null;

                if (is_numeric($sell) && $sell > 0) {
                    return number_format((float) $sell / $unit, 4, '.', '');
                }
            }

            return null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
