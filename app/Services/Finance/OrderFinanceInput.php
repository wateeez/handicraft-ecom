<?php

namespace App\Services\Finance;

/**
 * Everything the profit calculation needs. Money and rates are decimal strings
 * (never floats); percentages are in percent units, e.g. "14" means 14%.
 */
final class OrderFinanceInput
{
    public function __construct(
        public readonly string $productPriceUsd,
        public readonly string $shippingUsd,
        public readonly string $forexRate,
        public readonly string $vendorCostPricePct,
        public readonly string $vendorCommissionPct,
        public readonly string $gatewayFeePct,
        public readonly string $csrPct,
        public readonly string $dataEntryPct,
        public readonly string $deFixedRate,
        public readonly string $deThresholdRate,
        public readonly bool $includeShippingInCommission,
        public readonly string $actualShippingCostNpr = '0',
    ) {
    }
}
