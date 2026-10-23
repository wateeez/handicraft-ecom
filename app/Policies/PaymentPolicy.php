<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('view_finance');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('record_payments');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        return $payment->status === 'pending' && $this->create($user);
    }

    /**
     * Reversing is the only way to undo a confirmed payment, so it needs the
     * confirm_payments permission rather than the pending-only update rule.
     */
    public function reverse(User $user, Payment $payment): bool
    {
        return $payment->status === 'confirmed'
            && ($user->isSuperAdmin() || $user->hasPermission('confirm_payments'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
