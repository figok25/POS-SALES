<?php

namespace Tests\Feature\Admin;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Admin boleh mengoreksi stok yang dibawa Sales (mis. barang program tertinggal).
 */
class SalesStockCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function seedSalesStock(float $qty, $branch = null): array
    {
        [, $sales] = $this->makeSalesUser($branch);
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, $qty, 'bkb_apply');

        return [$sales, $product, Stock::where('location_type', Stock::LOCATION_SALES)->where('location_id', $sales->id)->firstOrFail()];
    }

    public function test_admin_sees_sales_stock_page_and_can_correct_quantity(): void
    {
        [$sales, $product, $stock] = $this->seedSalesStock(6);
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->get(route('admin.inventory.stock.sales'))
            ->assertOk()->assertSee($sales->name)->assertSee($product->name);

        $this->actingAs($admin)->put(route('admin.inventory.stock.sales.update', $stock), [
            'quantity' => 0, 'reason' => 'barang program tertinggal, dikembalikan',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(0, $stock->fresh()->quantity);

        $m = StockMovement::where('movement_type', 'stock_correction')->firstOrFail();
        $this->assertSame('out', $m->direction);
        $this->assertEquals(6, $m->quantity);
        $this->assertStringContainsString('barang program tertinggal', $m->notes);
    }

    public function test_reason_is_required(): void
    {
        [, , $stock] = $this->seedSalesStock(6);

        $this->actingAs($this->makeAdminUser())->put(route('admin.inventory.stock.sales.update', $stock), [
            'quantity' => 3,
        ])->assertSessionHasErrors('reason');

        $this->assertEquals(6, $stock->fresh()->quantity);
    }

    public function test_sales_user_cannot_use_it(): void
    {
        [, , $stock] = $this->seedSalesStock(6);
        [$salesUser] = $this->makeSalesUser();

        $this->actingAs($salesUser)->put(route('admin.inventory.stock.sales.update', $stock), [
            'quantity' => 99, 'reason' => 'curang',
        ])->assertForbidden();

        $this->assertEquals(6, $stock->fresh()->quantity);
    }

    public function test_admin_cannot_edit_stock_of_another_branch(): void
    {
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, , $stockB] = $this->seedSalesStock(6, $branchB);

        $this->actingAs($this->makeAdminUser())->put(route('admin.inventory.stock.sales.update', $stockB), [
            'quantity' => 1, 'reason' => 'lintas cabang',
        ])->assertNotFound();

        $this->assertEquals(6, $stockB->fresh()->quantity);
    }
}
