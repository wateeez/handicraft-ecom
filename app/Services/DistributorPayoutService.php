<?php

namespace App\Services;

use App\Models\DistributorPayable;
use App\Models\DistributorPayoutBatch;
use App\Models\DistributorPayoutItem;
use App\Models\FinanceAuditLog;
use App\Models\FinanceOrderSnapshot;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributorPayoutService
{
    public function createPayable(Order $order): DistributorPayable
    {
        return DB::transaction(function () use ($order) {
            $snapshot = FinanceOrderSnapshot::where('order_id', $order->id)->lockForUpdate()->firstOrFail();

            return DistributorPayable::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'distributor_id' => $snapshot->distributor_id,
                    'currency' => $snapshot->currency,
                    'original_amount' => $snapshot->distributor_share,
                    'adjusted_amount' => $snapshot->distributor_share,
                    'status' => 'not_due',
                ]
            );
        });
    }

    public function settle(array $data, User $user): DistributorPayoutBatch
    {
        return DB::transaction(function () use ($data, $user) {
            $batch = DistributorPayoutBatch::create([
                'distributor_id' => $data['distributor_id'],
                'recorded_by' => $user->id,
                'approved_by' => $user->id,
                'paid_on' => $data['paid_on'],
                'currency' => $data['currency'],
                'amount' => 0,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'status' => 'paid',
            ]);

            $total = 0.0;
            foreach ($data['allocations'] as $allocation) {
                $payable = DistributorPayable::lockForUpdate()->findOrFail($allocation['payable_id']);
                $amount = (float) $allocation['amount'];
                $outstanding = (float) $payable->adjusted_amount - (float) $payable->paid_amount;

                if ($payable->distributor_id !== (int) $data['distributor_id'] || $amount <= 0 || $amount > $outstanding) {
                    throw ValidationException::withMessages(['allocations' => 'An allocation is invalid or exceeds its outstanding payable balance.']);
                }

                DistributorPayoutItem::create([
                    'distributor_payout_batch_id' => $batch->id,
                    'distributor_payable_id' => $payable->id,
                    'amount' => $amount,
                ]);

                $payable->paid_amount += $amount;
                $payable->status = $payable->paid_amount >= $payable->adjusted_amount ? 'paid' : 'due';
                $payable->save();
                $total += $amount;
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['allocations' => 'A payout requires at least one positive allocation.']);
            }

            $batch->update(['amount' => $total]);
            FinanceAuditLog::create([
                'user_id' => $user->id,
                'subject_type' => DistributorPayoutBatch::class,
                'subject_id' => $batch->id,
                'action_type' => 'distributor_payout_recorded',
                'description' => "Distributor payout batch #{$batch->id} recorded.",
                'new_values' => ['amount' => $total, 'allocation_count' => count($data['allocations'])],
                'ip_address' => request()->ip(),
            ]);

            return $batch->fresh('items');
        });
    }
}
