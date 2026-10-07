<?php

namespace Tests\Feature\Inventory;

use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Menu Admin > Inventory > Stock hanya menampilkan stok Warehouse.
 * Stok yang dibawa Sales tidak muncul di sini (termasuk barang Return
 * Stock/BTB, yang tampil sebagai tambahan stok Warehouse). Halaman Stok di
 * aplikasi Sales tidak berubah.
 */
class AdminStockViewTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_admin_stock_menu_lists_only_warehouse_stock(): void
    {
        $branch = $this->makeBranch('CBG-S', 'Depo S');
        $warehouse = $this->makeWarehouse($branch);
        [, $sales] = $this->makeSalesUser($branch);

        $inWarehouse = $this->makeProduct();
        $onlyWithSales = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($inWarehouse->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'adjustment');
        $stock->increase($onlyWithSales->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');

        $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.inventory.stock.index'))
            ->assertOk()
            ->assertSee($inWarehouse->name)
            ->assertDontSee($onlyWithSales->name);
    }

    public function test_sales_location_cannot_be_requested_via_query_string(): void
    {
        $branch = $this->makeBranch('CBG-S', 'Depo S');
        $warehouse = $this->makeWarehouse($branch);
        [, $sales] = $this->makeSalesUser($branch);

        $inWarehouse = $this->makeProduct();
        $onlyWithSales = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($inWarehouse->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'adjustment');
        $stock->increase($onlyWithSales->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');

        // Filter lama ?location_type=sales tidak lagi punya efek.
        $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.inventory.stock.index', ['location_type' => 'sales']))
            ->assertOk()
            ->assertSee($inWarehouse->name)
            ->assertDontSee($onlyWithSales->name);
    }

    public function test_returned_goods_show_up_as_warehouse_stock_not_sales_stock(): void
    {
        $branch = $this->makeBranch('CBG-S', 'Depo S');
        $warehouse = $this->makeWarehouse($branch);
        [, $sales] = $this->makeSalesUser($branch);
        $product = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($product->id, Stock::LOCATION_SALES, $sales->id, 8, 'bkb_apply');
        // Seperti Apply BTB: Sales Stock -> Warehouse Stock.
        $stock->transfer(
            $product->id, Stock::LOCATION_SALES, $sales->id,
            Stock::LOCATION_WAREHOUSE, $warehouse->id,
            8, 'btb_apply', 'btb_test', 1,
        );

        $response = $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.inventory.stock.index'))
            ->assertOk();

        $response->assertSee($product->name);
        $this->assertCount(1, $response->viewData('items')->items());
        $this->assertSame(Stock::LOCATION_WAREHOUSE, $response->viewData('items')->items()[0]->location_type);
    }

    public function test_depo_isolation_still_applies_and_super_admin_sees_all_warehouses(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $whA = $this->makeWarehouse($branchA);
        $whB = $this->makeWarehouse($branchB);

        $productA = $this->makeProduct();
        $productB = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($productA->id, Stock::LOCATION_WAREHOUSE, $whA->id, 3, 'adjustment');
        $stock->increase($productB->id, Stock::LOCATION_WAREHOUSE, $whB->id, 4, 'adjustment');

        $this->actingAs($this->makeAdminUser($branchA))
            ->get(route('admin.inventory.stock.index'))
            ->assertOk()
            ->assertSee($productA->name)
            ->assertDontSee($productB->name);

        $this->actingAs($this->makeSuperAdminUser())
            ->get(route('admin.inventory.stock.index'))
            ->assertOk()
            ->assertSee($productA->name)
            ->assertSee($productB->name);
    }

    public function test_sales_app_stock_page_is_unchanged(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales); // gate active_sales_task
        $product = $this->makeProduct();

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 6, 'bkb_apply');

        $this->actingAs($user)
            ->get(route('sales.stock.index'))
            ->assertOk()
            ->assertSee($product->name);
    }
}
