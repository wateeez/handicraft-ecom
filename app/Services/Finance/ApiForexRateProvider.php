<?php

namespace App\Services\Finance;

use App\Contracts\ForexRateProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fetches the live NPR-per-USD rate from an exchange-rate API (cached briefly),
 * falling back to the last rate an admin confirmed if the API is unreachable.
 */
class ApiForexRateProvider implements ForexRateProvider
{
    private const CACHE_KEY = 'forex_rate_usd_npr';

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
            $key = config('services.forex.key');
            $url = $key
                ? str_replace('{key}', $key, config('services.forex.url'))
                : config('services.forex.open_url');

            $response = Http::timeout(5)->get($url);
            $rate = $response->json('conversion_rates.NPR') ?? $response->json('rates.NPR');

            return $response->successful() && is_numeric($rate) && $rate > 0
                ? number_format((float) $rate, 4, '.', '')
                : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
