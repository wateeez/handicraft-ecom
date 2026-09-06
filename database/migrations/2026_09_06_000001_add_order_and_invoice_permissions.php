<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing permissions
        $manageOrders = Permission::firstOrCreate(
            ['name' => 'manage_orders'],
            ['display_name' => 'Manage Orders', 'description' => 'Create, edit, update status, and mark orders as paid']
        );

        $manageInvoices = Permission::firstOrCreate(
            ['name' => 'manage_invoices'],
            ['display_name' => 'Manage Invoices', 'description' => 'Create, issue, and void invoices']
        );

        $viewOrders = Permission::firstOrCreate(
            ['name' => 'view_orders'],
            ['display_name' => 'View Orders', 'description' => 'View orders list and details']
        );

        // Assign to roles
        // super_admin & admin get full order management
        foreach (['super_admin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching([
                    $manageOrders->id,
                    $manageInvoices->id,
                    $viewOrders->id,
                ]);
            }
        }

        // editor & viewer get view_orders only
        foreach (['editor', 'viewer'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching([
                    $viewOrders->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['manage_orders', 'manage_invoices', 'view_orders'])->delete();
    }
};
