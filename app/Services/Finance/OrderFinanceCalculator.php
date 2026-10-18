<?php

namespace App\Services\Finance;

/**
 * Pure order profit & commission math: no database, no config, no clock.
 *
 * Rounding rule: all arithmetic uses bcmath at SCALE decimals (truncating, so
 * no hidden float error). Each monetary line is rounded half-up (away from
 * zero) to 2 decimals the moment it becomes a result line, and later lines are
 * derived from those rounded values, so the printed breakdown always adds up
 * to the cent. Rates (forex, data entry rate) are kept unrounded.
 */
class OrderFinanceCalculator
{
    private const SCALE = 10;

    public function calculate(OrderFinanceInput $in): OrderFinanceResult
    {
        // 1-3: USD side
        $sellingPriceUsd = $this->money(bcadd($in->productPriceUsd, $in->shippingUsd, self::SCALE));
        $gatewayFeeUsd = $this->money(bcmul($sellingPriceUsd, $this->fraction($in->gatewayFeePct), self::SCALE));
        $receivedUsd = bcsub($sellingPriceUsd, $gatewayFeeUsd, 2);

        // 4: converted at the real-time rate
        $receivedNpr = $this->money(bcmul($receivedUsd, $in->forexRate, self::SCALE));

        // 5: vendor cost; commission applies on cost price only, not MRP
        $mrpNpr = $this->money(bcmul($in->productPriceUsd, $in->forexRate, self::SCALE));
        $costPriceNpr = $this->money(bcmul($mrpNpr, $this->fraction($in->vendorCostPricePct), self::SCALE));
        $vendorCommissionNpr = $this->money(bcmul($costPriceNpr, $this->fraction($in->vendorCommissionPct), self::SCALE));
        $vendorTotalNpr = bcadd($costPriceNpr, $vendorCommissionNpr, 2);

        // 6
        $balanceAfterVendorNpr = bcsub($receivedNpr, $vendorTotalNpr, 2);

        // 7: commission base
        $commissionBaseUsd = $in->includeShippingInCommission
            ? $this->money(bcadd($in->productPriceUsd, $in->shippingUsd, self::SCALE))
            : $this->money($in->productPriceUsd);

        // 8: CSR at the real-time rate
        $csrUsd = $this->money(bcmul($commissionBaseUsd, $this->fraction($in->csrPct), self::SCALE));
        $csrNpr = $this->money(bcmul($csrUsd, $in->forexRate, self::SCALE));

        // 9: data entry uses the real rate at/above the threshold, else the fixed rate
        $dataEntryUsd = $this->money(bcmul($commissionBaseUsd, $this->fraction($in->dataEntryPct), self::SCALE));
        $dataEntryRateUsed = bccomp($in->forexRate, $in->deThresholdRate, self::SCALE) >= 0
            ? $in->forexRate
            : $in->deFixedRate;
        $dataEntryNpr = $this->money(bcmul($dataEntryUsd, $dataEntryRateUsed, self::SCALE));

        // 10-11
        $leftoverNpr = bcsub(bcsub($balanceAfterVendorNpr, $csrNpr, 2), $dataEntryNpr, 2);
        $netProfitNpr = bcsub($leftoverNpr, $this->money($in->actualShippingCostNpr), 2);

        return new OrderFinanceResult(
            sellingPriceUsd: $sellingPriceUsd,
            gatewayFeeUsd: $gatewayFeeUsd,
            receivedUsd: $receivedUsd,
            receivedNpr: $receivedNpr,
            mrpNpr: $mrpNpr,
            costPriceNpr: $costPriceNpr,
            vendorCommissionNpr: $vendorCommissionNpr,
            vendorTotalNpr: $vendorTotalNpr,
            balanceAfterVendorNpr: $balanceAfterVendorNpr,
            commissionBaseUsd: $commissionBaseUsd,
            csrUsd: $csrUsd,
            csrNpr: $csrNpr,
            dataEntryUsd: $dataEntryUsd,
            dataEntryRateUsed: $dataEntryRateUsed,
            dataEntryNpr: $dataEntryNpr,
            leftoverNpr: $leftoverNpr,
            netProfitNpr: $netProfitNpr,
        );
    }

    /**
     * Percent to fraction: "14" => "0.14".
     */
    private function fraction(string $percent): string
    {
        return bcdiv($percent === '' ? '0' : $percent, '100', self::SCALE);
    }

    /**
     * Round half-up (away from zero) to 2 decimals.
     */
    private function money(string $value): string
    {
        $value = $value === '' ? '0' : $value;
        $half = bccomp($value, '0', self::SCALE) < 0 ? '-0.005' : '0.005';

        return bcadd($value, $half, 2);
    }
}
