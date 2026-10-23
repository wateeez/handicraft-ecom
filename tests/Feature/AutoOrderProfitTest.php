<?php

namespace Tests\Feature;

use App\Contracts\ForexRateProvider;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Finance\AutoOrderProfitService;
use Database\Seeders\ProfitSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AutoOrderProfitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ProfitSettingsSeeder::class);
        $this->bindRate('150.0000');
    }

    private function bindRate(?string $rate): void
    {
        $this->app->bind(ForexRateProvider::class, fn () => new class($rate) implements ForexRateProvider {
            public function __construct(private ?string $rate) {}
            public function currentRate(): ?string { return $this->rate; }
            public function refresh(): ?string { return $this->rate; }
            public function remember(string $rate): void {}
        });
    }

    private function paidOrder(?Product $product = null): Order
    {
        $order = Order::create([
            'order_number' => 'T-' . uniqid(),
            'type' => 'order',
            'subtotal' => 100,
            'shipping_cost' => 65,
            'grand_total' => 165,
            'currency' => 'USD',
            'is_paid' => true,
        ]);

        if ($product) {
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1,
                'unit_price' => 100, 'line_total' => 100, 'product_snapshot' => ['name' => $product->name],
            ]);
        }

        return $order;
    }

    public function test_calculates_profit_with_the_products_vendor(): void
    {
        $vendor = Vendor::create(['name' => 'Acme', 'cost_price_pct' => 40, 'commission_pct' => 10, 'is_active' => true]);
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $order = $this->paidOrder($product);

        $snapshot = app(AutoOrderProfitService::class)->calculateIfNeeded($order);

        $this->assertNotNull($snapshot);
        $this->assertSame($vendor->id, $snapshot->vendor_id);
        $this->assertEquals(150, $snapshot->forex_rate);
        $this->assertEquals(100, $snapshot->product_price_usd);
        $this->assertEquals(65, $snapshot->shipping_usd);
        $this->assertSame($vendor->id, $order->fresh()->vendor_id);
    }

    public function test_falls_back_to_the_default_vendor(): void
    {
        $order = $this->paidOrder(Product::factory()->create());

        $snapshot = app(AutoOrderProfitService::class)->calculateIfNeeded($order);

        $this->assertSame('HME', $snapshot->vendor_name);
    }

    public function test_never_calculates_twice(): void
    {
        $order = $this->paidOrder();
        $service = app(AutoOrderProfitService::class);

        $this->assertNotNull($service->calculateIfNeeded($order));
        $this->assertNull($service->calculateIfNeeded($order));
        $this->assertSame(1, OrderFinanceSnapshot::where('order_id', $order->id)->count());
    }

    public function test_leaves_the_order_for_an_admin_when_no_rate_is_available(): void
    {
        $this->bindRate(null);
        $order = $this->paidOrder();

        $this->assertNull(app(AutoOrderProfitService::class)->calculateIfNeeded($order));
        $this->assertSame(0, OrderFinanceSnapshot::where('order_id', $order->id)->count());
    }

    public function test_leaves_the_order_for_an_admin_when_no_vendor_can_be_chosen(): void
    {
        Vendor::query()->update(['is_active' => false]);
        $order = $this->paidOrder();

        $this->assertNull(app(AutoOrderProfitService::class)->calculateIfNeeded($order));
        $this->assertSame(0, OrderFinanceSnapshot::where('order_id', $order->id)->count());
    }
}
