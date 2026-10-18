<?php

namespace App\Services\Finance;

use App\Models\FinanceAuditLog;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\OrderPayable;
use App\Models\PayoutPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Payables and payments for the three payees of a paid order, all in NPR:
 * the vendor (vendor total), the CSR and data entry (their commissions).
 */
class OrderPayoutService
{
    /**
     * Create or refresh the order's payables from the latest profit snapshot.
     * Returns nothing for an unpaid order or one with no snapshot, so no figure
     * is guessed. A payable never drops below what has already been paid.
     *
     * @return array<string, OrderPayable>
     */
    public function sync(Order $order): array
    {
        if (!$order->is_paid) {
            return [];
        }

        return DB::transaction(function () use ($order) {
            $snapshot = OrderFinanceSnapshot::where('order_id', $order->id)->orderByDesc('version')->first();
            if (!$snapshot) {
                return [];
            }

            $amounts = [
                OrderPayable::TYPE_VENDOR => (string) $snapshot->vendor_total_npr,
                OrderPayable::TYPE_CSR => (string) $snapshot->csr_npr,
                OrderPayable::TYPE_DATA_ENTRY => (string) $snapshot->data_entry_npr,
            ];

            $payables = [];
            foreach ($amounts as $type => $amount) {
                $payable = OrderPayable::where('order_id', $order->id)->where('payee_type', $type)->lockForUpdate()->first();

                if ($payable && bccomp((string) $payable->paid_amount, $amount, 2) > 0) {
                    throw ValidationException::withMessages([$type => "The recalculated {$payable->label} amount is lower than what has already been paid."]);
                }

                $payable ??= new OrderPayable(['order_id' => $order->id, 'payee_type' => $type, 'paid_amount' => 0]);
                $payable->amount = $amount;
                if ($type === OrderPayable::TYPE_VENDOR && bccomp((string) $payable->paid_amount, '0', 2) === 0) {
                    $payable->vendor_id = $snapshot->vendor_id;
                }
                $payable->status = $this->status($amount, (string) $payable->paid_amount);
                $payable->save();

                $payables[$type] = $payable;
            }

            return $payables;
        });
    }

    /**
     * Record a payment against one of an order's payables.
     *
     * @param array{payee_type:string,amount:string,paid_on:string,method:string,reference?:?string,notes?:?string} $data
     */
    public function recordPayment(Order $order, array $data, User $user): PayoutPayment
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $payable = OrderPayable::where('order_id', $order->id)
                ->where('payee_type', $data['payee_type'])
                ->lockForUpdate()
                ->first();

            if (!$payable) {
                throw ValidationException::withMessages(['payee_type' => 'Nothing is payable to this payee yet. The order must be paid and have a profit calculation.']);
            }

            $amount = (string) $data['amount'];
            if (bccomp($amount, $payable->balance, 2) > 0) {
                throw ValidationException::withMessages(['amount' => "The amount exceeds the outstanding balance of NPR {$payable->balance}."]);
            }

            $payment = PayoutPayment::create([
                'order_payable_id' => $payable->id,
                'amount' => $amount,
                'paid_on' => $data['paid_on'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->id,
            ]);

            $paid = bcadd((string) $payable->paid_amount, $amount, 2);
            $payable->forceFill(['paid_amount' => $paid, 'status' => $this->status((string) $payable->amount, $paid)])->save();

            FinanceAuditLog::create([
                'order_id' => $order->id,
                'client_id' => $order->client_id,
                'user_id' => $user->id,
                'subject_type' => PayoutPayment::class,
                'subject_id' => $payment->id,
                'action_type' => 'payout_payment_recorded',
                'description' => "{$payable->label} payment of NPR {$amount} recorded for order #{$order->order_number}.",
                'new_values' => ['payee_type' => $payable->payee_type, 'amount' => $amount, 'method' => $data['method'], 'reference' => $data['reference'] ?? null],
                'ip_address' => request()->ip(),
            ]);

            return $payment;
        });
    }

    /**
     * Pay the full outstanding balance of several payables of one payee type at once.
     * All or nothing: any stale or already-settled selection rolls everything back.
     *
     * @param array<int, int|string> $payableIds
     * @param array{paid_on:string,method:string,reference?:?string,notes?:?string} $data
     * @return int number of payables settled
     */
    public function recordBulkPayment(string $payeeType, array $payableIds, array $data, User $user): int
    {
        $ids = array_values(array_unique(array_map('intval', $payableIds)));

        return DB::transaction(function () use ($payeeType, $ids, $data, $user) {
            $payables = OrderPayable::with('order')
                ->where('payee_type', $payeeType)->whereIn('id', $ids)
                ->orderBy('id')->lockForUpdate()->get();

            if ($payables->count() !== count($ids)) {
                throw ValidationException::withMessages(['payable_ids' => 'Some selected payouts do not exist for this payee. Reload the page and try again.']);
            }

            foreach ($payables as $payable) {
                if (bccomp($payable->balance, '0', 2) <= 0) {
                    throw ValidationException::withMessages(['payable_ids' => "Order #{$payable->order->order_number} has nothing outstanding for {$payable->label}. Reload the page and try again."]);
                }

                $this->recordPayment($payable->order, $data + ['payee_type' => $payeeType, 'amount' => $payable->balance], $user);
            }

            return $payables->count();
        });
    }

    private function status(string $amount, string $paid): string
    {
        if (bccomp($paid, '0', 2) === 0) {
            return 'unpaid';
        }

        return bccomp($paid, $amount, 2) >= 0 ? 'paid' : 'partial';
    }
}
