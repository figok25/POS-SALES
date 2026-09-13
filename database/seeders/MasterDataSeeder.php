<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Price;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Phase 2 - Master Data sample seed, cukup untuk mencoba alur end-to-end
 * pada Fase 3+ (Stock, BKB/BTB) dan Fase 6 (Sales Transaction, Invoice).
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'HO'],
            ['name' => 'PT Contoh Sejahtera']
        );

        $branch = Branch::firstOrCreate(
            ['code' => 'CBG-PST'],
            ['company_id' => $company->id, 'name' => 'Cabang Pusat']
        );

        Warehouse::firstOrCreate(
            ['code' => 'WH-PST'],
            ['branch_id' => $branch->id, 'name' => 'Gudang Pusat']
        );

        $category = Category::firstOrCreate(['code' => 'GEN'], ['name' => 'Umum']);
        $unit = Unit::firstOrCreate(['symbol' => 'PCS'], ['name' => 'Pieces']);

        $product = Product::firstOrCreate(
            ['sku' => 'SKU-0001'],
            [
                'name' => 'Produk Contoh',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
            ]
        );

        // Fase 6 - Harga aktif diperlukan agar Sales Transaction Service
        // bisa memvalidasi harga (Blueprint #22 - Validate Price).
        Price::firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Harga Umum'],
            ['amount' => 15000, 'is_active' => true]
        );

        $sales = Sales::firstOrCreate(
            ['code' => 'SLS-0001'],
            ['branch_id' => $branch->id, 'name' => 'Sales Demo']
        );

        // Fase 6 - Customer contoh yang sudah ditugaskan ke Sales Demo,
        // agar alur Kunjungan & Sales Transaction bisa langsung dicoba
        // tanpa harus melalui Tagging Toko terlebih dahulu.
        Customer::firstOrCreate(
            ['code' => 'CUST-0001'],
            [
                'sales_id' => $sales->id,
                'name' => 'Toko Sumber Rejeki',
                'address' => 'Jl. Contoh No. 1',
                'phone' => '081200000000',
                'is_active' => true,
            ]
        );

        Vehicle::firstOrCreate(
            ['code' => 'VHC-0001'],
            ['name' => 'Truk Box', 'plate_number' => 'B 1234 XYZ']
        );

        Supplier::firstOrCreate(
            ['code' => 'SUP-0001'],
            ['name' => 'Supplier Contoh']
        );
    }
}
