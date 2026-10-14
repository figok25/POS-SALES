<?php

namespace Tests\Feature\Console;

use App\Models\BtbDistribusi;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/** Perintah audit read-only: berjalan, menandai BTB melebihi stok, dan tidak mengubah data. */
class AuditSalesStockTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_flags_return_exceeding_available_and_changes_nothing(): void
    {
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 30, 'bkb_apply');

        // BTB 55 padahal stok yang tersedia hanya 30 -> harus ditandai.
        $btb = BtbDistribusi::create(['code' => 'BTB-AUD-1', 'warehouse_id' => $wh->id, 'sales_id' => $sales->id, 'status' => BtbDistribusi::STATUS_APPLIED]);
        $btb->items()->create(['product_id' => $product->id, 'quantity' => 55]);

        $movements = \App\Models\StockMovement::count();
        $stock = (float) app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id);

        $this->artisan('sales:audit-stock', ['--detail' => true])
            ->expectsOutputToContain('KEMBALI>TERSEDIA')
            ->expectsOutputToContain('BTB-AUD-1')
            ->assertExitCode(0);

        $this->assertSame($movements, \App\Models\StockMovement::count());
        $this->assertSame($stock, (float) app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
    }
}
