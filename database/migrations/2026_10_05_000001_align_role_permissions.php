<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $manageBlog = Permission::firstOrCreate(
            ['name' => 'manage_blog'],
            ['display_name' => 'Manage Blog', 'description' => 'Create, edit, and delete blog posts']
        );

        $permissions = [
            'view_dashboard' => Permission::firstOrCreate(
                ['name' => 'view_dashboard'],
                ['display_name' => 'View Dashboard', 'description' => 'Access admin dashboard']
            )->id,
            'view_finance' => Permission::firstOrCreate(
                ['name' => 'view_finance'],
                ['display_name' => 'View Finance', 'description' => 'View finance dashboards and financial status.']
            )->id,
            'record_payments' => Permission::firstOrCreate(
                ['name' => 'record_payments'],
                ['display_name' => 'Record Payments', 'description' => 'Record pending finance payments.']
            )->id,
            'confirm_payments' => Permission::firstOrCreate(
                ['name' => 'confirm_payments'],
                ['display_name' => 'Confirm Payments', 'description' => 'Confirm payments and issue refunds.']
            )->id,
        ];

        foreach (['super_admin', 'admin', 'editor'] as $roleName) {
            Role::where('name', $roleName)->first()?->permissions()->syncWithoutDetaching([$manageBlog->id]);
        }

        Role::where('name', 'operations_staff')->first()?->permissions()->syncWithoutDetaching([
            $permissions['view_dashboard'],
        ]);

        Role::where('name', 'finance')->first()?->permissions()->syncWithoutDetaching([
            $permissions['view_dashboard'],
            $permissions['view_finance'],
            $permissions['record_payments'],
            $permissions['confirm_payments'],
        ]);
    }

    public function down(): void
    {
        Permission::where('name', 'manage_blog')->delete();
    }
};
