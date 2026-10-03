<?php

namespace App\Policies;

use App\Models\DistributorPayoutBatch;
use App\Models\User;

class DistributorPayoutBatchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('manage_finance');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DistributorPayoutBatch $distributorPayoutBatch): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DistributorPayoutBatch $distributorPayoutBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DistributorPayoutBatch $distributorPayoutBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DistributorPayoutBatch $distributorPayoutBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DistributorPayoutBatch $distributorPayoutBatch): bool
    {
        return false;
    }
}
