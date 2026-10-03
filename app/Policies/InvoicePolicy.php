<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('view_finance') || $user->hasPermission('manage_invoices');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('manage_invoices');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->isEditable() && $this->create($user);
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'draft' && $this->create($user);
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $invoice->isVoidable() && ($user->isSuperAdmin() || $user->hasPermission('void_invoices'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
