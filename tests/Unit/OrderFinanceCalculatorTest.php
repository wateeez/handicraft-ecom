<?php

namespace Tests\Unit;

use App\Services\Finance\OrderFinanceCalculator;
use App\Services\Finance\OrderFinanceInput;
use PHPUnit\Framework\TestCase;

class OrderFinanceCalculatorTest extends TestCase
{
    private function input(array $overrides = []): OrderFinanceInput
    {
        $d = array_merge([
            'productPriceUsd' => '100',
            'shippingUsd' => '65',
            'forexRate' => '154',
            'vendorCostPricePct' => '50',
            'vendorCommissionPct' => '20',
            'gatewayFeePct' => '14',
            'csrPct' => '4',
            'dataEntryPct' => '10',
            'deFixedRate' => '150',
            'deThresholdRate' => '200',
            'includeShippingInCommission' => true,
        ], $overrides);

        return new OrderFinanceInput(...$d);
    }

    private function calc(array $overrides = []): array
    {
        return (new OrderFinanceCalculator())->calculate($this->input($overrides))->toArray();
    }

    public function test_reference_example(): void
    {
        $r = $this->calc();

        $this->assertSame('165.00', $r['selling_price_usd']);
        $this->assertSame('23.10', $r['gateway_fee_usd']);
        $this->assertSame('141.90', $r['received_usd']);
        $this->assertSame('21852.60', $r['received_npr']);
        $this->assertSame('15400.00', $r['mrp_npr']);
        $this->assertSame('7700.00', $r['cost_price_npr']);
        $this->assertSame('1540.00', $r['vendor_commission_npr']);
        $this->assertSame('9240.00', $r['vendor_total_npr']);
        $this->assertSame('12612.60', $r['balance_after_vendor_npr']);
        $this->assertSame('165.00', $r['commission_base_usd']);
        $this->assertSame('1016.40', $r['csr_npr']);
        $this->assertSame('2475.00', $r['data_entry_npr']);
        $this->assertSame('150', $r['data_entry_rate_used']);
        $this->assertSame('9121.20', $r['leftover_npr']);
        $this->assertSame('9121.20', $r['net_profit_npr'], 'no actual shipping cost means net equals leftover');
    }

    public function test_forex_at_threshold_uses_the_real_rate_for_data_entry(): void
    {
        $r = $this->calc(['forexRate' => '200']);

        $this->assertSame('200', $r['data_entry_rate_used']);
        $this->assertSame('3300.00', $r['data_entry_npr']); // 16.50 x 200
        $this->assertSame('1320.00', $r['csr_npr']);
        $this->assertSame('16380.00', $r['balance_after_vendor_npr']);
        $this->assertSame('11760.00', $r['leftover_npr']);
    }

    public function test_forex_just_below_threshold_uses_the_fixed_rate(): void
    {
        $r = $this->calc(['forexRate' => '199.9999']);

        $this->assertSame('150', $r['data_entry_rate_used']);
        $this->assertSame('2475.00', $r['data_entry_npr']);
    }

    public function test_commission_base_excludes_shipping_when_setting_is_off(): void
    {
        $r = $this->calc(['includeShippingInCommission' => false]);

        $this->assertSame('100.00', $r['commission_base_usd']);
        $this->assertSame('616.00', $r['csr_npr']);          // 4.00 x 154
        $this->assertSame('1500.00', $r['data_entry_npr']);  // 10.00 x 150
        $this->assertSame('10496.60', $r['leftover_npr']);   // 12612.60 - 616 - 1500
    }

    public function test_actual_shipping_cost_reduces_net_profit(): void
    {
        $r = $this->calc(['actualShippingCostNpr' => '1000']);

        $this->assertSame('9121.20', $r['leftover_npr']);
        $this->assertSame('8121.20', $r['net_profit_npr']);
    }

    public function test_zero_or_empty_actual_shipping_cost_is_treated_as_zero(): void
    {
        $this->assertSame('9121.20', $this->calc(['actualShippingCostNpr' => '0'])['net_profit_npr']);
        $this->assertSame('9121.20', $this->calc(['actualShippingCostNpr' => '0.00'])['net_profit_npr']);
        $this->assertSame('9121.20', $this->calc(['actualShippingCostNpr' => ''])['net_profit_npr']);
    }

    public function test_net_profit_can_be_negative(): void
    {
        $r = $this->calc(['actualShippingCostNpr' => '10000']);

        $this->assertSame('-878.80', $r['net_profit_npr']);
    }

    public function test_rounding_is_half_up_and_uses_no_floats(): void
    {
        // 0.10 + 0.20 would be 0.30000000000000004 as floats; 33.335 x 14% = 4.6669 -> 4.67
        $r = $this->calc(['productPriceUsd' => '33.335', 'shippingUsd' => '0', 'forexRate' => '100']);

        $this->assertSame('33.34', $r['selling_price_usd']); // 33.335 rounds half-up
        $this->assertSame('4.67', $r['gateway_fee_usd']);
        $this->assertSame('28.67', $r['received_usd']);
    }
}
