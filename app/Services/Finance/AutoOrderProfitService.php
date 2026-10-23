<?php

namespace App\Services\Finance;

use App\Contracts\ForexRateProvider;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;

/**
 * Runs the first profit calculation for an order that was settled automatically
 * (an online PayPal capture), so nobody has to open each one. Anything it cannot
 * work out safely is left for an admin, who still sees it under "Needs attention".
 */
class AutoOrderProfitService
{
    private const DEFAULT_VENDOR_NAME = 'HME';

    public function __construct(
        private readonly OrderFinanceService $profit,
        private readonly ForexRateProvider $forex,
    ) {
    }

    public function calculateIfNeeded(Order $order): ?OrderFinanceSnapshot
    {
        if (OrderFinanceSnapshot::where('order_id', $order->id)->exists()) {
            return null;
        }

        $vendor = $this->resolveVendor($order);
        $rate = $this->forex->currentRate();

        if (!$vendor || !$rate) {
            Log::info('Auto profit skipped: needs a manual calculation.', [
                'order_id' => $order->id,
                'vendor_found' => (bool) $vendor,
                'forex_rate_found' => (bool) $rate,
            ]);

            return null;
        }

        // Same starting values the manual form offers. The real shipping cost is
        // not known yet, so it stays at 0 until an admin recalculates with it.
        return $this->profit->calculateAndStore($order, [
            'vendor_id' => $vendor->id,
            'product_price_usd' => bcsub((string) $order->subtotal, (string) $order->order_discount_amount, 2),
            'shipping_usd' => (string) $order->shipping_cost,
            'forex_rate' => $rate,
            'actual_shipping_cost_npr' => '0',
        ], null);
    }

    /**
     * The vendor all of the order's products belong to, else the order's own
     * vendor, else the default vendor (or the only active one).
     */
    private function resolveVendor(Order $order): ?Vendor
    {
        $productVendorIds = $order->items()->with('product:id,vendor_id')->get()
            ->map(fn ($item) => $item->product?->vendor_id)
            ->filter()
            ->unique();

        $ids = $productVendorIds->count() === 1 ? $productVendorIds : collect([$order->vendor_id])->filter();

        if ($ids->isNotEmpty() && ($vendor = Vendor::active()->find($ids->first()))) {
            return $vendor;
        }

        $active = Vendor::active()->get();

        return $active->firstWhere('name', self::DEFAULT_VENDOR_NAME)
            ?? ($active->count() === 1 ? $active->first() : null);
    }
}
