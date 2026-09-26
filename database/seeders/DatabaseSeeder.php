<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DemoUsersSeeder::class,
            OmsSeeder::class,
        ]);

        if (!User::where('email', 'admin@ecom.com')->exists()) {
            User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'admin@ecom.com',
                'password' => 'password',
                'is_active' => true,
            ]);
        }
    }
}
