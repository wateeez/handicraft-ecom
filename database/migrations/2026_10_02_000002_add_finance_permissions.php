<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            'view_finance' => ['View Finance', 'View finance dashboards and financial status.'],
            'record_payments' => ['Record Payments', 'Record pending finance payments.'],
            'confirm_payments' => ['Confirm Payments', 'Confirm payments and issue refunds.'],
            'manage_finance' => ['Manage Finance', 'Manage finance settings, commissions, and payouts.'],
        ];

        $ids = [];
        foreach ($permissions as $name => [$displayName, $description]) {
            $ids[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'description' => $description]
            )->id;
        }

        foreach (['super_admin', 'admin'] as $roleName) {
            Role::where('name', $roleName)->first()?->permissions()->syncWithoutDetaching(array_values($ids));
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['view_finance', 'record_payments', 'confirm_payments', 'manage_finance'])->delete();
    }
};
