<?php

namespace Database\Seeders;

use App\Models\Branch;
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

        // Multi Branch/Depo: MasterDataSeeder sudah membuat Branch ini
        // ('Cabang Pusat') - dipakai ulang di sini (bukan dibuat baru)
        // supaya Admin & Sales demo konsisten satu Branch yang sama.
        $branch = Branch::where('code', 'CBG-PST')->firstOrFail();

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@possales.test',
            // branch_id SENGAJA tidak diisi (NULL) - Super Admin bersifat
            // global, lihat User::isSuperAdmin() & BranchContext.
        ]);
        $superAdmin->assignRole('super_admin');

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@possales.test',
            'branch_id' => $branch->id,
        ]);
        $admin->assignRole('admin');

        $sales = User::factory()->create([
            'name' => 'Sales Demo',
            'email' => 'sales@possales.test',
            'branch_id' => $branch->id,
        ]);
        $sales->assignRole('sales');

        // Fase 6 - hubungkan user login Sales Demo ke master Sales
        // (SLS-0001) yang dibuat MasterDataSeeder, agar Sales App bisa
        // resolve "current sales" dari user yang login (Blueprint #15).
        Sales::where('code', 'SLS-0001')->update(['user_id' => $sales->id]);
    }
}
