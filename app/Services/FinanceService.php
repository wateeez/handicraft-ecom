<?php

namespace App\Services;

use App\Models\FinanceAuditLog;
use App\Models\Invoice;
use App\Models\FinancialLedgerEntry;
use App\Models\FinanceInvoice;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinanceSetting;
use App\Services\Finance\OrderPayoutService;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\OrderNotification;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CommissionService $commissionService,
        private readonly OrderPayoutService $orderPayoutService,
    )
    {
    }

    public function recordPayment(Order $order, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'invoice_id' => $data['invoice_id'] ?? null,
                'client_id' => $order->client_id,
                'recorded_by' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $order->currency,
                'reporting_amount' => $data['reporting_amount'],
                'reporting_exchange_rate' => $data['reporting_exchange_rate'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'received_on' => $data['received_on'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit('payment_recorded', $order, $payment, $user, [], $payment->only(['amount', 'currency', 'method', 'reference']));

            return $payment;
        });
    }

    public function confirmPayment(Payment $payment, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $user) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Only pending payments can be confirmed.']);
            }

            $order = Order::lockForUpdate()->findOrFail($payment->order_id);
            $payment->forceFill([
                'status' => 'confirmed',
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
            ])->save();

            $this->audit('payment_confirmed', $order, $payment, $user, ['status' => 'pending'], ['status' => 'confirmed']);
            $this->ledger($order, $payment, 'payment_confirmed', $payment->amount, $payment->reporting_amount, $payment->reporting_exchange_rate);
            DB::afterCommit(fn () => $this->logNotification($order, 'payment_received'));

            $netReceipts = (float) Payment::query()
                ->where('order_id', $order->id)
                ->where('status', 'confirmed')
                ->sum('amount')
                - (float) Refund::query()
                    ->where('order_id', $order->id)
                    ->where('status', 'confirmed')
                    ->sum('amount');

            if (!$order->is_paid && $netReceipts >= (float) $order->grand_total) {
                $this->orderService->markAsPaid($order, $user);
                $this->initializeSettledOrderFinance($order);
            }

            $this->syncInvoiceRegister($order);

            return $payment->fresh();
        });
    }

    /**
     * Auto-record a payment that is already settled (PayPal capture, or an admin marking
     * a cash order paid): creates a confirmed payment, its ledger entry and audit trail,
     * and refreshes the invoice register. Idempotent — returns null when the order's
     * confirmed receipts already cover the total or the reference was already recorded.
     */
    public function recordSettledPayment(Order $order, string $method, ?string $reference = null, ?User $user = null): ?Payment
    {
        return DB::transaction(function () use ($order, $method, $reference, $user) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            $invoice = $this->ensureIssuedInvoice($order, $user);
            $invoiceTotal = (string) data_get($invoice->financial_snapshot, 'grand_total', $order->grand_total);

            $confirmed = Payment::query()->where('order_id', $order->id)->where('status', 'confirmed');
            if ($reference && (clone $confirmed)->where('reference', $reference)->exists()) {
                return null;
            }
            if ((float) (clone $confirmed)->sum('amount') >= (float) $invoiceTotal) {
                return null;
            }

            // The payment follows the invoice's pricing, not whatever the order currently holds.
            $amount = $invoiceTotal;
            $reportingRate = $this->reportingRate((string) $order->currency);
            $payment = Payment::create([
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'client_id' => $order->client_id,
                'recorded_by' => $user?->id,
                'confirmed_by' => $user?->id,
                'amount' => $amount,
                'currency' => $order->currency,
                'reporting_amount' => bcmul((string) $amount, $reportingRate, 2),
                'reporting_exchange_rate' => $reportingRate,
                'method' => $method,
                'reference' => $reference,
                'received_on' => now()->toDateString(),
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'notes' => 'Auto-recorded on ' . ($method === 'paypal' ? 'PayPal capture.' : 'admin mark-as-paid.'),
            ]);

            $this->audit('payment_auto_recorded', $order, $payment, $user, [], $payment->only(['amount', 'currency', 'method', 'reference']));
            $this->ledger($order, $payment, 'payment_confirmed', $payment->amount, $payment->reporting_amount, $payment->reporting_exchange_rate);
            DB::afterCommit(fn () => $this->logNotification($order, 'payment_received'));

            if ($order->is_paid) {
                $this->initializeSettledOrderFinance($order);
            }
            $this->syncInvoiceRegister($order);

            return $payment;
        });
    }

    /**
     * Rate that converts an order-currency amount into the reporting currency (NPR).
     * Refuses to guess: with no rate available the whole operation fails and rolls back.
     */
    private function reportingRate(string $orderCurrency): string
    {
        $reporting = (string) (FinanceSetting::query()->value('reporting_currency') ?? 'NPR');
        if (strtoupper($orderCurrency) === strtoupper($reporting)) {
            return '1';
        }

        $rate = app(\App\Contracts\ForexRateProvider::class)->currentRate();
        if ($rate === null || bccomp($rate, '0', 8) <= 0) {
            throw ValidationException::withMessages(['payment' => "No {$orderCurrency}→{$reporting} exchange rate is available right now. Try again, or record the payment manually with a rate."]);
        }

        return $rate;
    }

    /**
     * The order's live invoice, created and issued when missing so a settled payment always
     * has an invoice to be recorded against.
     */
    private function ensureIssuedInvoice(Order $order, ?User $user): Invoice
    {
        $invoices = app(InvoiceService::class);
        $invoice = $order->invoices()->whereIn('status', ['draft', 'issued'])->latest('id')->first();

        if (!$invoice) {
            return $invoices->generateInvoice($order, $user, 'issued');
        }

        return $invoice->status === 'draft' && $user ? $invoices->issueInvoice($invoice, $user) : $invoice;
    }

    public function confirmRefund(Refund $refund, User $user): Refund
    {
        return DB::transaction(function () use ($refund, $user) {
            $refund = Refund::lockForUpdate()->findOrFail($refund->id);

            if ($refund->status !== 'pending') {
                throw ValidationException::withMessages(['refund' => 'Only pending refunds can be confirmed.']);
            }

            $payment = Payment::lockForUpdate()->findOrFail($refund->payment_id);
            if ($payment->status !== 'confirmed') {
                throw ValidationException::withMessages(['payment' => 'Refunds require a confirmed original payment.']);
            }

            $priorRefunds = (float) Refund::query()
                ->where('payment_id', $payment->id)
                ->where('status', 'confirmed')
                ->sum('amount');

            if ($priorRefunds + (float) $refund->amount > (float) $payment->amount) {
                throw ValidationException::withMessages(['amount' => 'Refund total cannot exceed the original payment.']);
            }

            $refund->forceFill(['status' => 'confirmed', 'recorded_by' => $user->id, 'confirmed_at' => now()])->save();
            $this->audit('refund_issued', $refund->order, $refund, $user, ['status' => 'pending'], ['status' => 'confirmed']);
            $this->ledger($refund->order, $refund, 'refund_confirmed', -$refund->amount, -$refund->reporting_amount, $refund->reporting_exchange_rate);
            $this->syncInvoiceRegister($refund->order);

            return $refund->fresh();
        });
    }

    public function reversePayment(Payment $payment, User $user, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $user, $reason) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'confirmed') {
                throw ValidationException::withMessages(['payment' => 'Only confirmed payments can be reversed.']);
            }

            $reversal = Payment::create([
                'order_id' => $payment->order_id,
                'invoice_id' => $payment->invoice_id,
                'client_id' => $payment->client_id,
                'recorded_by' => $user->id,
                'confirmed_by' => $user->id,
                'reversal_of_payment_id' => $payment->id,
                'amount' => -$payment->amount,
                'currency' => $payment->currency,
                'reporting_amount' => -$payment->reporting_amount,
                'reporting_exchange_rate' => $payment->reporting_exchange_rate,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'received_on' => now()->toDateString(),
                'status' => 'reversed',
                'confirmed_at' => now(),
                'notes' => $reason,
            ]);

            $this->audit('payment_reversed', $payment->order, $reversal, $user, [], [
                'reversal_of_payment_id' => $payment->id,
                'reason' => $reason,
            ]);
            $this->ledger($payment->order, $reversal, 'payment_reversed', $reversal->amount, $reversal->reporting_amount, $reversal->reporting_exchange_rate);
            $this->syncInvoiceRegister($payment->order);

            return $reversal;
        });
    }

    private function audit(string $action, Order $order, object $subject, ?User $user, array $oldValues, array $newValues): void
    {
        FinanceAuditLog::create([
            'order_id' => $order->id,
            'client_id' => $order->client_id,
            'user_id' => $user?->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'action_type' => $action,
            'description' => ucfirst(str_replace('_', ' ', $action)) . " for order #{$order->order_number}.",
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    private function ledger(Order $order, object $source, string $entryType, mixed $amount, mixed $reportingAmount, mixed $exchangeRate): void
    {
        FinancialLedgerEntry::create([
            'order_id' => $order->id,
            'client_id' => $order->client_id,
            'source_type' => $source::class,
            'source_id' => $source->id,
            'entry_type' => $entryType,
            'currency' => $source->currency,
            'amount' => $amount,
            'reporting_amount' => $reportingAmount,
            'reporting_exchange_rate' => $exchangeRate,
            'occurred_at' => now(),
        ]);
    }

    private function syncInvoiceRegister(Order $order): void
    {
        foreach ($order->invoices()->where('status', '!=', 'voided')->get() as $invoice) {
            $paid = (float) Payment::where('invoice_id', $invoice->id)->where('status', 'confirmed')->sum('amount');
            $total = (float) data_get($invoice->financial_snapshot, 'grand_total', 0);
            FinanceInvoice::updateOrCreate(
                ['invoice_id' => $invoice->id],
                ['order_id' => $order->id, 'client_id' => $order->client_id, 'currency' => $order->currency, 'amount_paid' => $paid, 'balance_due' => max(0, $total - $paid), 'payment_status' => $paid <= 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partially_paid')]
            );
        }
    }

    private function initializeSettledOrderFinance(Order $order): void
        {
            $settings = FinanceSetting::query()->firstOrCreate([]);
            $basis = match ($settings->commission_base) {
                'before_discount' => (float) $order->subtotal,
                'after_discount_including_shipping' => (float) $order->grand_total,
                default => max(0, (float) $order->subtotal - (float) $order->order_discount_amount),
            };
            $commission = $settings->default_commission_type === 'fixed'
                ? (float) $settings->default_commission_value
                : round($basis * ((float) $settings->default_commission_value / 100), 2);

            $snapshot = FinanceOrderSnapshot::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'vendor_id' => $order->vendor_id,
                    'currency' => $order->currency,
                    'reporting_exchange_rate' => 1,
                    'vendor_share' => OrderFinanceSnapshot::where('order_id', $order->id)->orderByDesc('version')->value('vendor_total_npr') ?? 0,
                    'commission_type' => $settings->default_commission_type,
                    'commission_value' => $settings->default_commission_value,
                    'commission_base' => $settings->commission_base,
                    'commission_amount' => $commission,
                    'financial_snapshot' => ['order_total' => $order->grand_total, 'commission_basis' => $basis],
                    'locked_at' => now(),
                ]
            );

            $this->commissionService->calculate($order);
            $this->orderPayoutService->sync($order);
        }

    private function logNotification(Order $order, string $eventType): void
        {
            $email = $order->client?->email ?? data_get($order->client_snapshot, 'email');
            if (!$email) {
                return;
            }

            // Runs after the money records are committed; a notification problem must never undo a payment.
            try {
                OrderNotification::create([
                    'order_id' => $order->id,
                    'notifiable_email' => $email,
                    'channel' => 'email',
                    'event_type' => $eventType,
                    'status' => 'queued',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Finance: could not log order notification', ['order_id' => $order->id, 'event' => $eventType, 'error' => $e->getMessage()]);
            }
    }
}
