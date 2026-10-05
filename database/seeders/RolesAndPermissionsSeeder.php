<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            [
                'name' => 'manage_users',
                'display_name' => 'Manage Users',
                'description' => 'Create, edit, delete, and manage admin users'
            ],
            [
                'name' => 'manage_roles',
                'display_name' => 'Manage Roles',
                'description' => 'Create, edit, and manage roles and permissions'
            ],
            [
                'name' => 'manage_products',
                'display_name' => 'Manage Products',
                'description' => 'Create, edit, and delete products'
            ],
            [
                'name' => 'view_products',
                'display_name' => 'View Products',
                'description' => 'View products list and details'
            ],
            [
                'name' => 'manage_categories',
                'display_name' => 'Manage Categories',
                'description' => 'Create, edit, and delete categories'
            ],
            [
                'name' => 'view_categories',
                'display_name' => 'View Categories',
                'description' => 'View categories list and details'
            ],
            [
                'name' => 'manage_inquiries',
                'display_name' => 'Manage Inquiries',
                'description' => 'Reply to and manage customer inquiries'
            ],
            [
                'name' => 'view_inquiries',
                'display_name' => 'View Inquiries',
                'description' => 'View customer inquiries'
            ],
            [
                'name' => 'manage_shipping',
                'display_name' => 'Manage Shipping',
                'description' => 'Manage shipping zones, providers, and rates'
            ],
            [
                'name' => 'view_shipping',
                'display_name' => 'View Shipping',
                'description' => 'View shipping settings'
            ],
            [
                'name' => 'manage_settings',
                'display_name' => 'Manage Settings',
                'description' => 'Manage system settings and configuration'
            ],
            [
                'name' => 'view_dashboard',
                'display_name' => 'View Dashboard',
                'description' => 'Access admin dashboard'
            ],
            [
                'name' => 'manage_blog',
                'display_name' => 'Manage Blog',
                'description' => 'Create, edit, and delete blog posts'
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Create Roles
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Has full control over the system'
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Admin',
                'description' => 'Can manage products, categories, and inquiries'
            ]
        );

        $editorRole = Role::firstOrCreate(
            ['name' => 'editor'],
            [
                'display_name' => 'Editor',
                'description' => 'Can edit products and categories but cannot delete'
            ]
        );

        $viewerRole = Role::firstOrCreate(
            ['name' => 'viewer'],
            [
                'display_name' => 'Viewer',
                'description' => 'Can only view data, no editing permissions'
            ]
        );

        // Assign all permissions to Super Admin
        $superAdminRole->permissions()->sync(Permission::all());

        // Assign permissions to Admin (including user/role management)
        $adminRole->permissions()->sync(
            Permission::whereIn('name', [
                'manage_users',
                'manage_roles',
                'manage_products',
                'manage_categories',
                'manage_inquiries',
                'manage_shipping',
                'view_products',
                'view_categories',
                'view_inquiries',
                'view_shipping',
                'view_dashboard',
                'manage_blog',
            ])->pluck('id')
        );

        // Assign permissions to Editor
        $editorRole->permissions()->sync(
            Permission::whereIn('name', [
                'manage_products',
                'manage_categories',
                'view_products',
                'view_categories',
                'view_inquiries',
                'view_dashboard',
                'manage_blog',
            ])->pluck('id')
        );

        // Assign permissions to Viewer
        $viewerRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_products',
                'view_categories',
                'view_inquiries',
                'view_shipping',
                'view_dashboard',
            ])->pluck('id')
        );

        // Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ecom.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Assign Super Admin role
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole($superAdminRole);
        }

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Super Admin created:');
        $this->command->info('Email: admin@ecom.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login!');
    }
}
