<?php

namespace Tests\Feature\Admin;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Super Admin boleh mengoreksi jumlah stok Warehouse; Admin biasa tidak.
 */
class StockCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function seedStock(float $qty): Stock
    {
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, $qty, 'adjustment_in');

        return Stock::firstOrFail();
    }

    public function test_super_admin_can_correct_quantity_and_it_is_logged(): void
    {
        $stock = $this->seedStock(10);
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->put(route('admin.inventory.stock.update', $stock), [
            'quantity' => 25, 'reason' => 'stok opname',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(25, $stock->fresh()->quantity);
        $m = StockMovement::where('movement_type', 'stock_correction')->firstOrFail();
        $this->assertSame('in', $m->direction);
        $this->assertEquals(15, $m->quantity);
        $this->assertEquals(25, $m->balance_after);
        $this->assertStringContainsString('stok opname', $m->notes);
    }

    public function test_decrease_is_recorded_as_out(): void
    {
        $stock = $this->seedStock(10);

        $this->actingAs($this->makeSuperAdminUser())->put(route('admin.inventory.stock.update', $stock), [
            'quantity' => 4, 'reason' => 'rusak',
        ])->assertSessionHasNoErrors();

        $m = StockMovement::where('movement_type', 'stock_correction')->firstOrFail();
        $this->assertSame('out', $m->direction);
        $this->assertEquals(6, $m->quantity);
    }

    public function test_reason_is_required_and_negative_rejected(): void
    {
        $stock = $this->seedStock(10);
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->put(route('admin.inventory.stock.update', $stock), ['quantity' => 5])
            ->assertSessionHasErrors('reason');
        $this->actingAs($super)->put(route('admin.inventory.stock.update', $stock), ['quantity' => -1, 'reason' => 'x'])
            ->assertSessionHasErrors('quantity');
        $this->assertEquals(10, $stock->fresh()->quantity);
    }

    public function test_regular_admin_cannot_correct_stock(): void
    {
        $stock = $this->seedStock(10);

        $this->actingAs($this->makeAdminUser())->put(route('admin.inventory.stock.update', $stock), [
            'quantity' => 99, 'reason' => 'coba',
        ])->assertForbidden();

        $this->assertEquals(10, $stock->fresh()->quantity);
    }

    public function test_edit_button_only_visible_to_super_admin(): void
    {
        $this->seedStock(10);

        $this->actingAs($this->makeAdminUser())->get(route('admin.inventory.stock.index'))
            ->assertOk()->assertDontSee('Jumlah baru');
        $this->actingAs($this->makeSuperAdminUser())->get(route('admin.inventory.stock.index'))
            ->assertOk()->assertSee('Jumlah baru');
    }
}
