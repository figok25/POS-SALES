<?php

namespace Tests\Feature\Finance;

use App\Models\BtbDistribusi;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Settlement;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\PaymentService;
use App\Services\SalesTransactionService;
use App\Services\SettlementService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 7 - Test untuk SettlementService (Blueprint #16).
 *
 * Alur yang diuji (diluruskan dengan BTB Distribusi):
 *  - Barang kembali HANYA dipindahkan oleh BTB; Settlement tidak memindahkan
 *    stok dan menampilkan status barang turunan BTB.
 *  - Settlement Draft dibuat otomatis (ensureDraft), bukan manual.
 *  - Settlement tidak bisa di-Apply selama masih ada BTB yang belum di-Apply.
 */
class SettlementServiceTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /**
     * Setup: satu Sales dengan 10 unit Sales Stock, terjual 3 (sisa 7), satu
     * invoice cash senilai Rp15.000 yang sudah dibayar lunas oleh customer.
     *
     * @return array{0: Sales, 1: Product, 2: Invoice}
     */
    protected function setUpSalesWithCashPayment(): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(5000);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        $trx = app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]], // Sales Stock sisa 7, invoice 15.000
        ]);

        $invoice = app(PaymentService::class)->create($trx->invoice, [
            'amount' => 15000,
            'method' => 'cash',
        ], $user->id)->invoice;

        return [$sales, $product, $invoice];
    }

    /**
     * BTB retur. Status 'applied' meniru Apply BTB sungguhan (stok Sales ->
     * Warehouse lewat StockService), supaya hasil Settlement diuji terhadap
     * kondisi stok yang nyata.
     */
    protected function makeBtb(Sales $sales, Warehouse $warehouse, Product $product, float $qty, string $status = BtbDistribusi::STATUS_DRAFT): BtbDistribusi
    {
        static $n = 0;
        $n++;

        $btb = BtbDistribusi::create([
            'code' => "BTB-T-{$n}",
            'sales_id' => $sales->id,
            'warehouse_id' => $warehouse->id,
            'status' => $status,
            'source' => BtbDistribusi::SOURCE_RETURN_STOCK,
        ]);
        $btb->items()->create(['product_id' => $product->id, 'quantity' => $qty]);

        if ($status === BtbDistribusi::STATUS_APPLIED) {
            app(StockService::class)->transfer(
                $product->id, Stock::LOCATION_SALES, $sales->id,
                Stock::LOCATION_WAREHOUSE, $warehouse->id,
                $qty, 'btb_apply', BtbDistribusi::class, $btb->id,
            );
        }

        return $btb;
    }

    private function qty(Product $product, string $type, int $id): float
    {
        return (float) app(StockService::class)->getQuantity($product->id, $type, $id);
    }

    // ---------------------------------------------------------------
    // Draft
    // ---------------------------------------------------------------

    public function test_create_draft_captures_cash_expected_and_reserves_payments(): void
    {
        [$sales] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $admin = $this->makeAdminUser();

        $settlement = app(SettlementService::class)->createDraft($sales->id, $warehouse->id, $admin->id);

        $this->assertEquals(Settlement::STATUS_DRAFT, $settlement->status);
        $this->assertEquals(15000, $settlement->cash_expected);
        // Barang tidak di-snapshot saat draft: dihitung langsung dari BTB + stok Sales.
        $this->assertCount(0, $settlement->items);

        // Payment cash langsung direservasi ke settlement ini.
        $this->assertEquals(1, Payment::where('settlement_id', $settlement->id)->count());
    }

    public function test_cannot_create_second_draft_while_one_is_open(): void
    {
        [$sales] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $service->createDraft($sales->id, $warehouse->id, null);

        $this->expectException(ValidationException::class);

        $service->createDraft($sales->id, $warehouse->id, null);
    }

    public function test_ensure_draft_creates_draft_and_claims_pending_btb_once(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $btb = $this->makeBtb($sales, $warehouse, $product, 7);

        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null);

        $this->assertNotNull($settlement);
        $this->assertSame($settlement->id, $btb->fresh()->settlement_id);

        // Idempotent: dipanggil lagi tidak membuat draft kedua.
        $again = $service->ensureDraft($sales->id, $warehouse->id, null);
        $this->assertSame($settlement->id, $again->id);
        $this->assertDatabaseCount('settlements', 1);
    }

    public function test_ensure_draft_returns_null_when_nothing_to_settle(): void
    {
        [, $sales] = $this->makeSalesUser();
        $warehouse = $this->makeWarehouse();

        $this->assertNull(app(SettlementService::class)->ensureDraft($sales->id, $warehouse->id, null));
        $this->assertDatabaseCount('settlements', 0);
    }

    public function test_ensure_draft_claims_new_btb_into_the_open_draft(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $first = $this->makeBtb($sales, $warehouse, $product, 3);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null);

        $second = $this->makeBtb($sales, $warehouse, $product, 4);
        $service->ensureDraft($sales->id, $warehouse->id, null);

        $this->assertDatabaseCount('settlements', 1);
        $this->assertSame($settlement->id, $first->fresh()->settlement_id);
        $this->assertSame($settlement->id, $second->fresh()->settlement_id);
    }

    public function test_ensure_draft_does_not_claim_old_applied_btb_unless_triggered(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();

        $old = $this->makeBtb($sales, $warehouse, $product, 2, BtbDistribusi::STATUS_APPLIED);

        $settlement = app(SettlementService::class)->ensureDraft($sales->id, $warehouse->id, null);

        $this->assertNotNull($settlement); // dibuat karena ada cash belum disetor
        $this->assertNull($old->fresh()->settlement_id);
    }

    // ---------------------------------------------------------------
    // Status barang (turunan BTB)
    // ---------------------------------------------------------------

    public function test_goods_summary_separates_pending_btb_from_real_variance(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment(); // stok Sales 7
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $this->makeBtb($sales, $warehouse, $product, 5); // menunggu: 5 dari 7
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null);

        $summary = $service->goodsSummary($settlement);
        $row = $summary['rows']->first();

        $this->assertTrue($summary['has_pending']);
        $this->assertEquals(0, $row['returned']);
        $this->assertEquals(5, $row['pending']);
        // 2 unit tidak tercakup BTB mana pun = selisih nyata.
        $this->assertEquals(2, $row['unreturned']);
    }

    // ---------------------------------------------------------------
    // Apply
    // ---------------------------------------------------------------

    public function test_apply_is_blocked_while_btb_is_still_pending(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $this->makeBtb($sales, $warehouse, $product, 7);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null);

        try {
            $service->apply($settlement, 15000, null);
            $this->fail('Apply seharusnya ditolak selama BTB belum di-Apply.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertEquals(Settlement::STATUS_DRAFT, $settlement->fresh()->status);
        $this->assertEquals(7, $this->qty($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_apply_after_btb_applied_has_no_variance_and_does_not_move_stock_again(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $btb = $this->makeBtb($sales, $warehouse, $product, 7, BtbDistribusi::STATUS_APPLIED);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null, $btb);

        $applied = $service->apply($settlement, 15000, null);

        $this->assertEquals(Settlement::STATUS_APPLIED, $applied->status);
        // Stok dipindahkan SEKALI oleh BTB (7), bukan dua kali.
        $this->assertEquals(0, $this->qty($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(7, $this->qty($product, Stock::LOCATION_WAREHOUSE, $warehouse->id));

        $this->assertEquals(0, $applied->cash_variance);
        $this->assertCount(1, $applied->items);
        $this->assertEquals(7, $applied->items->first()->returned_qty);
        $this->assertEquals(0, $applied->items->first()->variance_qty);
        $this->assertFalse($applied->hasGoodsVariance());
    }

    public function test_apply_records_goods_variance_for_stock_not_returned_by_any_btb(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        // BTB Applied hanya 5 dari 7; 2 tersisa di Sales tanpa BTB.
        $btb = $this->makeBtb($sales, $warehouse, $product, 5, BtbDistribusi::STATUS_APPLIED);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null, $btb);

        $applied = $service->apply($settlement, 15000, null, 'ada barang rusak');

        $this->assertEquals(2, $applied->items->first()->variance_qty);
        $this->assertTrue($applied->hasGoodsVariance());
        // Tetap tidak ada perpindahan stok oleh Settlement.
        $this->assertEquals(2, $this->qty($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_discrepancy_btb_does_not_block_and_counts_as_variance(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $btb = $this->makeBtb($sales, $warehouse, $product, 7, BtbDistribusi::STATUS_DISCREPANCY);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null, $btb);

        $applied = $service->apply($settlement, 15000, null);

        // Discrepancy tidak memindahkan stok -> barang masih di Sales = selisih.
        $this->assertEquals(7, $applied->items->first()->variance_qty);
    }

    public function test_apply_with_cash_shortage_records_cash_variance(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $btb = $this->makeBtb($sales, $warehouse, $product, 7, BtbDistribusi::STATUS_APPLIED);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null, $btb);

        // Sales cuma setor 10.000 dari 15.000 yang seharusnya.
        $applied = $service->apply($settlement, 10000, null);

        $this->assertEquals(-5000, $applied->cash_variance);
    }

    public function test_cannot_apply_the_same_settlement_twice(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $btb = $this->makeBtb($sales, $warehouse, $product, 7, BtbDistribusi::STATUS_APPLIED);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null, $btb);
        $service->apply($settlement, 15000, null);

        $this->expectException(ValidationException::class);

        $service->apply($settlement->fresh(), 0, null);
    }

    // ---------------------------------------------------------------
    // Batal / payment baru
    // ---------------------------------------------------------------

    public function test_discard_draft_releases_reserved_payments_and_btb_claims(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $btb = $this->makeBtb($sales, $warehouse, $product, 7);
        $settlement = $service->ensureDraft($sales->id, $warehouse->id, null);
        $paymentId = Payment::where('settlement_id', $settlement->id)->first()->id;

        $service->discardDraft($settlement, null);

        $this->assertNull(Payment::find($paymentId)->settlement_id);
        $this->assertNull($btb->fresh()->settlement_id);
        $this->assertDatabaseMissing('settlements', ['id' => $settlement->id]);

        // Setelah dibatalkan, Sales ini boleh dibuatkan Draft baru lagi.
        $newSettlement = $service->createDraft($sales->id, $warehouse->id, null);
        $this->assertEquals(15000, $newSettlement->cash_expected);
    }

    public function test_new_payment_after_draft_created_is_not_included(): void
    {
        [$sales] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);

        // Payment cash baru masuk SETELAH draft dibuat (mis. dari invoice lain).
        [$user2] = $this->makeSalesUser();
        $customer2 = $this->makeCustomer($sales->id);
        $product2 = $this->makeProduct(2000);
        app(StockService::class)->increase($product2->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');
        $trx2 = app(SalesTransactionService::class)->create($sales->id, $user2->id, [
            'customer_id' => $customer2->id,
            'items' => [['product_id' => $product2->id, 'quantity' => 1]],
        ]);
        app(PaymentService::class)->create($trx2->invoice, ['amount' => 2000, 'method' => 'cash'], null);

        // cash_expected settlement yang sudah dibuat TIDAK berubah.
        $this->assertEquals(15000, $settlement->fresh()->cash_expected);
    }
}
