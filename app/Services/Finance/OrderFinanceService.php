<?php

namespace App\Services\Finance;

use App\Contracts\ForexRateProvider;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

/**
 * Persistence side of the profit feature: gathers inputs, runs the pure
 * calculator and stores an immutable snapshot version.
 */
class OrderFinanceService
{
    public function __construct(
        private readonly OrderFinanceCalculator $calculator,
        private readonly FinanceRateRepository $rates,
        private readonly ForexRateProvider $forex,
        private readonly OrderPayoutService $payouts,
    ) {
    }

    /**
     * Calculate with the rates and vendor terms in force right now and store
     * the result as the next snapshot version. Used for both the first
     * calculation and "Recalculate".
     *
     * @param array{vendor_id:int,product_price_usd:string,shipping_usd:string,forex_rate:string,actual_shipping_cost_npr?:?string} $data
     */
    public function calculateAndStore(Order $order, array $data, ?User $user): OrderFinanceSnapshot
    {
        $vendor = Vendor::active()->findOrFail($data['vendor_id']);
        $input = $this->buildInput($vendor, $data);
        $result = $this->calculator->calculate($input);

        return DB::transaction(function () use ($order, $vendor, $data, $input, $result, $user) {
            // Serialize concurrent calculations so versions stay sequential
            Order::whereKey($order->id)->lockForUpdate()->first();

            $version = (int) OrderFinanceSnapshot::where('order_id', $order->id)->max('version') + 1;
            $invoiceId = Invoice::where('order_id', $order->id)->where('status', '!=', 'voided')->latest('id')->value('id');

            $snapshot = OrderFinanceSnapshot::create([
                'order_id' => $order->id,
                'invoice_id' => $invoiceId,
                'vendor_id' => $vendor->id,
                'calculated_by' => $user?->id,
                'version' => $version,
                'vendor_name' => $vendor->name,
                'product_price_usd' => $input->productPriceUsd,
                'shipping_usd' => $input->shippingUsd,
                'forex_rate' => $input->forexRate,
                'actual_shipping_cost_npr' => $input->actualShippingCostNpr,
                'vendor_cost_price_pct' => $input->vendorCostPricePct,
                'vendor_commission_pct' => $input->vendorCommissionPct,
                'gateway_fee_pct' => $input->gatewayFeePct,
                'csr_pct' => $input->csrPct,
                'data_entry_pct' => $input->dataEntryPct,
                'de_fixed_rate' => $input->deFixedRate,
                'de_threshold_rate' => $input->deThresholdRate,
                'include_shipping_in_commission' => $input->includeShippingInCommission,
            ] + $result->toArray());

            $order->forceFill(['vendor_id' => $vendor->id])->saveQuietly();
            $this->forex->remember($input->forexRate);

            // A paid order owes its vendor, CSR and data entry the (re)calculated amounts
            $this->payouts->sync($order);

            return $snapshot;
        });
    }

    /**
     * Pure-math preview of a stored snapshot's inputs, used to prove history
     * is independent of today's settings.
     */
    public function inputFromSnapshot(OrderFinanceSnapshot $s): OrderFinanceInput
    {
        return new OrderFinanceInput(
            productPriceUsd: (string) $s->product_price_usd,
            shippingUsd: (string) $s->shipping_usd,
            forexRate: (string) $s->forex_rate,
            vendorCostPricePct: (string) $s->vendor_cost_price_pct,
            vendorCommissionPct: (string) $s->vendor_commission_pct,
            gatewayFeePct: (string) $s->gateway_fee_pct,
            csrPct: (string) $s->csr_pct,
            dataEntryPct: (string) $s->data_entry_pct,
            deFixedRate: (string) $s->de_fixed_rate,
            deThresholdRate: (string) $s->de_threshold_rate,
            includeShippingInCommission: (bool) $s->include_shipping_in_commission,
            actualShippingCostNpr: (string) $s->actual_shipping_cost_npr,
        );
    }

    /**
     * Form defaults: the latest snapshot's inputs, else the order's own amounts.
     */
    public function formDefaults(Order $order, ?OrderFinanceSnapshot $latest): array
    {
        if ($latest) {
            return [
                'vendor_id' => $latest->vendor_id ?? $order->vendor_id,
                'product_price_usd' => (string) $latest->product_price_usd,
                'shipping_usd' => (string) $latest->shipping_usd,
                'forex_rate' => (string) $latest->forex_rate,
                'actual_shipping_cost_npr' => (string) $latest->actual_shipping_cost_npr,
            ];
        }

        return [
            'vendor_id' => $order->vendor_id,
            'product_price_usd' => bcsub((string) $order->subtotal, (string) $order->order_discount_amount, 2),
            'shipping_usd' => (string) $order->shipping_cost,
            'forex_rate' => $this->forex->currentRate() ?? '',
            'actual_shipping_cost_npr' => '0',
        ];
    }

    private function buildInput(Vendor $vendor, array $data): OrderFinanceInput
    {
        $rates = $this->rates->all();

        return new OrderFinanceInput(
            productPriceUsd: (string) $data['product_price_usd'],
            shippingUsd: (string) $data['shipping_usd'],
            forexRate: (string) $data['forex_rate'],
            vendorCostPricePct: (string) $vendor->cost_price_pct,
            vendorCommissionPct: (string) $vendor->commission_pct,
            gatewayFeePct: $rates['gateway_fee_pct'],
            csrPct: $rates['csr_pct'],
            dataEntryPct: $rates['data_entry_pct'],
            deFixedRate: $rates['de_fixed_rate'],
            deThresholdRate: $rates['de_threshold_rate'],
            includeShippingInCommission: (bool) (int) $rates['include_shipping_in_commission'],
            actualShippingCostNpr: (string) (($data['actual_shipping_cost_npr'] ?? null) === null || $data['actual_shipping_cost_npr'] === '' ? '0' : $data['actual_shipping_cost_npr']),
        );
    }
}
