<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Phase 2 - Master Data sample seed, cukup untuk mencoba alur end-to-end
 * pada Fase 3+ (Stock, BKB/BTB, Sales Transaction).
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

        Product::firstOrCreate(
            ['sku' => 'SKU-0001'],
            [
                'name' => 'Produk Contoh',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
            ]
        );

        Sales::firstOrCreate(
            ['code' => 'SLS-0001'],
            ['branch_id' => $branch->id, 'name' => 'Sales Demo']
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
