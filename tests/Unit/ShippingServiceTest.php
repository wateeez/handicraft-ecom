<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ShippingService;
use App\Models\ShippingZone;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;

class ShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeItem(float $weight = 1.0, float $length = 10.0, float $width = 10.0, float $height = 10.0, int $qty = 1): array
    {
        $product = new class($weight, $length, $width, $height) {
            public $weight; public $length; public $width; public $height;
            public function __construct($w, $l, $b, $h) { $this->weight = $w; $this->length = $l; $this->width = $b; $this->height = $h; }
        };
        return ['product' => $product, 'quantity' => $qty];
    }

    private function seedZonesAndRates(): array
    {
        $zone1 = ShippingZone::create(['name' => 'Zone 1', 'countries' => ['US', 'CA', 'DE']]);
        $zone2 = ShippingZone::create(['name' => 'Zone 2', 'countries' => ['United Kingdom', 'France']]);

        $provider = ShippingProvider::create(['name' => 'DHL', 'is_active' => true]);

        ShippingRate::create([
            'shipping_provider_id' => $provider->id,
            'shipping_zone_id' => $zone1->id,
            'min_weight' => 0,
            'max_weight' => 100,
            'price' => 10.00,
        ]);

        ShippingRate::create([
            'shipping_provider_id' => $provider->id,
            'shipping_zone_id' => $zone2->id,
            'min_weight' => 0,
            'max_weight' => 100,
            'price' => 20.00,
        ]);

        return [$zone1, $zone2, $provider];
    }

    private function seedZoneWithWeightLimit(float $maxWeight): ShippingZone
    {
        $zone = ShippingZone::create(['name' => 'Weight Limit Zone', 'countries' => ['NP']]);
        $provider = ShippingProvider::create(['name' => 'TestCourier', 'is_active' => true]);

        ShippingRate::create([
            'shipping_provider_id' => $provider->id,
            'shipping_zone_id' => $zone->id,
            'min_weight' => 0,
            'max_weight' => $maxWeight,
            'price' => 15.00,
        ]);

        return $zone;
    }

    public function test_over_weight_is_false_when_under_admin_limit()
    {
        $this->seedZoneWithWeightLimit(10);
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(8.0)], 'NP');

        $this->assertFalse($result['over_weight']);
    }

    public function test_over_weight_is_false_when_exactly_equal_to_admin_limit()
    {
        $this->seedZoneWithWeightLimit(10);
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(10.0)], 'NP');

        $this->assertFalse($result['over_weight']);
    }

    public function test_over_weight_is_true_when_slightly_above_admin_limit()
    {
        $this->seedZoneWithWeightLimit(10);
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(10.1)], 'NP');

        $this->assertTrue($result['over_weight']);
    }

    public function test_over_weight_is_true_when_well_above_admin_limit()
    {
        $this->seedZoneWithWeightLimit(10);
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(12.0)], 'NP');

        $this->assertTrue($result['over_weight']);
    }

    public function test_over_weight_is_false_for_matching_decimal_limit()
    {
        $this->seedZoneWithWeightLimit(10.5);
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(10.5)], 'NP');

        $this->assertFalse($result['over_weight']);
    }

    public function test_over_weight_is_true_for_decimal_above_limit()
    {
        $this->seedZoneWithWeightLimit(10.5);
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(10.6)], 'NP');

        $this->assertTrue($result['over_weight']);
    }

    public function test_over_weight_is_false_when_no_zone_matches()
    {
        $service = new ShippingService();

        $result = $service->checkOverWeight([$this->makeItem(999.0)], 'ZZ-UNKNOWN');

        $this->assertFalse($result['over_weight']);
    }

    public function test_matches_zone_when_country_is_name()
    {
        [$zone1, $zone2] = $this->seedZonesAndRates();
        $service = new ShippingService();

        $items = [ $this->makeItem(2.0) ];
        $rates = $service->calculateShipping($items, 'United States');

        $this->assertNotEmpty($rates, 'Expected rates for United States');
        $this->assertSame('Zone 1', $rates[0]['details']['zone']);
    }

    public function test_matches_zone_when_country_is_code()
    {
        [$zone1, $zone2] = $this->seedZonesAndRates();
        $service = new ShippingService();

        $items = [ $this->makeItem(2.0) ];
        $rates = $service->calculateShipping($items, 'GB');

        $this->assertNotEmpty($rates, 'Expected rates for GB');
        $this->assertSame('Zone 2', $rates[0]['details']['zone']);
    }

    public function test_direct_code_match_still_works()
    {
        [$zone1, $zone2] = $this->seedZonesAndRates();
        $service = new ShippingService();

        $items = [ $this->makeItem(2.0) ];
        $rates = $service->calculateShipping($items, 'DE');

        $this->assertNotEmpty($rates, 'Expected rates for DE');
        $this->assertSame('Zone 1', $rates[0]['details']['zone']);
    }
}
