<?php

namespace App\Services\Finance;

use App\Contracts\ForexRateProvider;

/**
 * Default provider: the admin types the rate; the last confirmed one is the default.
 */
class ManualForexRateProvider implements ForexRateProvider
{
    public function __construct(private readonly FinanceRateRepository $rates)
    {
    }

    public function currentRate(): ?string
    {
        return $this->rates->get(FinanceRateRepository::LAST_FOREX_KEY);
    }

    public function refresh(): ?string
    {
        return $this->currentRate();
    }

    public function remember(string $rate): void
    {
        $this->rates->set(FinanceRateRepository::LAST_FOREX_KEY, $rate);
    }
}
