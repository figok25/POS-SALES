<?php

namespace Tests\Feature\Sales;

use App\Models\AuditLog;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Admin/Superadmin mencatat transaksi atas nama Sales: milik Sales yang dipilih,
 * Admin tercatat sebagai penginput, stok Sales terpotong, tidak boleh dobel.
 */
class AdminEntersForSalesTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function setUpSales(float $stock = 10): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(15000);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, $stock, 'bkb_apply');

        return [$admin, $sales, $customer, $product];
    }

    private function payload($sales, $customer, $product, float $qty = 4, float $price = 15000, array $extra = []): array
    {
        return array_merge([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'transaction_date' => now()->subDay()->toDateString(),
            'entry_reason' => 'Sales lupa input',
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'price' => $price]],
        ], $extra);
    }

    private function stockOf($product, $sales): float
    {
        return app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id);
    }

    public function test_admin_entry_is_owned_by_sales_and_recorded_by_admin(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();
        $day = now()->subDay()->toDateString();

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product))
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame($sales->id, $trx->sales_id);                       // tanggung jawab Sales
        $this->assertSame($admin->id, $trx->created_by);                     // penginput Admin
        $this->assertSame(SalesTransaction::SOURCE_ADMIN, $trx->source);
        $this->assertTrue($trx->isEnteredForSales());
        $this->assertSame($day, $trx->created_at->toDateString());           // waktu sebenarnya
        $this->assertNotNull($trx->recorded_at);                             // waktu dicatat
        $this->assertTrue($trx->recorded_at->isToday());
        $this->assertSame('Sales lupa input', $trx->entry_reason);
        $this->assertEquals(60000, $trx->total);

        $this->assertSame(6.0, $this->stockOf($product, $sales));
        $this->assertSame($day, $trx->invoice->date->toDateString());
        $this->assertSame(Invoice::STATUS_UNPAID, $trx->invoice->status);
        $this->assertSame(DeliveryOrder::STATUS_DRAFT, $trx->deliveryOrder->status);
    }

    public function test_price_and_discount_can_be_adjusted(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 4, 14000, ['discount' => 6000]))
            ->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertEquals(56000, $trx->subtotal);
        $this->assertEquals(6000, $trx->discount);
        $this->assertEquals(50000, $trx->total);
        $this->assertEquals(50000, $trx->invoice->grand_total);
    }

    public function test_discount_above_subtotal_is_rejected(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 1, 1000, ['discount' => 5000]))
            ->assertSessionHasErrors('discount');
        $this->assertSame(0, SalesTransaction::count());
        $this->assertSame(10.0, $this->stockOf($product, $sales));
    }

    public function test_same_transaction_cannot_be_recorded_twice_unless_confirmed(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 2));
        $this->assertSame(1, SalesTransaction::count());

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 2))
            ->assertSessionHasErrors('duplicate');
        $this->assertSame(1, SalesTransaction::count());
        $this->assertSame(8.0, $this->stockOf($product, $sales));

        // Isi barang berbeda = bukan dobel.
        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 3))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, SalesTransaction::count());

        // Memang sama tapi dikonfirmasi Admin.
        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 2, 15000, ['allow_duplicate' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertSame(3, SalesTransaction::count());
    }

    public function test_already_recorded_by_sales_is_detected_as_duplicate(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();
        $salesUser = $sales->user;

        $existing = app(SalesTransactionService::class)->create($sales->id, $salesUser?->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 2, 15000, [
            'transaction_date' => $existing->created_at->toDateString(),
        ]))->assertSessionHasErrors('duplicate');
        $this->assertSame(1, SalesTransaction::count());
    }

    public function test_insufficient_sales_stock_rejects_without_side_effects(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales(3);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 5))
            ->assertSessionHasErrors('items');
        $this->assertSame(0, SalesTransaction::count());
        $this->assertSame(3.0, $this->stockOf($product, $sales));
    }

    public function test_customer_of_another_sales_is_rejected(): void
    {
        [$admin, $sales, , $product] = $this->setUpSales();
        [, $otherSales] = $this->makeSalesUser();
        $foreign = $this->makeCustomer($otherSales->id);

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $foreign, $product))
            ->assertSessionHasErrors('customer_id');
        $this->assertSame(0, SalesTransaction::count());
    }

    public function test_reason_required_and_future_date_rejected(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();

        $payload = $this->payload($sales, $customer, $product);
        unset($payload['entry_reason']);
        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $payload)->assertSessionHasErrors('entry_reason');

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product, 1, 15000, [
            'transaction_date' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors('transaction_date');
        $this->assertSame(0, SalesTransaction::count());
    }

    public function test_audit_log_records_actor_sales_and_dates(): void
    {
        [$admin, $sales, $customer, $product] = $this->setUpSales();

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product));
        $trx = SalesTransaction::firstOrFail();

        $log = AuditLog::where('action', 'create')->where('document_type', SalesTransaction::class)->where('document_id', $trx->id)->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($sales->id, $log->after['sales_id']);
        $this->assertSame('admin', $log->after['source']);
        $this->assertSame('Sales lupa input', $log->after['entry_reason']);
        $this->assertNotNull($log->after['recorded_at']);
        $this->assertNotNull($log->after['created_at']);
    }

    public function test_admin_cannot_enter_for_sales_of_another_depo(): void
    {
        [, $sales, $customer, $product] = $this->setUpSales();
        $otherAdmin = $this->makeAdminUser($this->makeBranch('CBG-LAIN', 'Depo Lain'));

        $this->actingAs($otherAdmin)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product))
            ->assertForbidden();
        $this->assertSame(0, SalesTransaction::count());

        // Sales depo lain tidak muncul di pilihan.
        $this->actingAs($otherAdmin)->get(route('admin.sales.transactions.create-for-sales', ['sales_id' => $sales->id]))
            ->assertOk()->assertDontSee($customer->name);
    }

    public function test_super_admin_can_enter_for_any_depo_and_form_renders(): void
    {
        [, $sales, $customer, $product] = $this->setUpSales();
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->get(route('admin.sales.transactions.create-for-sales', ['sales_id' => $sales->id]))
            ->assertOk()->assertSee($customer->name)->assertSee($product->name);

        $this->actingAs($super)->post(route('admin.sales.transactions.store-for-sales'), $this->payload($sales, $customer, $product))
            ->assertSessionHasNoErrors();
        $this->assertSame($super->id, SalesTransaction::firstOrFail()->created_by);

        $this->actingAs($super)->get(route('admin.sales.transactions.show', SalesTransaction::first()))
            ->assertOk()->assertSee('Diinput oleh');
    }

    public function test_normal_sales_creation_ignores_admin_only_fields(): void
    {
        [, $sales, $customer, $product] = $this->setUpSales();

        $trx = app(SalesTransactionService::class)->create($sales->id, null, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 1]],
            'discount' => 5000,
            'transaction_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertEquals(30000, $trx->total);   // harga & diskon dari klien Sales diabaikan
        $this->assertSame(SalesTransaction::SOURCE_SALES, $trx->source);
        $this->assertNull($trx->recorded_at);
        $this->assertTrue($trx->created_at->isToday());
    }
}
