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
        $this->call(RolePermissionSeeder::class);
        $this->call(MasterDataSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@possales.test',
        ]);
        $admin->assignRole('admin');

        $sales = User::factory()->create([
            'name' => 'Sales Demo',
            'email' => 'sales@possales.test',
        ]);
        $sales->assignRole('sales');
    }
}
