<?php

namespace Tests\Unit;

use App\Services\Finance\NrbForexRateProvider;
use App\Services\Finance\FinanceRateRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NrbForexRateProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function provider(?string $lastConfirmed = null): NrbForexRateProvider
    {
        $rates = \Mockery::mock(FinanceRateRepository::class);
        $rates->shouldReceive('get')->andReturn($lastConfirmed);

        return new NrbForexRateProvider($rates);
    }

    private function day(string $date, array $rates): array
    {
        return ['date' => $date, 'rates' => $rates];
    }

    private function rate(string $iso3, string $sell, int $unit = 1): array
    {
        return ['currency' => ['iso3' => $iso3, 'unit' => $unit], 'buy' => $sell, 'sell' => $sell];
    }

    public function test_uses_latest_day_usd_sell_rate(): void
    {
        Http::fake(['*' => Http::response(['data' => ['payload' => [
            $this->day('2026-10-16', [$this->rate('USD', '150.00')]),
            $this->day('2026-10-18', [$this->rate('JPY', '9.5', 10), $this->rate('USD', '152.4567')]),
        ]]])]);

        $this->assertSame('152.4567', $this->provider()->currentRate());
    }

    public function test_falls_back_to_last_confirmed_rate_when_nrb_fails(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->assertSame('151.0000', $this->provider('151.0000')->currentRate());
    }
}
