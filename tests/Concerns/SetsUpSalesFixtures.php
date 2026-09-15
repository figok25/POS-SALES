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
 */
trait SetsUpSalesFixtures
{
    /**
     * Auto-dipanggil oleh Illuminate\Foundation\Testing\TestCase::setUp()
     * (lewat mekanisme setUpTraits) karena mengikuti konvensi nama
     * setUp<NamaTrait>. Menyediakan role & permission (admin/sales)
     * yang dibutuhkan assignRole()/middleware role|permission di semua
     * test yang memakai trait ini.
     */
    protected function setUpSetsUpSalesFixtures(): void
    {
        $this->seed(RolePermissionSeeder::class);
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

    protected function makeSales(?User $user = null): Sales
    {
        static $counter = 0;
        $counter++;

        $company = Company::firstOrCreate(['code' => 'HO-TEST'], ['name' => 'PT Test']);
        $branch = Branch::firstOrCreate(
            ['code' => 'CBG-TEST'],
            ['company_id' => $company->id, 'name' => 'Cabang Test']
        );

        return Sales::create([
            'branch_id' => $branch->id,
            'user_id' => $user?->id,
            'code' => "SLS-TEST-{$counter}",
            'name' => "Sales Test {$counter}",
            'is_active' => true,
        ]);
    }

    protected function makeWarehouse(): Warehouse
    {
        static $counter = 0;
        $counter++;

        $company = Company::firstOrCreate(['code' => 'HO-TEST'], ['name' => 'PT Test']);
        $branch = Branch::firstOrCreate(
            ['code' => 'CBG-TEST'],
            ['company_id' => $company->id, 'name' => 'Cabang Test']
        );

        return Warehouse::create([
            'branch_id' => $branch->id,
            'code' => "WH-TEST-{$counter}",
            'name' => "Gudang Test {$counter}",
            'is_active' => true,
        ]);
    }

    protected function makeCustomer(?int $salesId = null): Customer
    {
        static $counter = 0;
        $counter++;

        return Customer::create([
            'sales_id' => $salesId,
            'code' => "CUST-TEST-{$counter}",
            'name' => "Toko Test {$counter}",
            'is_active' => true,
        ]);
    }

    protected function makeSalesUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('sales');
        $sales = $this->makeSales($user);

        return [$user, $sales];
    }

    protected function makeAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

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
