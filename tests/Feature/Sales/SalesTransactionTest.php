<?php

namespace Tests\Feature\Sales;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Unit;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 6 Hardening - Test untuk SalesTransactionService (Blueprint #42
 * Definition of Done: "Testing wajib untuk ... sales transaction").
 * Mengikuti alur resmi: Validate Customer -> Product -> Price -> Stock
 * -> Create -> Decrease Sales Stock -> Stock Movement -> Invoice
 * (Blueprint #22).
 */
class SalesTransactionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_successful_transaction_decreases_stock_and_generates_invoice(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(15000);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        $trx = app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ]);

        $this->assertInstanceOf(SalesTransaction::class, $trx);
        $this->assertEquals(60000, $trx->total);

        // Sales Stock berkurang 4 (10 - 4 = 6).
        $this->assertEquals(6, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));

        // Invoice otomatis terbit sesuai total transaksi.
        $this->assertInstanceOf(Invoice::class, $trx->invoice);
        $this->assertEquals(60000, $trx->invoice->grand_total);
        $this->assertEquals(Invoice::STATUS_UNPAID, $trx->invoice->status);

        // Stock movement tercatat dengan document reference ke transaksi.
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'direction' => 'out',
            'document_type' => SalesTransaction::class,
            'document_id' => $trx->id,
        ]);
    }

    public function test_transaction_rejected_when_sales_stock_insufficient(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(15000);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 2, 'bkb_apply');

        $this->expectException(ValidationException::class);

        try {
            app(SalesTransactionService::class)->create($sales->id, $user->id, [
                'customer_id' => $customer->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ]);
        } finally {
            // Rollback bersih: stock tidak boleh berkurang sama sekali,
            // dan tidak ada transaksi/invoice yang ke-create.
            $this->assertEquals(2, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
            $this->assertDatabaseCount('sales_transactions', 0);
            $this->assertDatabaseCount('invoices', 0);
        }
    }

    public function test_transaction_rejected_for_customer_belonging_to_another_sales(): void
    {
        [$userA, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $customerOfB = $this->makeCustomer($salesB->id);
        $product = $this->makeProduct();

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $salesA->id, 10, 'bkb_apply');

        $this->expectException(ValidationException::class);

        app(SalesTransactionService::class)->create($salesA->id, $userA->id, [
            'customer_id' => $customerOfB->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_transaction_rejected_when_no_active_price(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);

        // Produk tanpa Price aktif (dibuat manual, bukan lewat makeProduct()).
        $category = Category::firstOrCreate(['code' => 'GEN'], ['name' => 'Umum']);
        $unit = Unit::firstOrCreate(['symbol' => 'PCS'], ['name' => 'Pieces']);
        $product = Product::create([
            'sku' => 'SKU-NOPRICE',
            'name' => 'Produk Tanpa Harga',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        $this->expectException(ValidationException::class);

        app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
    }
}
