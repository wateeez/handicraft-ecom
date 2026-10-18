<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class OmsSeeder extends Seeder
{
    public function run(): void
    {
        // New OMS permissions
        $permissions = [
            ['name' => 'manage_orders', 'display_name' => 'Manage Orders', 'description' => 'Create, edit, and update order status'],
            ['name' => 'override_order_status', 'display_name' => 'Override Order Status', 'description' => 'Skip workflow steps in order status'],
            ['name' => 'manage_invoices', 'display_name' => 'Manage Invoices', 'description' => 'Generate and issue invoices'],
            ['name' => 'void_invoices', 'display_name' => 'Void Invoices', 'description' => 'Void issued invoices'],
            ['name' => 'merge_orders', 'display_name' => 'Merge Orders', 'description' => 'Merge multiple orders into one'],
            ['name' => 'manage_clients', 'display_name' => 'Manage Clients', 'description' => 'Create and edit client profiles'],
            ['name' => 'view_orders', 'display_name' => 'View Orders', 'description' => 'View orders list and details'],
            ['name' => 'view_clients', 'display_name' => 'View Clients', 'description' => 'View client profiles'],
            ['name' => 'view_dashboard', 'display_name' => 'View Dashboard', 'description' => 'Access admin dashboard'],
            ['name' => 'view_finance', 'display_name' => 'View Finance', 'description' => 'View finance dashboards and financial status.'],
            ['name' => 'record_payments', 'display_name' => 'Record Payments', 'description' => 'Record pending finance payments.'],
            ['name' => 'confirm_payments', 'display_name' => 'Confirm Payments', 'description' => 'Confirm payments and issue refunds.'],
            ['name' => 'manage_finance', 'display_name' => 'Manage Finance', 'description' => 'Manage finance settings, commissions, and payouts.'],
            ['name' => 'view_audit_logs', 'display_name' => 'View Audit Logs', 'description' => 'Read-only access to audit trail'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Assign to roles
        $rolePermissions = [
            'super_admin' => ['view_orders', 'manage_orders', 'override_order_status', 'manage_invoices', 'void_invoices', 'merge_orders', 'view_clients', 'manage_clients', 'view_audit_logs', 'view_finance', 'record_payments', 'confirm_payments', 'manage_finance'],
            'admin' => ['view_orders', 'manage_orders', 'manage_invoices', 'merge_orders', 'view_clients', 'manage_clients', 'view_audit_logs'],
        ];

        // Create operations_staff and finance roles if not exist
        $opsRole = Role::firstOrCreate(
            ['name' => 'operations_staff'],
            ['display_name' => 'Operations Staff', 'description' => 'Handles order processing and client management']
        );
        $finRole = Role::firstOrCreate(
            ['name' => 'finance'],
            ['display_name' => 'Finance', 'description' => 'Handles invoices and financial records']
        );

        $rolePermissions['operations_staff'] = ['view_orders', 'manage_orders', 'view_clients', 'manage_clients', 'view_dashboard'];
        $rolePermissions['finance'] = [
            'manage_invoices',
            'void_invoices',
            'view_audit_logs',
            'view_dashboard',
            'view_finance',
            'record_payments',
            'confirm_payments',
            'manage_finance',
            'view_orders',
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if (!$role)
                continue;

            foreach ($perms as $permName) {
                $permission = Permission::where('name', $permName)->first();
                if ($permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $this->command->info('OMS permissions and roles seeded.');
    }
}
