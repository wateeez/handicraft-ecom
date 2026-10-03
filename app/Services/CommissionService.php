<?php

namespace App\Services;

use App\Models\CommissionEntry;
use App\Models\CommissionPayoutBatch;
use App\Models\CommissionPayoutItem;
use App\Models\FinanceAuditLog;
use App\Models\FinanceOrderSnapshot;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public function calculate(Order $order): CommissionEntry
    {
        return DB::transaction(function () use ($order) {
            $snapshot = FinanceOrderSnapshot::where('order_id', $order->id)->lockForUpdate()->firstOrFail();
            $amount = $snapshot->commission_amount;

            return CommissionEntry::updateOrCreate(
                ['order_id' => $order->id, 'recipient_id' => null],
                [
                    'currency' => $snapshot->currency,
                    'original_amount' => $amount,
                    'adjusted_amount' => $amount,
                    'basis_snapshot' => $snapshot->financial_snapshot,
                    'status' => 'pending',
                ]
            );
        });
    }

    public function settle(array $data, User $user): CommissionPayoutBatch
    {
        return DB::transaction(function () use ($data, $user) {
            $batch = CommissionPayoutBatch::create([
                'recipient_id' => $data['recipient_id'],
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
                $entry = CommissionEntry::lockForUpdate()->findOrFail($allocation['commission_entry_id']);
                $amount = (float) $allocation['amount'];
                $outstanding = (float) $entry->adjusted_amount - (float) $entry->paid_amount;

                if ($entry->recipient_id !== (int) $data['recipient_id'] || $amount <= 0 || $amount > $outstanding) {
                    throw ValidationException::withMessages(['allocations' => 'An allocation is invalid or exceeds its outstanding commission balance.']);
                }

                CommissionPayoutItem::create([
                    'commission_payout_batch_id' => $batch->id,
                    'commission_entry_id' => $entry->id,
                    'amount' => $amount,
                ]);
                $entry->paid_amount += $amount;
                $entry->status = $entry->paid_amount >= $entry->adjusted_amount ? 'paid' : 'payable';
                $entry->paid_at = $entry->status === 'paid' ? now() : null;
                $entry->save();
                $total += $amount;
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['allocations' => 'A commission payout requires at least one positive allocation.']);
            }

            $batch->update(['amount' => $total]);
            FinanceAuditLog::create([
                'user_id' => $user->id,
                'subject_type' => CommissionPayoutBatch::class,
                'subject_id' => $batch->id,
                'action_type' => 'commission_payout_recorded',
                'description' => "Commission payout batch #{$batch->id} recorded.",
                'new_values' => ['amount' => $total, 'allocation_count' => count($data['allocations'])],
                'ip_address' => request()->ip(),
            ]);

            return $batch->fresh('items');
        });
    }
}
