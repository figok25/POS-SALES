<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sales;
use App\Models\Settlement;
use App\Models\Stock;
use App\Services\PaymentService;
use App\Services\SalesTransactionService;
use App\Services\SettlementService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 7 - Test untuk SettlementService (Blueprint #16): setoran
 * harian Sales (uang + retur barang), mengikuti pola Draft -> Apply
 * yang sama seperti BKB/BTB (Blueprint #13).
 */
class SettlementServiceTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /**
     * Setup: satu Sales dengan 10 unit Sales Stock, satu invoice cash
     * senilai Rp50.000 yang sudah dibayar lunas oleh customer.
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

    public function test_create_draft_captures_cash_expected_and_system_qty(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $admin = $this->makeAdminUser();

        $settlement = app(SettlementService::class)->createDraft($sales->id, $warehouse->id, $admin->id);

        $this->assertEquals(Settlement::STATUS_DRAFT, $settlement->status);
        $this->assertEquals(15000, $settlement->cash_expected);
        $this->assertCount(1, $settlement->items);
        $this->assertEquals(7, $settlement->items->first()->system_qty); // 10 - 3 terjual

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

    public function test_apply_with_full_return_transfers_stock_to_warehouse(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);
        $stockService = app(StockService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);

        $applied = $service->apply($settlement, [$product->id => 7], 15000, null);

        $this->assertEquals(Settlement::STATUS_APPLIED, $applied->status);
        $this->assertEquals(0, $stockService->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(7, $stockService->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertEquals(0, $applied->cash_variance);
        $this->assertEquals(0, $applied->items->first()->variance_qty);
    }

    public function test_apply_with_partial_return_records_goods_variance(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);

        // Sales cuma kembalikan 5 dari 7 (2 hilang/selisih).
        $applied = $service->apply($settlement, [$product->id => 5], 15000, null, 'ada barang rusak');

        $this->assertEquals(2, $applied->items->first()->variance_qty);
        $this->assertTrue($applied->hasGoodsVariance());
    }

    public function test_apply_with_cash_shortage_records_cash_variance(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);

        // Sales cuma setor 10.000 dari 15.000 yang seharusnya.
        $applied = $service->apply($settlement, [$product->id => 7], 10000, null);

        $this->assertEquals(-5000, $applied->cash_variance);
    }

    public function test_cannot_apply_the_same_settlement_twice(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);
        $service->apply($settlement, [$product->id => 7], 15000, null);

        $this->expectException(ValidationException::class);

        $service->apply($settlement->fresh(), [$product->id => 0], 0, null);
    }

    public function test_cannot_return_more_than_system_qty(): void
    {
        [$sales, $product] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);

        $this->expectException(ValidationException::class);

        $service->apply($settlement, [$product->id => 999], 15000, null);
    }

    public function test_discard_draft_releases_reserved_payments(): void
    {
        [$sales] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);
        $paymentId = Payment::where('settlement_id', $settlement->id)->first()->id;

        $service->discardDraft($settlement, null);

        $this->assertNull(Payment::find($paymentId)->settlement_id);
        $this->assertDatabaseMissing('settlements', ['id' => $settlement->id]);

        // Setelah dibatalkan, Sales ini boleh dibuatkan Draft baru lagi.
        $newSettlement = $service->createDraft($sales->id, $warehouse->id, null);
        $this->assertEquals(15000, $newSettlement->cash_expected);
    }

    public function test_new_payment_after_draft_created_is_not_included(): void
    {
        [$sales, $product, $invoice] = $this->setUpSalesWithCashPayment();
        $warehouse = $this->makeWarehouse();
        $service = app(SettlementService::class);

        $settlement = $service->createDraft($sales->id, $warehouse->id, null);

        // Payment cash baru masuk SETELAH draft dibuat (mis. dari invoice lain).
        [$user2, $sales2] = $this->makeSalesUser();
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
