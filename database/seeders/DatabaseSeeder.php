<?php

namespace Database\Seeders;

use App\Models\Sales;
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

        // Fase 6 - hubungkan user login Sales Demo ke master Sales
        // (SLS-0001) yang dibuat MasterDataSeeder, agar Sales App bisa
        // resolve "current sales" dari user yang login (Blueprint #15).
        Sales::where('code', 'SLS-0001')->update(['user_id' => $sales->id]);
    }
}
