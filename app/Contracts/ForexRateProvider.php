<?php

namespace App\Contracts;

interface ForexRateProvider
{
    /**
     * Current NPR-per-USD rate as a decimal string, or null when none is known.
     * Swap the bound implementation to plug in an automatic rate API.
     */
    public function currentRate(): ?string;

    /**
     * Drop any cached rate and fetch the latest one (null when none is known).
     */
    public function refresh(): ?string;

    /**
     * Remember the rate an admin confirmed so it becomes the next default.
     */
    public function remember(string $rate): void;
}
