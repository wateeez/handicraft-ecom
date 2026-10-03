<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientLoyaltyAssignment;
use App\Models\FinanceAuditLog;
use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function __construct(private readonly FinanceInsightService $insights)
    {
    }

    public function assignAutomatically(Client $client): ?ClientLoyaltyAssignment
    {
        $summary = $this->insights->clientSummary($client);
        $tier = LoyaltyTier::query()
            ->where('is_active', true)
            ->orderByDesc('rank')
            ->get()
            ->first(function (LoyaltyTier $tier) use ($summary) {
                return match ($tier->qualification_basis) {
                    'order_count' => $summary['total_orders'] >= $tier->threshold_order_count,
                    default => $summary['lifetime_spend'] >= (float) $tier->threshold_amount,
                };
            });

        if (!$tier) {
            return null;
        }

        return DB::transaction(function () use ($client, $tier) {
            $current = $client->currentLoyaltyAssignment;
            if ($current?->loyalty_tier_id === $tier->id) {
                return $current;
            }

            if ($current) {
                $current->update(['ended_at' => now()]);
            }

            $assignment = ClientLoyaltyAssignment::create([
                'client_id' => $client->id,
                'loyalty_tier_id' => $tier->id,
                'source' => 'automatic',
                'reason' => 'Threshold recalculated from confirmed finance records.',
                'effective_at' => now(),
            ]);
            FinanceAuditLog::create([
                'client_id' => $client->id,
                'subject_type' => ClientLoyaltyAssignment::class,
                'subject_id' => $assignment->id,
                'action_type' => 'client_tier_changed',
                'description' => "Client tier changed to {$tier->name}.",
                'new_values' => ['loyalty_tier_id' => $tier->id, 'source' => 'automatic'],
                'ip_address' => request()->ip(),
            ]);

            return $assignment;
        });
    }
}
