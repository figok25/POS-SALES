<?php

namespace Tests\Feature\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 6 Hardening - Test untuk StockService (Blueprint #42 Definition
 * of Done: "Testing wajib untuk stock").
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_increase_creates_stock_row_and_movement(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $movement = app(StockService::class)->increase(
            $product->id,
            Stock::LOCATION_WAREHOUSE,
            $warehouse->id,
            50,
            'stock_adjustment',
        );

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'location_type' => Stock::LOCATION_WAREHOUSE,
            'location_id' => $warehouse->id,
            'quantity' => 50,
        ]);

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertSame('in', $movement->direction);
        $this->assertEquals(50, $movement->balance_after);
    }

    public function test_decrease_reduces_quantity_and_records_movement(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();
        $service = app(StockService::class);

        $service->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 50, 'stock_adjustment');
        $movement = $service->decrease($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 20, 'stock_adjustment');

        $this->assertEquals(30, $service->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertSame('out', $movement->direction);
        $this->assertEquals(30, $movement->balance_after);
    }

    public function test_decrease_throws_when_stock_insufficient(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();
        $service = app(StockService::class);

        $service->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'stock_adjustment');

        $this->expectException(InsufficientStockException::class);

        $service->decrease($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 999, 'stock_adjustment');
    }

    public function test_decrease_rolls_back_cleanly_when_insufficient_no_partial_movement(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();
        $service = app(StockService::class);

        $service->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'stock_adjustment');

        try {
            $service->decrease($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 999, 'stock_adjustment');
        } catch (InsufficientStockException) {
            // expected
        }

        // Quantity harus tetap 10 (tidak berubah sama sekali), dan tidak
        // ada movement "out" yang ke-record walau sebagian.
        $this->assertEquals(10, $service->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'direction' => 'out',
        ]);
    }

    public function test_transfer_moves_quantity_between_two_locations(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();
        [, $sales] = $this->makeSalesUser();
        $service = app(StockService::class);

        $service->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 100, 'bkb_apply');

        [$out, $in] = $service->transfer(
            $product->id,
            Stock::LOCATION_WAREHOUSE,
            $warehouse->id,
            Stock::LOCATION_SALES,
            $sales->id,
            30,
            'bkb_apply',
        );

        $this->assertEquals(70, $service->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertEquals(30, $service->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame('out', $out->direction);
        $this->assertSame('in', $in->direction);
    }

    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        $product = $this->makeProduct();
        $warehouse = $this->makeWarehouse();

        $this->expectException(\InvalidArgumentException::class);

        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 0, 'stock_adjustment');
    }
}
