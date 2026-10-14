<?php

namespace Tests\Feature\Sales;

use App\Models\AuditLog;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\PaymentService;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Edit transaksi penjualan oleh Admin/Superadmin: stok, invoice, dan piutang
 * dihitung ulang dari selisih; tercatat di audit log; terkunci per Depo.
 */
class SalesTransactionRevisionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function stockOf($product, string $type, int $id): float
    {
        return app(StockService::class)->getQuantity($product->id, $type, $id);
    }

    /** Sales bawa 10, jual 4 @15.000 = 60.000. */
    private function scenario(float $carry = 10, float $sold = 4): array
    {
        $admin = $this->makeAdminUser();
        [$salesUser, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(15000);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, $carry, 'bkb_apply');

        $trx = app(SalesTransactionService::class)->create($sales->id, $salesUser->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $sold]],
        ]);

        return [$admin, $sales, $customer, $product, $trx];
    }

    private function payload($product, float $qty, float $price, array $extra = []): array
    {
        return array_merge([
            'reason' => 'Salah input',
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'price' => $price]],
        ], $extra);
    }

    public function test_reducing_quantity_returns_difference_and_recalculates_invoice(): void
    {
        [$admin, $sales, , $product, $trx] = $this->scenario();

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000))
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertSame(7.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));   // 6 + 1
        $trx->refresh();
        $this->assertEquals(45000, $trx->total);
        $this->assertSame(1, $trx->revision_count);
        $this->assertEquals(45000, $trx->invoice->grand_total);
        $this->assertSame(Invoice::STATUS_UNPAID, $trx->invoice->status);
        $this->assertEquals(45000, $trx->invoice->items->sum('subtotal'));
    }

    public function test_same_revision_twice_does_not_double_move_stock(): void
    {
        [$admin, $sales, , $product, $trx] = $this->scenario();

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000));
        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000));

        $this->assertSame(7.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_increasing_quantity_takes_more_stock_and_fails_cleanly_when_short(): void
    {
        [$admin, $sales, , $product, $trx] = $this->scenario(carry: 10, sold: 4);   // sisa 6

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 9, 15000))
            ->assertSessionHasNoErrors();
        $this->assertSame(1.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));   // 6 - 5

        // Butuh 5 lagi tapi sisa 1 -> ditolak, tidak ada yang berubah.
        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 14, 15000))
            ->assertSessionHasErrors('items');
        $this->assertSame(1.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(135000, $trx->fresh()->total);
        $this->assertSame(1, $trx->fresh()->revision_count);
    }

    public function test_changing_product_corrects_both_products_stock(): void
    {
        [$admin, $sales, , $old, $trx] = $this->scenario();
        $new = $this->makeProduct(20000);
        app(StockService::class)->increase($new->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($new, 2, 20000))
            ->assertSessionHasNoErrors();

        $this->assertSame(10.0, $this->stockOf($old, Stock::LOCATION_SALES, $sales->id));   // semula 6, kembali 4
        $this->assertSame(3.0, $this->stockOf($new, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(40000, $trx->fresh()->total);
    }

    public function test_discount_price_and_payment_status_are_recomputed(): void
    {
        [$admin, , , $product, $trx] = $this->scenario();
        app(PaymentService::class)->create($trx->invoice, ['amount' => 20000], $admin->id);

        // Total baru 45.000 - 5.000 diskon = 40.000, dibayar 20.000 -> partial.
        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000, ['discount' => 5000]))
            ->assertSessionHasNoErrors();
        $inv = $trx->fresh()->invoice;
        $this->assertEquals(40000, $inv->grand_total);
        $this->assertEquals(5000, $inv->discount);
        $this->assertSame(Invoice::STATUS_PARTIAL, $inv->status);

        // Total baru 20.000 = sudah dibayar -> paid.
        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 2, 10000, ['discount' => 0]))
            ->assertSessionHasNoErrors();
        $this->assertSame(Invoice::STATUS_PAID, $trx->fresh()->invoice->status);
    }

    public function test_total_below_payment_already_received_is_rejected(): void
    {
        [$admin, $sales, , $product, $trx] = $this->scenario();
        app(PaymentService::class)->create($trx->invoice, ['amount' => 50000], $admin->id);

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 1, 15000))
            ->assertSessionHasErrors('items');

        $this->assertSame(6.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(60000, $trx->fresh()->total);
    }

    public function test_date_and_customer_can_be_corrected(): void
    {
        [$admin, $sales, , $product, $trx] = $this->scenario();
        $other = $this->makeCustomer($sales->id);
        $day = now()->subDays(2)->toDateString();

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 4, 15000, [
            'customer_id' => $other->id,
            'transaction_date' => $day,
        ]))->assertSessionHasNoErrors();

        $trx->refresh();
        $this->assertSame($other->id, (int) $trx->customer_id);
        $this->assertSame($day, $trx->created_at->toDateString());
        $this->assertSame($other->id, (int) $trx->invoice->customer_id);
        $this->assertSame($day, $trx->invoice->date->toDateString());
    }

    public function test_customer_of_another_sales_is_rejected(): void
    {
        [$admin, , , $product, $trx] = $this->scenario();
        [, $otherSales] = $this->makeSalesUser();
        $foreign = $this->makeCustomer($otherSales->id);

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 4, 15000, ['customer_id' => $foreign->id]))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_draft_delivery_order_follows_new_items_but_dispatched_one_does_not(): void
    {
        [$admin, , , $product, $trx] = $this->scenario();

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000));
        $this->assertEquals(3, $trx->fresh()->deliveryOrder->items->first()->quantity);

        $trx->deliveryOrder->update(['status' => DeliveryOrder::STATUS_DISPATCHED]);

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 2, 15000))
            ->assertSessionHas('warnings');
        $this->assertEquals(3, $trx->fresh()->deliveryOrder->items->first()->quantity);
    }

    public function test_free_item_quantity_can_be_reduced_and_stock_returns(): void
    {
        [$admin, $sales, , $product, $trx] = $this->scenario();
        $gift = $this->makeProduct(5000);
        $stock = app(StockService::class);
        $stock->increase($gift->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');
        $stock->decrease($gift->id, Stock::LOCATION_SALES, $sales->id, 2, 'sales_transaction', SalesTransaction::class, $trx->id);
        $free = $trx->items()->create(['product_id' => $gift->id, 'quantity' => 2, 'price' => 0, 'subtotal' => 0, 'is_free' => true]);

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 4, 15000, ['free' => [$free->id => 1]]))
            ->assertSessionHasNoErrors();

        $this->assertSame(4.0, $this->stockOf($gift, Stock::LOCATION_SALES, $sales->id));   // 3 + 1
        $this->assertEquals(1, $trx->fresh()->items->firstWhere('is_free', true)->quantity);
        $this->assertEquals(60000, $trx->fresh()->total);   // gratis tidak mengubah total
    }

    public function test_reason_is_required_and_audit_log_records_before_after(): void
    {
        [$admin, , , $product, $trx] = $this->scenario();

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), ['items' => [['product_id' => $product->id, 'quantity' => 3, 'price' => 15000]]])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000, ['reason' => 'Hitung ulang nota']));

        $log = AuditLog::where('action', 'revise')->where('document_type', SalesTransaction::class)->where('document_id', $trx->id)->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Hitung ulang nota', $log->after['reason']);
        $this->assertEquals(60000, $log->before['total']);
        $this->assertEquals(45000, $log->after['total']);
        $this->assertSame(-1.0, (float) $log->after['stock_impact'][0]['delta']);
    }

    public function test_depo_sale_edit_adjusts_warehouse_stock(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        Price::create(['product_id' => $product->id, 'name' => 'Konsumen', 'price_type' => Price::TYPE_CONSUMER, 'amount' => 20000, 'is_active' => true]);
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'adjustment_in');

        $this->actingAs($admin)->post(route('admin.depo.store'), ['warehouse_id' => $warehouse->id, 'items' => [['product_id' => $product->id, 'quantity' => 4]]]);
        $trx = SalesTransaction::firstOrFail();
        $this->assertSame(6.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $warehouse->id));

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 20000, ['consumer_name' => 'Pak Budi']))
            ->assertSessionHasNoErrors();

        $this->assertSame(7.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertSame('Pak Budi', $trx->fresh()->consumer_name);
        $this->assertEquals(60000, $trx->fresh()->total);
    }

    public function test_admin_of_other_depo_is_forbidden_and_super_admin_can_edit(): void
    {
        [, , , $product, $trx] = $this->scenario();
        $otherAdmin = $this->makeAdminUser($this->makeBranch('CBG-LAIN', 'Depo Lain'));

        $this->actingAs($otherAdmin)->get(route('admin.sales.transactions.edit', $trx))->assertForbidden();
        $this->actingAs($otherAdmin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 1, 1000))->assertForbidden();

        $super = $this->makeSuperAdminUser();
        $this->actingAs($super)->get(route('admin.sales.transactions.edit', $trx))->assertOk();
        $this->actingAs($super)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 3, 15000))->assertSessionHasNoErrors();
        $this->actingAs($super)->get(route('admin.sales.transactions.show', $trx))->assertOk()->assertSee('Riwayat Koreksi');
    }

    public function test_cancelled_transaction_cannot_be_edited(): void
    {
        [$admin, , , $product, $trx] = $this->scenario();
        $trx->update(['status' => SalesTransaction::STATUS_CANCELLED]);

        $this->actingAs($admin)->get(route('admin.sales.transactions.edit', $trx))->assertRedirect(route('admin.sales.transactions.show', $trx));
        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $this->payload($product, 1, 1000))->assertSessionHasErrors('items');
    }
}
