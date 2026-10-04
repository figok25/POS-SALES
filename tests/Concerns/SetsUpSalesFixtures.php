<?php

namespace Tests\Concerns;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Price;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SalesTask;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;

/**
 * Helper fixture untuk test Phase 6 (Sales & Customer) dan Stock, supaya
 * tidak duplikasi setup master data di tiap test class.
 *
 * Multi Branch/Depo: semua fixture di bawah default ke SATU Branch bersama
 * (defaultBranch(), kode 'CBG-TEST') supaya ratusan test HTTP existing yang
 * memanggil makeAdminUser()/makeSalesUser()/makeCustomer()/makeWarehouse()
 * TANPA argumen tetap konsisten & lolos BranchContext (Admin & Sales di
 * Branch yang sama, Customer ikut Branch Sales-nya). Setiap helper
 * menerima parameter `?Branch $branch` OPSIONAL di akhir untuk test
 * skenario lintas-Branch (mis. Admin A vs Branch B) tanpa mengubah
 * pemanggilan lama manapun.
 */
trait SetsUpSalesFixtures
{
    /**
     * Auto-dipanggil oleh Illuminate\Foundation\Testing\TestCase::setUp()
     * (lewat mekanisme setUpTraits) karena mengikuti konvensi nama
     * setUp<NamaTrait>. Menyediakan role & permission (super_admin/admin/
     * sales) yang dibutuhkan assignRole()/middleware role|permission di
     * semua test yang memakai trait ini.
     */
    protected function setUpSetsUpSalesFixtures(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Branch default yang dipakai semua fixture lain kalau tidak diberi
     * Branch eksplisit. firstOrCreate supaya aman dipanggil berkali-kali
     * dalam satu test tanpa duplikat.
     */
    protected function defaultBranch(): Branch
    {
        return $this->makeBranch();
    }

    /**
     * Buat (atau ambil) Branch tertentu - dipakai langsung oleh test yang
     * butuh DUA Branch berbeda untuk skenario lintas-Branch (Scenario A-G
     * di blueprint Multi Branch/Depo), mis.:
     *   $branchA = $this->makeBranch('CBG-A', 'Cabang A');
     *   $branchB = $this->makeBranch('CBG-B', 'Cabang B');
     */
    protected function makeBranch(string $code = 'CBG-TEST', ?string $name = null): Branch
    {
        $company = Company::firstOrCreate(['code' => 'HO-TEST'], ['name' => 'PT Test']);

        return Branch::firstOrCreate(
            ['code' => $code],
            ['company_id' => $company->id, 'name' => $name ?? "Cabang {$code}", 'is_active' => true]
        );
    }

    protected function makeProduct(float $price = 10000): Product
    {
        static $counter = 0;
        $counter++;

        $category = Category::firstOrCreate(['code' => 'GEN'], ['name' => 'Umum']);
        $unit = Unit::firstOrCreate(['symbol' => 'PCS'], ['name' => 'Pieces']);

        $product = Product::create([
            'sku' => "SKU-TEST-{$counter}",
            'name' => "Produk Test {$counter}",
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        Price::create([
            'product_id' => $product->id,
            'name' => 'Harga Umum',
            'amount' => $price,
            'is_active' => true,
        ]);

        return $product;
    }

    protected function makeSales(?User $user = null, ?Branch $branch = null): Sales
    {
        static $counter = 0;
        $counter++;

        $branch ??= $this->defaultBranch();

        return Sales::create([
            'branch_id' => $branch->id,
            'user_id' => $user?->id,
            'code' => "SLS-TEST-{$counter}",
            'name' => "Sales Test {$counter}",
            'is_active' => true,
        ]);
    }

    protected function makeWarehouse(?Branch $branch = null): Warehouse
    {
        static $counter = 0;
        $counter++;

        $branch ??= $this->defaultBranch();

        return Warehouse::create([
            'branch_id' => $branch->id,
            'code' => "WH-TEST-{$counter}",
            'name' => "Gudang Test {$counter}",
            'is_active' => true,
        ]);
    }

    /**
     * Multi Branch/Depo: branch_id WAJIB terisi (kolom sudah ada di
     * schema sejak migration 2026_10_03_000002) - diturunkan dari Sales
     * pemilik ($salesId) kalau diberi, supaya Customer & Sales-nya selalu
     * konsisten satu Branch (persis aturan CustomerRequest/
     * BkbDistribusiRequest dkk di kode asli). Kalau $salesId tidak
     * diberi (Customer belum di-assign), fallback ke defaultBranch()
     * atau $branch eksplisit.
     */
    protected function makeCustomer(?int $salesId = null, ?Branch $branch = null): Customer
    {
        static $counter = 0;
        $counter++;

        $branch ??= $salesId ? Sales::find($salesId)?->branch : null;
        $branch ??= $this->defaultBranch();

        return Customer::create([
            'branch_id' => $branch->id,
            'sales_id' => $salesId,
            'code' => "CUST-TEST-{$counter}",
            'name' => "Toko Test {$counter}",
            'is_active' => true,
        ]);
    }

    protected function makeSalesUser(?Branch $branch = null): array
    {
        $branch ??= $this->defaultBranch();

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('sales');
        $sales = $this->makeSales($user, $branch);

        return [$user, $sales];
    }

    protected function makeAdminUser(?Branch $branch = null): User
    {
        $branch ??= $this->defaultBranch();

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('admin');

        return $user;
    }

    /**
     * Multi Branch/Depo: Super Admin global - branch_id SELALU NULL
     * (lihat User::isSuperAdmin(), BranchContext::resolveForUser()).
     */
    protected function makeSuperAdminUser(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        $user->assignRole('super_admin');

        return $user;
    }

    /**
     * Live Sales Field Operations (Blueprint #14) - buat SalesTask untuk
     * hari ini dengan status tertentu. Default 'working' supaya gate
     * middleware active_sales_task lolos di test yang tidak sedang
     * menguji gate itu sendiri.
     */
    protected function makeSalesTask(Sales $sales, string $status = SalesTask::STATUS_WORKING): SalesTask
    {
        static $counter = 0;
        $counter++;

        return SalesTask::create([
            'code' => "TASK-TEST-{$counter}",
            'sales_id' => $sales->id,
            'branch_id' => $sales->branch_id,
            'task_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }
}
