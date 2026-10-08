<?php

namespace Tests\Feature\Sales;

use App\Models\CashLedger;
use App\Models\Invoice;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Penjualan langsung Depo: Admin menjual dari Gudang Depo, tanpa Sales.
 */
class AdminCreatesTransactionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function consumerPrice($product, float $amount = 20000): void
    {
        Price::create(['product_id' => $product->id, 'name' => 'Harga Konsumen', 'price_type' => Price::TYPE_CONSUMER, 'amount' => $amount, 'is_active' => true]);
    }

    private function stockWarehouse($warehouse, $product, float $qty): void
    {
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, $qty, 'adjustment_in');
    }

    private function payload($customer, $warehouse, $product, float $qty, array $extra = []): array
    {
        return array_merge([
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'price_type' => Price::TYPE_CONSUMER,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ], $extra);
    }

    public function test_depo_sale_decreases_warehouse_stock_without_sales(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->consumerPrice($product, 20000);
        $this->stockWarehouse($warehouse, $product, 10);

        $this->actingAs($admin)->get(route('admin.sales.transactions.create', [
            'warehouse_id' => $warehouse->id, 'customer_id' => $customer->id, 'price_type' => 'consumer',
        ]))->assertOk()->assertSee($product->name);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 4))
            ->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertNull($trx->sales_id);
        $this->assertSame($admin->branch_id, $trx->branch_id);
        $this->assertSame($warehouse->id, $trx->warehouse_id);
        $this->assertSame(SalesTransaction::SOURCE_ADMIN, $trx->source);
        $this->assertSame('consumer', $trx->price_type);
        $this->assertEquals(80000, $trx->total);

        $this->assertEquals(6, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));

        $invoice = $trx->invoice;
        $this->assertNotNull($invoice);
        $this->assertNull($invoice->sales_id);
        $this->assertSame($admin->branch_id, $invoice->branch_id);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);

        // Tanpa Delivery Order.
        $this->assertDatabaseCount('delivery_orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_at_creation_goes_to_branch_cash_ledger(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->consumerPrice($product, 20000);
        $this->stockWarehouse($warehouse, $product, 10);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 3, [
            'pay_amount' => 60000, 'pay_method' => 'cash',
        ]))->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame(Invoice::STATUS_PAID, $trx->invoice->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['invoice_id' => $trx->invoice->id, 'settlement_id' => null]);
        $this->assertDatabaseHas('cash_ledgers', [
            'branch_id' => $admin->branch_id, 'type' => CashLedger::TYPE_INCOME,
            'category' => 'Penjualan Depo', 'amount' => 60000,
        ]);
    }

    public function test_later_payment_for_depo_invoice_also_goes_to_cash_ledger(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->consumerPrice($product, 10000);
        $this->stockWarehouse($warehouse, $product, 10);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 5, [
            'pay_amount' => 20000,
        ]));

        $invoice = SalesTransaction::firstOrFail()->invoice;
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->status);

        app(PaymentService::class)->create($invoice, ['amount' => 30000, 'method' => 'transfer'], $admin->id);

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertEquals(50000, CashLedger::where('category', 'Penjualan Depo')->sum('amount'));
    }

    public function test_insufficient_warehouse_stock_is_rejected(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->consumerPrice($product);
        $this->stockWarehouse($warehouse, $product, 2);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 5))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales_transactions', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertEquals(2, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
    }

    public function test_missing_price_for_chosen_category_is_rejected(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $customer = $this->makeCustomer();
        $product = $this->makeProduct(); // hanya harga Retail
        $this->stockWarehouse($warehouse, $product, 5);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 1))
            ->assertSessionHasErrors('items');

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 1, ['price_type' => 'retail']))
            ->assertSessionHasNoErrors();

        $this->assertEquals(10000, SalesTransaction::firstOrFail()->total);
    }

    public function test_warehouse_of_another_branch_is_rejected(): void
    {
        $admin = $this->makeAdminUser();
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $warehouseB = $this->makeWarehouse($branchB);
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->consumerPrice($product);
        $this->stockWarehouse($warehouseB, $product, 5);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouseB, $product, 1))
            ->assertSessionHasErrors('warehouse_id');

        $this->assertDatabaseCount('sales_transactions', 0);
    }

    public function test_super_admin_picks_branch_and_branch_admin_cannot_see_it(): void
    {
        $superAdmin = $this->makeSuperAdminUser();
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $warehouseB = $this->makeWarehouse($branchB);
        $customer = $this->makeCustomer(null, $branchB);
        $product = $this->makeProduct();
        $this->consumerPrice($product);
        $this->stockWarehouse($warehouseB, $product, 5);

        // Tanpa branch_id: ditolak.
        $this->actingAs($superAdmin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouseB, $product, 1))
            ->assertSessionHasErrors('branch_id');

        $this->actingAs($superAdmin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouseB, $product, 1, ['branch_id' => $branchB->id]))
            ->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame($branchB->id, $trx->branch_id);

        // Admin Depo default tidak boleh melihat transaksi Depo B.
        $adminA = $this->makeAdminUser();
        $this->actingAs($adminA)->get(route('admin.sales.transactions.show', $trx))->assertForbidden();
        $this->actingAs($adminA)->get(route('admin.sales.transactions.index'))->assertOk()->assertDontSee($trx->code);
        $this->actingAs($superAdmin)->get(route('admin.sales.transactions.show', $trx))->assertOk();
    }

    public function test_depo_sales_are_separate_from_sales_dashboard_numbers(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $customer = $this->makeCustomer();
        $product = $this->makeProduct();
        $this->consumerPrice($product, 20000);
        $this->stockWarehouse($warehouse, $product, 10);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store'), $this->payload($customer, $warehouse, $product, 2, ['pay_amount' => 10000]));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertEquals(0, $response->viewData('kpi')['sales_total']);
        $this->assertEquals(0, $response->viewData('kpi')['outstanding_invoices']);

        $depo = $response->viewData('depo');
        $this->assertSame(1, $depo['count']);
        $this->assertEquals(40000, $depo['total']);
        $this->assertEquals(10000, $depo['cash_in']);
        $this->assertSame(1, $depo['outstanding_count']);
        $this->assertEquals(30000, $depo['outstanding_amount']);

        $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['source' => 'depo']))->assertOk();
        $this->actingAs($admin)->get(route('admin.sales.invoices.index'))->assertOk();
    }

    public function test_sales_user_cannot_use_depo_sale_page(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->get(route('admin.sales.transactions.create'))->assertForbidden();
    }

    public function test_sales_transaction_gets_branch_id_from_sales(): void
    {
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);

        $trx = SalesTransaction::create([
            'code' => 'TRX-BR-1', 'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);

        $this->assertSame($sales->branch_id, $trx->branch_id);
        $this->assertFalse($trx->isDepoSale());
    }
}
