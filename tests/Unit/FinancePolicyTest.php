<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use PHPUnit\Framework\TestCase;

class FinancePolicyTest extends TestCase
{
    public function test_payment_policy_allows_only_pending_payment_updates(): void
    {
        $user = new class extends User {
            public function hasPermission(string $permission): bool { return $permission === 'record_payments'; }
            public function isSuperAdmin(): bool { return false; }
        };
        $policy = new PaymentPolicy();

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, new Payment(['status' => 'pending'])));
        $this->assertFalse($policy->update($user, new Payment(['status' => 'confirmed'])));
    }

    public function test_invoice_policy_allows_only_draft_updates(): void
    {
        $user = new class extends User {
            public function hasPermission(string $permission): bool { return $permission === 'manage_invoices'; }
            public function isSuperAdmin(): bool { return false; }
        };
        $policy = new InvoicePolicy();

        $this->assertTrue($policy->update($user, new Invoice(['status' => 'draft'])));
        $this->assertFalse($policy->update($user, new Invoice(['status' => 'issued'])));
    }
}
