<?php

namespace Tests\Feature;

use App\Models\FinanceRateSetting;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\FinanceAuditLog;
use App\Models\FinancialLedgerEntry;
use App\Services\Finance\FinanceDashboardService;
use App\Models\OrderPayable;
use App\Models\PayoutPayment;
use App\Services\Finance\OrderPayoutService;
use Illuminate\Validation\ValidationException;
use App\Services\Finance\FinanceRateRepository;
use App\Services\Finance\OrderFinanceCalculator;
use App\Services\Finance\OrderFinanceService;
use Database\Seeders\ProfitSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use LogicException;
use Tests\TestCase;

class OrderProfitSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ProfitSettingsSeeder::class);
    }

    private function order(): Order
    {
        return Order::create(['order_number' => 'T-' . uniqid(), 'type' => 'order', 'subtotal' => 100, 'shipping_cost' => 65]);
    }

    private function data(Vendor $vendor, array $extra = []): array
    {
        return $extra + ['vendor_id' => $vendor->id, 'product_price_usd' => '100', 'shipping_usd' => '65', 'forex_rate' => '154'];
    }

    private function adminWith(string ...$permissions): User
    {
        $role = Role::create(['name' => 'r_' . uniqid(), 'display_name' => 'Test']);
        foreach ($permissions as $name) {
            $role->permissions()->attach(Permission::firstOrCreate(['name' => $name], ['display_name' => $name])->id);
        }
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_seeded_defaults_and_hme_vendor(): void
    {
        $rates = app(FinanceRateRepository::class)->all();

        $this->assertSame('14', $rates['gateway_fee_pct']);
        $this->assertSame('150', $rates['de_fixed_rate']);
        $this->assertSame('200', $rates['de_threshold_rate']);
        $hme = Vendor::where('name', 'HME')->firstOrFail();
        $this->assertEquals(50, $hme->cost_price_pct);
        $this->assertEquals(20, $hme->commission_pct);
    }

    public function test_snapshot_stores_inputs_and_outputs_for_the_reference_example(): void
    {
        $snapshot = app(OrderFinanceService::class)->calculateAndStore($this->order(), $this->data(Vendor::first()), null);

        $this->assertSame(1, $snapshot->version);
        $this->assertSame('9121.20', $snapshot->leftover_npr);
        $this->assertSame('2475.00', $snapshot->data_entry_npr);
        $this->assertSame('HME', $snapshot->vendor_name);
        $this->assertEquals(14, $snapshot->gateway_fee_pct);
        $this->assertEquals(50, $snapshot->vendor_cost_price_pct);
    }

    public function test_changing_vendor_or_settings_does_not_alter_an_existing_snapshot(): void
    {
        $vendor = Vendor::first();
        $order = $this->order();
        $service = app(OrderFinanceService::class);
        $first = $service->calculateAndStore($order, $this->data($vendor), null);

        $vendor->update(['cost_price_pct' => 60, 'commission_pct' => 30]);
        app(FinanceRateRepository::class)->setMany(['gateway_fee_pct' => 20, 'csr_pct' => 9]);

        $reloaded = OrderFinanceSnapshot::find($first->id);
        $this->assertSame('9121.20', $reloaded->leftover_npr);
        $this->assertEquals(50, $reloaded->vendor_cost_price_pct);
        $this->assertEquals(14, $reloaded->gateway_fee_pct);

        // The stored inputs still reproduce the stored outputs
        $replayed = (new OrderFinanceCalculator())->calculate($service->inputFromSnapshot($reloaded));
        $this->assertSame($reloaded->net_profit_npr, $replayed->netProfitNpr);

        // Recalculating makes a NEW version with the new terms, leaving v1 intact
        $second = $service->calculateAndStore($order, $this->data($vendor), null);
        $this->assertSame(2, $second->version);
        $this->assertNotSame($first->leftover_npr, $second->leftover_npr);
        $this->assertSame('9121.20', OrderFinanceSnapshot::find($first->id)->leftover_npr);
    }

    public function test_deleting_a_vendor_keeps_the_snapshot_figures(): void
    {
        $vendor = Vendor::first();
        $snapshot = app(OrderFinanceService::class)->calculateAndStore($this->order(), $this->data($vendor), null);

        $vendor->delete();

        $this->assertSame('HME', $snapshot->fresh()->vendor_name);
        $this->assertSame('9121.20', $snapshot->fresh()->leftover_npr);
        $this->assertSame('HME', $snapshot->fresh()->vendor->name);
    }

    public function test_snapshots_are_immutable(): void
    {
        $snapshot = app(OrderFinanceService::class)->calculateAndStore($this->order(), $this->data(Vendor::first()), null);

        $this->expectException(LogicException::class);
        $snapshot->update(['net_profit_npr' => 1]);
    }

    public function test_settings_are_cached_and_refreshed_on_save(): void
    {
        $rates = app(FinanceRateRepository::class);
        $rates->all();
        FinanceRateSetting::where('key', 'csr_pct')->update(['value' => '99']); // bypasses the repository

        $this->assertSame('4', $rates->get('csr_pct'), 'served from cache');

        $rates->set('csr_pct', '5');
        $this->assertSame('5', $rates->get('csr_pct'));
    }

    public function test_only_finance_managers_can_calculate_and_inactive_vendors_are_rejected(): void
    {
        $order = $this->order();
        $vendor = Vendor::first();
        $payload = $this->data($vendor);

        $viewer = $this->adminWith('view_finance');
        $this->actingAs($viewer)->get(route('admin.finance.orders.profit.show', $order))->assertOk();
        $this->actingAs($viewer)->post(route('admin.finance.orders.profit.store', $order), $payload)->assertForbidden();

        $manager = $this->adminWith('manage_finance');
        $this->actingAs($manager)->post(route('admin.finance.orders.profit.store', $order), $payload)
            ->assertRedirect(route('admin.finance.orders.profit.show', $order));
        $this->assertSame(1, $order->financeSnapshots()->count());

        $vendor->update(['is_active' => false]);
        $this->actingAs($manager)->post(route('admin.finance.orders.profit.store', $order), $payload)
            ->assertSessionHasErrors('vendor_id');
        $this->assertSame(1, $order->financeSnapshots()->count());
    }

    public function test_validation_rejects_bad_numbers(): void
    {
        $order = $this->order();
        $manager = $this->adminWith('manage_finance');

        $this->actingAs($manager)->post(route('admin.finance.orders.profit.store', $order), $this->data(Vendor::first(), ['forex_rate' => '0', 'product_price_usd' => '-1']))
            ->assertSessionHasErrors(['forex_rate', 'product_price_usd']);

        $this->actingAs($manager)->put(route('admin.finance.rates.update'), ['gateway_fee_pct' => 101, 'csr_pct' => 4, 'data_entry_pct' => 10, 'de_fixed_rate' => 0, 'de_threshold_rate' => 200])
            ->assertSessionHasErrors(['gateway_fee_pct', 'de_fixed_rate']);
    }

    public function test_vendor_crud_and_rate_update(): void
    {
        $manager = $this->adminWith('manage_finance');

        $this->actingAs($manager)->post(route('admin.finance.vendors.store'), ['name' => 'ACME', 'cost_price_pct' => 45, 'commission_pct' => 15, 'is_active' => 1])
            ->assertRedirect(route('admin.finance.vendors.index'));
        $acme = Vendor::where('name', 'ACME')->firstOrFail();

        $this->actingAs($manager)->post(route('admin.finance.vendors.store'), ['name' => 'ACME', 'cost_price_pct' => 45, 'commission_pct' => 15])
            ->assertSessionHasErrors('name');

        $this->actingAs($manager)->patch(route('admin.finance.vendors.toggle', $acme))->assertRedirect();
        $this->assertFalse($acme->fresh()->is_active);

        $this->actingAs($manager)->delete(route('admin.finance.vendors.destroy', $acme))->assertRedirect();
        $this->assertSoftDeleted($acme);

        $this->actingAs($manager)->put(route('admin.finance.rates.update'), ['gateway_fee_pct' => 12, 'csr_pct' => 4, 'data_entry_pct' => 10, 'de_fixed_rate' => 150, 'de_threshold_rate' => 200, 'include_shipping_in_commission' => 0])
            ->assertRedirect();
        $this->assertSame('12', app(FinanceRateRepository::class)->get('gateway_fee_pct'));
        $this->assertFalse(app(FinanceRateRepository::class)->includeShippingInCommission());
    }

    public function test_all_profit_pages_render_with_data(): void
    {
        $vendor = Vendor::first();
        $order = $this->order();
        $service = app(OrderFinanceService::class);
        $service->calculateAndStore($order, $this->data($vendor), null);
        $service->calculateAndStore($order, $this->data($vendor, ['actual_shipping_cost_npr' => '500']), null);
        $manager = $this->adminWith('manage_finance');

        $this->actingAs($manager)->get(route('admin.finance.orders.profit.show', $order))
            ->assertOk()->assertSee('Version 2')->assertSee('NPR 8,621.20')->assertSee('History');
        $this->actingAs($manager)->get(route('admin.finance.orders.profit.show', [$order, 'version' => 1]))
            ->assertOk()->assertSee('Version 1')->assertSee('NPR 9,121.20');
        $this->actingAs($this->adminWith('view_finance', 'manage_finance'))->get(route('admin.finance.index'))->assertOk()->assertSee('Payment gateway fee');
        $this->actingAs($manager)->get(route('admin.finance.rates.edit'))->assertRedirect(route('admin.finance.index', ['tool' => 'settings']));
        $this->actingAs($manager)->get(route('admin.finance.vendors.index'))->assertOk()->assertSee('HME');
        $this->actingAs($manager)->get(route('admin.finance.vendors.create'))->assertOk();
        $this->actingAs($manager)->get(route('admin.finance.vendors.edit', $vendor))->assertOk()->assertSee('HME');
    }

    private function paidOrder(array $data = []): Order
    {
        $order = $this->order();
        $order->forceFill(['is_paid' => true])->saveQuietly();
        app(OrderFinanceService::class)->calculateAndStore($order, $this->data(Vendor::first(), $data), null);

        return $order;
    }

    public function test_no_payables_until_the_order_is_paid_and_a_profit_snapshot_exists(): void
    {
        $unpaid = $this->order();
        $this->assertSame([], app(OrderPayoutService::class)->sync($unpaid));

        app(OrderFinanceService::class)->calculateAndStore($unpaid, $this->data(Vendor::first()), null);
        $this->assertSame(0, OrderPayable::count(), 'unpaid order owes nothing yet');

        $paidWithoutSnapshot = $this->order();
        $paidWithoutSnapshot->forceFill(['is_paid' => true])->saveQuietly();
        $this->assertSame([], app(OrderPayoutService::class)->sync($paidWithoutSnapshot));
    }

    public function test_paid_order_owes_vendor_csr_and_data_entry_and_recalculation_adjusts_them(): void
    {
        $order = $this->paidOrder();

        $payables = $order->payables()->get()->keyBy('payee_type');
        $this->assertSame('9240.00', $payables['vendor']->amount);
        $this->assertSame('1016.40', $payables['csr']->amount);
        $this->assertSame('2475.00', $payables['data_entry']->amount);
        $this->assertSame(Vendor::first()->id, $payables['vendor']->vendor_id);
        $this->assertNull($payables['csr']->vendor_id);
        $this->assertSame('unpaid', $payables['csr']->status);

        app(OrderFinanceService::class)->calculateAndStore($order, $this->data(Vendor::first(), ['forex_rate' => '160']), null);

        $this->assertSame(3, OrderPayable::count());
        $payables = $order->payables()->get()->keyBy('payee_type');
        $this->assertSame('9600.00', $payables['vendor']->amount);
        $this->assertSame('1056.00', $payables['csr']->amount);
        $this->assertSame('2475.00', $payables['data_entry']->amount);
    }

    public function test_recalculation_cannot_drop_an_amount_below_what_was_already_paid(): void
    {
        $order = $this->paidOrder();
        OrderPayable::where('order_id', $order->id)->where('payee_type', 'vendor')->update(['paid_amount' => 9000]);

        try {
            app(OrderFinanceService::class)->calculateAndStore($order, $this->data(Vendor::first(), ['forex_rate' => '100']), null); // vendor total 6000
            $this->fail('Expected a validation error.');
        } catch (ValidationException) {
            $this->assertSame(1, $order->financeSnapshots()->count(), 'the failed recalculation is rolled back');
        }
    }

    public function test_payments_are_recorded_per_payee_and_update_the_status(): void
    {
        $order = $this->paidOrder();
        $user = $this->adminWith('manage_finance');
        $payouts = app(OrderPayoutService::class);
        $base = ['paid_on' => '2026-10-19', 'method' => 'bank'];

        $payouts->recordPayment($order, $base + ['payee_type' => 'csr', 'amount' => '500'], $user);
        $csr = $order->payables()->where('payee_type', 'csr')->first();
        $this->assertSame('500.00', $csr->paid_amount);
        $this->assertSame('partial', $csr->status);
        $this->assertSame('516.40', $csr->balance);

        $payouts->recordPayment($order, $base + ['payee_type' => 'csr', 'amount' => '516.40', 'reference' => 'TXN-1'], $user);
        $this->assertSame('paid', $csr->fresh()->status);
        $this->assertSame(2, PayoutPayment::where('order_payable_id', $csr->id)->count());

        $payouts->recordPayment($order, $base + ['payee_type' => 'vendor', 'amount' => '9240'], $user);
        $this->assertSame('paid', $order->payables()->where('payee_type', 'vendor')->first()->status);
        $this->assertSame('unpaid', $order->payables()->where('payee_type', 'data_entry')->first()->status);

        $this->assertSame(3, FinanceAuditLog::where('action_type', 'payout_payment_recorded')->count());
    }

    public function test_bulk_payment_settles_the_full_balance_of_every_selected_order(): void
    {
        $a = $this->paidOrder();
        $b = $this->paidOrder();
        $manager = $this->adminWith('manage_finance');
        $csrIds = OrderPayable::whereIn('order_id', [$a->id, $b->id])->where('payee_type', 'csr')->pluck('id')->all();
        $payload = ['payee_type' => 'csr', 'payable_ids' => $csrIds, 'paid_on' => now()->toDateString(), 'method' => 'bank'];

        $this->actingAs($this->adminWith('view_finance'))->post(route('admin.finance.payouts.bulk'), $payload)->assertForbidden();
        $this->actingAs($manager)->post(route('admin.finance.payouts.bulk'), ['payable_ids' => []] + $payload)->assertSessionHasErrors('payable_ids');

        $this->actingAs($manager)->post(route('admin.finance.payouts.bulk'), $payload)->assertRedirect();
        $this->assertSame(['paid', 'paid'], OrderPayable::whereIn('id', $csrIds)->pluck('status')->all());
        $this->assertSame(2, PayoutPayment::whereIn('order_payable_id', $csrIds)->count());
        $this->assertSame(2, FinanceAuditLog::where('action_type', 'payout_payment_recorded')->count());

        // Paying again is rejected and records nothing more.
        $this->actingAs($manager)->post(route('admin.finance.payouts.bulk'), $payload)->assertSessionHasErrors('payable_ids');
        $this->assertSame(2, PayoutPayment::whereIn('order_payable_id', $csrIds)->count());
    }

    public function test_overpaying_or_paying_a_payee_with_no_payable_is_rejected(): void
    {
        $order = $this->paidOrder();
        $user = $this->adminWith('manage_finance');
        $payouts = app(OrderPayoutService::class);
        $base = ['paid_on' => '2026-10-19', 'method' => 'bank'];

        try {
            $payouts->recordPayment($order, $base + ['payee_type' => 'data_entry', 'amount' => '2475.01'], $user);
            $this->fail('Overpayment should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $unpaid = $this->order();
        $this->expectException(ValidationException::class);
        $payouts->recordPayment($unpaid, $base + ['payee_type' => 'vendor', 'amount' => '1'], $user);
    }

    public function test_recording_a_payout_payment_over_http_needs_manage_finance(): void
    {
        $order = $this->paidOrder();
        $payload = ['payee_type' => 'vendor', 'amount' => '1000', 'paid_on' => now()->toDateString(), 'method' => 'bank'];

        $this->actingAs($this->adminWith('view_finance'))->post(route('admin.finance.orders.payouts.store', $order), $payload)->assertForbidden();

        $manager = $this->adminWith('manage_finance');
        $this->actingAs($manager)->post(route('admin.finance.orders.payouts.store', $order), $payload)
            ->assertRedirect(route('admin.finance.orders.profit.show', $order));
        $this->actingAs($manager)->post(route('admin.finance.orders.payouts.store', $order), ['amount' => '-5'] + $payload)->assertSessionHasErrors('amount');
        $this->actingAs($manager)->post(route('admin.finance.orders.payouts.store', $order), ['paid_on' => now()->addDay()->toDateString()] + $payload)->assertSessionHasErrors('paid_on');

        $this->actingAs($manager)->get(route('admin.finance.orders.profit.show', $order))
            ->assertOk()->assertSee('Payouts (NPR)')->assertSee('NPR 8,240.00')->assertSee('Partial');
    }

    public function test_vendor_contact_details_can_be_saved(): void
    {
        $manager = $this->adminWith('manage_finance');

        $this->actingAs($manager)->post(route('admin.finance.vendors.store'), ['name' => 'Contacts Co', 'email' => 'pay@example.com', 'phone' => '+977 1', 'address' => 'Kathmandu', 'cost_price_pct' => 40, 'commission_pct' => 10])
            ->assertRedirect(route('admin.finance.vendors.index'));

        $this->assertSame('pay@example.com', Vendor::where('name', 'Contacts Co')->value('email'));
    }

    public function test_finance_sidebar_submenu_follows_permissions(): void
    {
        $overviewUrl = route('admin.finance.index') . '#overview';
        $vendorsUrl = route('admin.finance.vendors.index');
        $link = fn (string $url) => 'href="' . $url . '"';

        // manage_finance alone: Vendors, but no Overview (that page needs view_finance)
        $this->actingAs($this->adminWith('manage_finance'))->get($vendorsUrl)
            ->assertOk()->assertSee($link($vendorsUrl), false)->assertDontSee($link($overviewUrl), false);

        // view_finance alone: Overview, but no Vendors
        $this->actingAs($this->adminWith('view_finance'))->get(route('admin.finance.index'))
            ->assertOk()->assertSee($link($overviewUrl), false)->assertDontSee($link($vendorsUrl), false);

        // Both: the full submenu
        $this->actingAs($this->adminWith('view_finance', 'manage_finance'))->get(route('admin.finance.index'))
            ->assertOk()->assertSee($link($overviewUrl), false)->assertSee($link($vendorsUrl), false)->assertSee("hash === 'overview'", false);
    }

    public function test_finance_overview_shows_its_data_without_hiding_it_in_collapsed_panels(): void
    {
        $order = $this->paidOrder();
        $user = $this->adminWith('view_finance', 'manage_finance');
        app(OrderPayoutService::class)->recordPayment($order, ['payee_type' => 'csr', 'amount' => '16.40', 'paid_on' => '2026-10-19', 'method' => 'bank'], $user);

        $html = $this->actingAs($user)->get(route('admin.finance.index'))->assertOk()
            ->assertSee('Net profit')->assertSee('NPR 9,121.20')
            ->assertSee('Payouts by payee')->assertSee('Vendor')->assertSee('CSR')->assertSee('Data entry')
            ->assertSee('9,240.00')->assertSee('1,016.40')->assertSee('16.40')->assertSee('1,000.00')
            ->assertSee('Unpaid order balances by age')->assertSee('Receipts activity')
            ->assertSee('Recent profit calculations')->assertSee($order->order_number)
            ->getContent();

        // Data sections must not live inside a collapsed <details>; only the Tools forms may
        foreach (['Payouts by payee', 'Unpaid order balances by age', 'Receipts activity', 'Recent profit calculations', 'Sales'] as $heading) {
            $before = substr($html, 0, strpos($html, '>' . $heading . '<'));
            $this->assertSame(substr_count($before, '<details'), substr_count($before, '</details>'), "{$heading} is inside a collapsed panel");
        }
    }

    public function test_finance_overview_flags_work_that_needs_attention_and_filters_by_date(): void
    {
        $unprofited = $this->order();
        $unprofited->forceFill(['is_paid' => true])->saveQuietly();
        $user = $this->adminWith('view_finance');

        $this->actingAs($user)->get(route('admin.finance.index'))
            ->assertOk()->assertSee('Needs attention')->assertSee('no profit calculation yet');

        $this->actingAs($user)->get(route('admin.finance.index', ['from' => '2026-01-01', 'to' => '2026-12-31']))
            ->assertOk()->assertSee('Filtered: 2026-01-01 to 2026-12-31');
    }

    private function ledger(Order $order, string $type, string $amount, string $when): void
    {
        FinancialLedgerEntry::create([
            'order_id' => $order->id, 'source_type' => 'test', 'source_id' => 1, 'entry_type' => $type,
            'currency' => 'USD', 'amount' => $amount, 'reporting_amount' => $amount, 'reporting_exchange_rate' => 1,
            'occurred_at' => $when,
        ]);
    }

    public function test_receipts_series_buckets_ledger_entries_by_day_and_month(): void
    {
        $order = $this->order();
        $this->ledger($order, 'payment_confirmed', '100.00', '2026-10-10 09:00:00');
        $this->ledger($order, 'payment_confirmed', '50.00', '2026-10-10 18:00:00');
        $this->ledger($order, 'refund_confirmed', '-30.00', '2026-10-11 10:00:00');
        $this->ledger($order, 'payment_reversed', '-20.00', '2026-10-11 12:00:00');
        $this->ledger($order, 'payment_confirmed', '999.00', '2026-09-01 10:00:00'); // outside the range
        $service = app(FinanceDashboardService::class);

        $daily = $service->receiptsSeries('2026-10-09', '2026-10-12');
        $this->assertSame('day', $daily['granularity']);
        $this->assertSame(['Oct 9', 'Oct 10', 'Oct 11', 'Oct 12'], $daily['labels']);
        $this->assertSame([0.0, 150.0, 0.0, 0.0], $daily['receipts']);
        $this->assertSame([0.0, 0.0, 50.0, 0.0], $daily['outflow']);
        $this->assertSame([0.0, 150.0, -50.0, 0.0], $daily['net']);
        $this->assertTrue($daily['filtered']);

        $monthly = $service->receiptsSeries('2026-08-01', '2026-10-31');
        $this->assertSame('month', $monthly['granularity']);
        $this->assertSame(['Aug 2026', 'Sep 2026', 'Oct 2026'], $monthly['labels']);
        $this->assertSame([0.0, 999.0, 150.0], $monthly['receipts']);

        // Reversed bounds are swapped and a bad date falls back to the default window
        $this->assertSame('2026-10-09', $service->receiptsSeries('2026-10-12', '2026-10-09')['from']);
        $fallback = $service->receiptsSeries('not-a-date', null);
        $this->assertSame(30, count($fallback['labels']));
        $this->assertFalse($service->receiptsSeries(null, null)['filtered']);
    }

    public function test_money_split_adds_up_to_the_selling_price_in_npr(): void
    {
        $this->paidOrder();
        $profit = app(FinanceDashboardService::class)->build(null, null)['profit'];

        $this->assertSame('9240.00', $profit['parts']['vendor']);
        $this->assertSame('1016.40', $profit['parts']['csr']);
        $this->assertSame('2475.00', $profit['parts']['data_entry']);
        $this->assertSame('3557.40', $profit['parts']['gateway']); // 23.10 USD x 154
        $this->assertSame('9121.20', $profit['parts']['net']);
        $this->assertSame('25410.00', $profit['gross']); // 165 USD x 154
        $this->assertSame(1, $profit['count']);
        $this->assertSame('HME', $profit['vendors'][0]['name']);
        $this->assertSame('9121.20', $profit['average']);
        $this->assertCount(6, $profit['by_month']);
        $this->assertSame(9121.2, $profit['by_month']->last()['net']);
    }

    public function test_overview_renders_tabs_and_well_formed_charts_with_data(): void
    {
        $order = $this->paidOrder();
        $user = $this->adminWith('view_finance', 'manage_finance');
        app(OrderPayoutService::class)->recordPayment($order, ['payee_type' => 'vendor', 'amount' => '4620', 'paid_on' => now()->toDateString(), 'method' => 'bank', 'reference' => 'TXN-7'], $user);
        $this->ledger($order, 'payment_confirmed', '165.00', now()->subDays(2)->toDateTimeString());
        $this->ledger($order, 'refund_confirmed', '-15.00', now()->subDay()->toDateTimeString());

        $html = $this->actingAs($user)->get(route('admin.finance.index'))->assertOk()
            ->assertSee('Where the money goes')->assertSee('Receipts trend')->assertSee('Payout progress')
            ->assertSee('Net profit by order')->assertSee('Monthly net profit')->assertSee('Profit by vendor')
            ->assertSee('Recent payout payments')->assertSee('TXN-7')->assertSee('Largest unpaid orders')
            ->assertSee('Day by day')->assertSee('Gateway fees')->assertSee('NPR 3,557.40')
            ->getContent();

        foreach (['overview', 'receipts', 'profit', 'payouts', 'receivables', 'sales'] as $tab) {
            $this->assertStringContainsString('id="panel-' . $tab . '"', $html);
        }

        // Every SVG on the page must be well-formed XML
        preg_match_all('#<svg\b.*?</svg>#s', $html, $matches);
        $this->assertGreaterThanOrEqual(8, count($matches[0]));
        libxml_use_internal_errors(true);
        foreach ($matches[0] as $svg) {
            $doc = simplexml_load_string(str_replace('&nbsp;', ' ', $svg));
            $this->assertNotFalse($doc, 'Malformed SVG: ' . substr($svg, 0, 160) . ' ' . json_encode(array_map(fn ($e) => trim($e->message), libxml_get_errors())));
            libxml_clear_errors();
        }
    }

    public function test_overview_is_safe_with_no_data_at_all(): void
    {
        $this->actingAs($this->adminWith('view_finance'))->get(route('admin.finance.index'))->assertOk()
            ->assertSee('No activity in this period')->assertSee('No data yet')
            ->assertSee('Calculate a paid order')->assertSee('Nothing outstanding');
    }
}
