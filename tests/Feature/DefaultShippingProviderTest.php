<?php

namespace Tests\Feature;

use App\Models\ShippingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultShippingProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_shipping_is_the_default_provider_when_not_selected(): void
    {
        $provider = ShippingProvider::firstOrCreate(['name' => 'Standard Shipping']);

        $this->assertSame($provider->id, ShippingProvider::getDefaultProviderId());
    }
}
