<?php

namespace App\Services\Finance;

/**
 * Calculation output. Every monetary value is a string rounded to 2 decimals.
 */
final class OrderFinanceResult
{
    public function __construct(
        public readonly string $sellingPriceUsd,
        public readonly string $gatewayFeeUsd,
        public readonly string $receivedUsd,
        public readonly string $receivedNpr,
        public readonly string $mrpNpr,
        public readonly string $costPriceNpr,
        public readonly string $vendorCommissionNpr,
        public readonly string $vendorTotalNpr,
        public readonly string $balanceAfterVendorNpr,
        public readonly string $commissionBaseUsd,
        public readonly string $csrUsd,
        public readonly string $csrNpr,
        public readonly string $dataEntryUsd,
        public readonly string $dataEntryRateUsed,
        public readonly string $dataEntryNpr,
        public readonly string $leftoverNpr,
        public readonly string $netProfitNpr,
    ) {
    }

    /**
     * Keyed by the snapshot column names.
     */
    public function toArray(): array
    {
        return [
            'selling_price_usd' => $this->sellingPriceUsd,
            'gateway_fee_usd' => $this->gatewayFeeUsd,
            'received_usd' => $this->receivedUsd,
            'received_npr' => $this->receivedNpr,
            'mrp_npr' => $this->mrpNpr,
            'cost_price_npr' => $this->costPriceNpr,
            'vendor_commission_npr' => $this->vendorCommissionNpr,
            'vendor_total_npr' => $this->vendorTotalNpr,
            'balance_after_vendor_npr' => $this->balanceAfterVendorNpr,
            'commission_base_usd' => $this->commissionBaseUsd,
            'csr_usd' => $this->csrUsd,
            'csr_npr' => $this->csrNpr,
            'data_entry_usd' => $this->dataEntryUsd,
            'data_entry_rate_used' => $this->dataEntryRateUsed,
            'data_entry_npr' => $this->dataEntryNpr,
            'leftover_npr' => $this->leftoverNpr,
            'net_profit_npr' => $this->netProfitNpr,
        ];
    }
}
