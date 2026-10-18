<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view_dashboard' => ['View Dashboard', 'Access admin dashboard'],
        'view_orders' => ['View Orders', 'View orders list and details'],
        'manage_orders' => ['Manage Orders', 'Create, edit, and update order status'],
        'manage_invoices' => ['Manage Invoices', 'Generate and issue invoices'],
        'void_invoices' => ['Void Invoices', 'Void issued invoices'],
        'merge_orders' => ['Merge Orders', 'Merge multiple orders into one'],
        'view_clients' => ['View Clients', 'View client profiles'],
        'manage_clients' => ['Manage Clients', 'Create and edit client profiles'],
        'view_finance' => ['View Finance', 'View finance dashboards and financial status.'],
        'record_payments' => ['Record Payments', 'Record pending finance payments.'],
        'confirm_payments' => ['Confirm Payments', 'Confirm payments and issue refunds.'],
        'manage_finance' => ['Manage Finance', 'Manage finance settings, commissions, and payouts.'],
        'manage_blog' => ['Manage Blog', 'Create, edit, and delete blog posts'],
    ];

    private const ROLE_PERMISSIONS = [
        'admin' => ['view_orders', 'manage_orders', 'manage_invoices', 'merge_orders', 'view_clients', 'manage_clients'],
        'operations_staff' => ['view_dashboard', 'view_orders', 'manage_orders', 'view_clients', 'manage_clients'],
        'finance' => ['view_dashboard', 'view_orders', 'view_finance', 'record_payments', 'confirm_payments', 'manage_finance', 'manage_invoices', 'void_invoices'],
        'editor' => ['view_orders', 'view_clients'],
        'viewer' => ['view_orders', 'view_clients'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name => [$display, $description]) {
            Permission::firstOrCreate(['name' => $name], ['display_name' => $display, 'description' => $description]);
        }

        // Super admin always holds every permission
        Role::where('name', 'super_admin')->first()?->permissions()->sync(Permission::pluck('id'));

        foreach (self::ROLE_PERMISSIONS as $roleName => $names) {
            Role::where('name', $roleName)->first()?->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', $names)->pluck('id')
            );
        }
    }

    public function down(): void
    {
        Permission::where('name', 'view_clients')->delete();
    }
};
