<?php

namespace Tests\Feature\Finance;

use App\Models\BkbDistribusi;
use App\Models\BtbDistribusi;
use App\Models\SalesTask;
use App\Models\Settlement;
use App\Models\Stock;
use App\Services\PaymentService;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Alur lengkap yang diluruskan: Sales Return Stock -> BTB (menunggu) +
 * Settlement Draft otomatis -> Admin Apply BTB -> Admin Apply Settlement.
 */
class AutoSettlementFlowTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /**
     * Sales dengan Task WORKING yang terikat BKB (syarat Return Stock).
     *
     * @return array{0: \App\Models\User, 1: \App\Models\Sales, 2: \App\Models\Warehouse, 3: SalesTask}
     */
    private function setUpWorkingTask(): array
    {
        static $n = 0;
        $n++;

        [$user, $sales] = $this->makeSalesUser();
        $warehouse = $this->makeWarehouse();

        $bkb = BkbDistribusi::create([
            'code' => "BKB-AS-{$n}",
            'warehouse_id' => $warehouse->id,
            'sales_id' => $sales->id,
            'status' => BkbDistribusi::STATUS_APPLIED,
        ]);

        $task = $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);
        $task->update(['bkb_distribusi_id' => $bkb->id]);

        return [$user, $sales, $warehouse, $task];
    }

    public function test_return_stock_creates_btb_and_settlement_draft_then_full_apply_flow(): void
    {
        [$user, $sales, $warehouse] = $this->setUpWorkingTask();
        $admin = $this->makeAdminUser();
        $product = $this->makeProduct(5000);
        $stockService = app(StockService::class);

        $stockService->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        // 1. Sales submit Return Stock -> BTB menunggu + Settlement Draft otomatis.
        $this->actingAs($user)
            ->post(route('sales.return-stock.store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 10]],
            ])
            ->assertRedirect(route('sales.return-stock.index'));

        $btb = BtbDistribusi::firstOrFail();
        $settlement = Settlement::where('sales_id', $sales->id)->firstOrFail();

        $this->assertSame($settlement->id, $btb->settlement_id);
        $this->assertSame(Settlement::STATUS_DRAFT, $settlement->status);
        // Stok belum berpindah saat Submit.
        $this->assertEquals(10, $stockService->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));

        // 2. BTB masih menunggu -> Settlement tidak bisa di-Apply.
        $this->actingAs($admin)
            ->post(route('admin.finance.settlements.apply', $settlement), ['cash_deposited' => 0])
            ->assertSessionHasErrors('status');
        $this->assertSame(Settlement::STATUS_DRAFT, $settlement->fresh()->status);

        // 3. Admin Apply BTB -> barang kembali ke Warehouse.
        $this->actingAs($admin)
            ->post(route('admin.distribution.btb.apply', $btb))
            ->assertRedirect();
        $this->assertEquals(0, $stockService->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(10, $stockService->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));

        // Masih satu Settlement yang sama (tidak membuat draft kedua).
        $this->assertDatabaseCount('settlements', 1);

        // 4. Admin Apply Settlement -> selesai tanpa selisih barang.
        $this->actingAs($admin)
            ->post(route('admin.finance.settlements.apply', $settlement), ['cash_deposited' => 0])
            ->assertRedirect(route('admin.finance.settlements.index'));

        $applied = $settlement->fresh('items');
        $this->assertSame(Settlement::STATUS_APPLIED, $applied->status);
        $this->assertEquals(10, $applied->items->first()->returned_qty);
        $this->assertEquals(0, $applied->items->first()->variance_qty);
        // Warehouse tetap 10 (Settlement tidak memindahkan stok lagi).
        $this->assertEquals(10, $stockService->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
    }

    public function test_complete_empty_creates_settlement_draft_when_there_is_cash_to_settle(): void
    {
        [$user, $sales] = $this->setUpWorkingTask();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(5000);

        // Semua stok terjual (3 dari 3) dan dibayar cash -> stok Sales 0.
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 3, 'bkb_apply');
        $trx = app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);
        app(PaymentService::class)->create($trx->invoice, ['amount' => 15000, 'method' => 'cash'], $user->id);

        $this->actingAs($user)
            ->post(route('sales.return-stock.complete-empty'))
            ->assertRedirect(route('sales.return-stock.index'));

        $settlement = Settlement::where('sales_id', $sales->id)->firstOrFail();
        $this->assertEquals(15000, $settlement->cash_expected);
        $this->assertDatabaseCount('btb_distribusi', 0);
    }

    public function test_complete_empty_without_cash_does_not_create_empty_settlement(): void
    {
        [$user] = $this->setUpWorkingTask();

        $this->actingAs($user)
            ->post(route('sales.return-stock.complete-empty'))
            ->assertRedirect(route('sales.return-stock.index'));

        $this->assertDatabaseCount('settlements', 0);
    }
}
