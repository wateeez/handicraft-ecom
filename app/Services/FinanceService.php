<?php

namespace App\Services;

use App\Models\FinanceAuditLog;
use App\Models\FinancialLedgerEntry;
use App\Models\FinanceInvoice;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinanceSetting;
use App\Models\Order;
use App\Models\OrderNotification;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CommissionService $commissionService,
        private readonly DistributorPayoutService $distributorPayoutService,
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
            $this->logNotification($order, 'payment_received');

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

    private function audit(string $action, Order $order, object $subject, User $user, array $oldValues, array $newValues): void
    {
        FinanceAuditLog::create([
            'order_id' => $order->id,
            'client_id' => $order->client_id,
            'user_id' => $user->id,
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
                    'distributor_id' => $order->distributor_id,
                    'currency' => $order->currency,
                    'reporting_exchange_rate' => 1,
                    'distributor_share' => 0,
                    'commission_type' => $settings->default_commission_type,
                    'commission_value' => $settings->default_commission_value,
                    'commission_base' => $settings->commission_base,
                    'commission_amount' => $commission,
                    'financial_snapshot' => ['order_total' => $order->grand_total, 'commission_basis' => $basis],
                    'locked_at' => now(),
                ]
            );

            $this->commissionService->calculate($order);
            if ($snapshot->distributor_id) {
                $this->distributorPayoutService->createPayable($order);
            }
        }

    private function logNotification(Order $order, string $eventType): void
        {
            $email = $order->client?->email ?? data_get($order->client_snapshot, 'email');
            if (!$email) {
                return;
            }

            OrderNotification::create([
                'order_id' => $order->id,
                'notifiable_email' => $email,
                'channel' => 'email',
                'event_type' => $eventType,
                'status' => 'queued',
            ]);
    }
}
