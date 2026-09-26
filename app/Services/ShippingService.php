<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\ShippingRate;

class ShippingService
{
    /**
     * Calculate shipping rates based on items and destination country.
     *
     * @param array $items Array of ['product' => Product, 'quantity' => int]
     * @param string $country Country code or name
     * @return array
     */
    public function calculateShipping(array $items, string $country)
    {
        $zone = $this->resolveZone($country);

        if (!$zone) {
            return []; // No shipping zone found for this country
        }

        $weights = $this->computeWeights($items);
        $chargeableWeight = $weights['chargeable'];

        // Find Rates: search for rates in the found zone where chargeable weight falls within min/max
        $rates = $zone->rates()
            ->with('provider')
            ->where('min_weight', '<=', $chargeableWeight)
            ->where('max_weight', '>=', $chargeableWeight)
            ->get();

        $results = [];
        foreach ($rates as $rate) {
            if ($rate->provider && $rate->provider->is_active) {
                $results[] = [
                    'provider_name' => $rate->provider->name,
                    'price' => $rate->price,
                    'currency' => '$', // Assumption
                    'details' => [
                        'actual_weight' => round($weights['actual'], 3),
                        'volumetric_weight' => round($weights['volumetric'], 3),
                        'chargeable_weight' => round($chargeableWeight, 3),
                        'zone' => $zone->name,
                    ]
                ];
            }
        }

        return $results;
    }

    /**
     * Determine whether the chargeable weight for the given items exceeds the
     * heaviest admin-configured shipping tier (max_weight) for the destination zone.
     *
     * @param array $items Array of ['product' => Product, 'quantity' => int]
     * @param string $country Country code or name
     * @return array{over_weight: bool, chargeable_weight: ?float, weight_limit: ?float}
     */
    public function checkOverWeight(array $items, string $country): array
    {
        $zone = $this->resolveZone($country);

        if (!$zone) {
            return ['over_weight' => false, 'chargeable_weight' => null, 'weight_limit' => null];
        }

        $chargeableWeight = round($this->computeWeights($items)['chargeable'], 3);

        // Admin-configured limit = heaviest tier configured for this zone across all rates.
        $weightLimit = $zone->rates()->max('max_weight');
        $weightLimit = $weightLimit !== null ? (float) $weightLimit : null;

        return [
            'over_weight' => $weightLimit !== null && $chargeableWeight > $weightLimit,
            'chargeable_weight' => $chargeableWeight,
            'weight_limit' => $weightLimit,
        ];
    }

    /**
     * Resolve the shipping zone containing the given country.
     * We assume 'countries' value in DB is JSON array of strings (codes or names).
     * Try direct match; if not found, try code→name and name→code fallbacks using config('countries.map').
     */
    private function resolveZone(string $country): ?ShippingZone
    {
        $zone = ShippingZone::whereJsonContains('countries', $country)->first();

        if ($zone) {
            return $zone;
        }

        $map = config('countries.map', []);
        $upper = strtoupper(trim($country));

        // If provided as code, try name
        if (isset($map[$upper])) {
            $name = $map[$upper];
            $zone = ShippingZone::whereJsonContains('countries', $name)->first();
        }

        // If provided as name, try code (inverse lookup)
        if (!$zone) {
            $inverseCode = null;
            foreach ($map as $code => $label) {
                if (strcasecmp(trim($country), $label) === 0) {
                    $inverseCode = $code;
                    break;
                }
            }
            if ($inverseCode) {
                $zone = ShippingZone::whereJsonContains('countries', $inverseCode)->first();
            }
        }

        return $zone;
    }

    /**
     * Compute actual, volumetric and chargeable weight for a set of cart/order items.
     *
     * @param array $items Array of ['product' => Product, 'quantity' => int]
     * @return array{actual: float, volumetric: float, chargeable: float}
     */
    private function computeWeights(array $items): array
    {
        $totalActualWeight = 0;
        // We'll accumulate total volume to divide later
        $totalVolume = 0;

        foreach ($items as $item) {
            $product = $item['product'];
            $qty = $item['quantity'];

            // Actual Weight
            $totalActualWeight += ($product->weight * $qty);

            // Dimensions logic as per README:
            // "Actual dimension of breath is multiplied by the number of quantity"
            // Only contribute volumetric weight when ALL three dimensions are present and non-zero.
            // A product with any missing dimension falls back to actual weight only (no volumetric addition).
            $l = $product->length ?? 0;
            $w = $product->width  ?? 0;
            $h = $product->height ?? 0;

            if ($l > 0 && $w > 0 && $h > 0) {
                $w_packed = ($w * $qty) + 4; // Width * Qty, then add packing buffer
                $l_packed = $l + 4;
                $h_packed = $h + 4;

                // "Add 4 cm to each side L, B, H from actual dimension for packing material / boxes(buffer)"
                $volume = $l_packed * $w_packed * $h_packed;
                $totalVolume += $volume;
            }
            // If dimensions are missing, volumetric contribution is zero for this item.
        }

        // Divisor logic
        // "if weight is more than 500kg volumetric calculation is measured by LxBxH/6000"
        // We use Total Actual Weight to decide the divisor.
        $divisor = ($totalActualWeight > 500) ? 6000 : 5000;

        $totalVolumetricWeight = $totalVolume / $divisor;

        // "To apply shipping charges greater value of actual weight and volumetric weight; whichever is greater"
        $chargeableWeight = max($totalActualWeight, $totalVolumetricWeight);

        return [
            'actual' => $totalActualWeight,
            'volumetric' => $totalVolumetricWeight,
            'chargeable' => $chargeableWeight,
        ];
    }
}
