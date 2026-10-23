<?php

namespace Tests\Feature;

use App\Contracts\ForexRateProvider;
use App\Models\FinanceSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentReversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->app->bind(ForexRateProvider::class, fn () => new class implements ForexRateProvider {
            public function currentRate(): ?string { return '150.0000'; }
            public function refresh(): ?string { return '150.0000'; }
            public function remember(string $rate): void {}
        });
        FinanceSetting::query()->firstOrCreate([], [
            'default_commission_type' => 'percent',
            'default_commission_value' => 0,
            'commission_base' => 'after_discount_excluding_shipping',
        ]);
    }

    private function order(float $total = 329): Order
    {
        return Order::create([
            'order_number' => 'T-' . uniqid(),
            'type' => 'order',
            'subtotal' => $total,
            'grand_total' => $total,
            'currency' => 'USD',
        ]);
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

    private function confirmedPayment(Order $order, User $user, float $amount = 329): Payment
    {
        $service = app(FinanceService::class);
        $payment = $service->recordPayment($order, [
            'amount' => $amount,
            'currency' => 'USD',
            'reporting_amount' => $amount * 150,
            'reporting_exchange_rate' => 150,
            'method' => 'PayPal',
            'received_on' => now()->toDateString(),
        ], $user);

        return $service->confirmPayment($payment, $user);
    }

    private function net(Order $order): float
    {
        return (float) Payment::where('order_id', $order->id)->whereIn('status', ['confirmed', 'reversed'])->sum('amount');
    }

    public function test_a_payment_cannot_be_reversed_twice(): void
    {
        $user = $this->adminWith('record_payments', 'confirm_payments');
        $order = $this->order();
        $payment = $this->confirmedPayment($order, $user);
        $service = app(FinanceService::class);

        $service->reversePayment($payment, $user, 'first reversal');

        $this->expectException(ValidationException::class);
        $service->reversePayment($payment, $user, 'second reversal');
    }

    public function test_reversal_lowers_the_net_received(): void
    {
        $user = $this->adminWith('record_payments', 'confirm_payments');
        $order = $this->order();
        $payment = $this->confirmedPayment($order, $user);
        $this->assertSame(329.0, $this->net($order));

        app(FinanceService::class)->reversePayment($payment, $user, 'wrong payment');

        $this->assertSame(0.0, $this->net($order));
    }

    public function test_reverse_policy_needs_confirm_permission_and_a_confirmed_payment(): void
    {
        $confirmer = $this->adminWith('record_payments', 'confirm_payments');
        $recorderOnly = $this->adminWith('record_payments');
        $order = $this->order();
        $payment = $this->confirmedPayment($order, $confirmer);

        $this->assertTrue($confirmer->can('reverse', $payment));
        $this->assertFalse($recorderOnly->can('reverse', $payment));

        $pending = app(FinanceService::class)->recordPayment($order, [
            'amount' => 1, 'currency' => 'USD', 'reporting_amount' => 150,
            'reporting_exchange_rate' => 150, 'method' => 'cash', 'received_on' => now()->toDateString(),
        ], $confirmer);
        $this->assertFalse($confirmer->can('reverse', $pending));
    }

    public function test_overpayment_is_blocked_until_acknowledged(): void
    {
        $user = $this->adminWith('record_payments', 'confirm_payments', 'view_orders');
        $order = $this->order();
        $this->confirmedPayment($order, $user);

        $payload = [
            'amount' => 329, 'currency' => 'USD', 'reporting_amount' => 49350,
            'reporting_exchange_rate' => 150, 'method' => 'PayPal', 'received_on' => now()->toDateString(),
        ];

        $this->actingAs($user)->post(route('admin.finance.payments.store', $order), $payload)
            ->assertSessionHasErrors(['amount', 'confirm_overpayment']);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());

        $this->actingAs($user)->post(route('admin.finance.payments.store', $order), $payload + ['confirm_overpayment' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    public function test_payment_within_the_order_total_needs_no_acknowledgement(): void
    {
        $user = $this->adminWith('record_payments', 'view_orders');
        $order = $this->order();

        $this->actingAs($user)->post(route('admin.finance.payments.store', $order), [
            'amount' => 100, 'currency' => 'USD', 'reporting_amount' => 15000,
            'reporting_exchange_rate' => 150, 'method' => 'PayPal', 'received_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_mark_as_paid_records_the_chosen_method(): void
    {
        $user = $this->adminWith('manage_orders');
        $order = $this->order();

        $this->actingAs($user)->post(route('admin.orders.mark-paid', $order), ['method' => 'paypal', 'reference' => 'PP-123']);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment, 'a payment should be recorded');
        $this->assertSame('paypal', $payment->method);
        $this->assertSame('PP-123', $payment->reference);
        $this->assertTrue($order->fresh()->is_paid);
    }

    public function test_mark_as_paid_requires_a_method(): void
    {
        $user = $this->adminWith('manage_orders');
        $order = $this->order();

        $this->actingAs($user)->post(route('admin.orders.mark-paid', $order), [])->assertSessionHasErrors('method');
        $this->assertFalse($order->fresh()->is_paid);
    }

    public function test_finance_tab_lists_pending_payments_and_confirms_them(): void
    {
        $user = $this->adminWith('view_finance', 'view_dashboard', 'record_payments', 'confirm_payments');
        $order = $this->order();
        $pending = app(FinanceService::class)->recordPayment($order, [
            'amount' => 329, 'currency' => 'USD', 'reporting_amount' => 49350,
            'reporting_exchange_rate' => 150, 'method' => 'PayPal', 'reference' => 'REF-PENDING-1', 'received_on' => now()->toDateString(),
        ], $user);

        $this->actingAs($user)->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('Payments awaiting confirmation')
            ->assertSee('REF-PENDING-1')
            ->assertSee('Confirm receipt');

        $this->actingAs($user)->post(route('admin.finance.payments.confirm', $pending), [
            'return_to' => route('admin.finance.index') . '#receipts',
        ])->assertRedirect(route('admin.finance.index') . '#receipts');

        $this->assertSame('confirmed', $pending->fresh()->status);
        $this->actingAs($user)->get(route('admin.finance.index'))->assertSee('Nothing is waiting for confirmation.');
    }

    public function test_confirm_ignores_an_untrusted_return_to_url(): void
    {
        $user = $this->adminWith('record_payments', 'confirm_payments');
        $order = $this->order();
        $pending = app(FinanceService::class)->recordPayment($order, [
            'amount' => 10, 'currency' => 'USD', 'reporting_amount' => 1500,
            'reporting_exchange_rate' => 150, 'method' => 'cash', 'received_on' => now()->toDateString(),
        ], $user);

        $this->actingAs($user)->from('/admin/orders')->post(route('admin.finance.payments.confirm', $pending), ['return_to' => 'https://evil.example/'])
            ->assertRedirect('/admin/orders');
    }

    public function test_user_without_confirm_permission_cannot_confirm(): void
    {
        $recorder = $this->adminWith('record_payments');
        $order = $this->order();
        $pending = app(FinanceService::class)->recordPayment($order, [
            'amount' => 10, 'currency' => 'USD', 'reporting_amount' => 1500,
            'reporting_exchange_rate' => 150, 'method' => 'cash', 'received_on' => now()->toDateString(),
        ], $recorder);

        $this->actingAs($recorder)->post(route('admin.finance.payments.confirm', $pending))->assertForbidden();
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_finance_tab_reverses_a_confirmed_payment_and_hides_it_from_the_list(): void
    {
        $user = $this->adminWith('view_finance', 'view_dashboard', 'record_payments', 'confirm_payments');
        $order = $this->order();
        $payment = $this->confirmedPayment($order, $user);
        $payment->update(['reference' => 'REF-REVERSE-ME']);

        $this->actingAs($user)->get(route('admin.finance.index'))
            ->assertOk()->assertSee('REF-REVERSE-ME')->assertSee('Reverse payment');

        $this->actingAs($user)->post(route('admin.finance.payments.reverse', $payment), [
            'reason' => 'Recorded by mistake',
            'return_to' => route('admin.finance.index') . '#receipts',
        ])->assertRedirect(route('admin.finance.index') . '#receipts');

        $this->assertSame(0.0, $this->net($order));
        $this->actingAs($user)->get(route('admin.finance.index'))->assertDontSee('REF-REVERSE-ME');
    }

    public function test_finance_sidebar_has_collapsible_sub_navigation(): void
    {
        $viewer = $this->adminWith('view_finance', 'view_dashboard');

        $html = $this->actingAs($viewer)->get(route('admin.finance.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="finance-subnav"', $html);
        $this->assertStringContainsString('aria-controls="finance-subnav"', $html);
        foreach (['overview', 'receipts', 'profit', 'payouts', 'receivables', 'sales', 'lookup'] as $tab) {
            $this->assertStringContainsString(route('admin.finance.index') . '#' . $tab, $html);
        }
        $this->assertStringNotContainsString(route('admin.finance.index') . '#settings', $html, 'settings needs manage_finance');
        $this->assertStringNotContainsString('role="tablist"', $html, 'the in-page tab bar was replaced by the sidebar');
    }

    public function test_finance_settings_link_shows_for_finance_managers(): void
    {
        $manager = $this->adminWith('view_finance', 'manage_finance', 'view_dashboard');

        $this->actingAs($manager)->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee(route('admin.finance.index') . '#settings', false);
    }
}
