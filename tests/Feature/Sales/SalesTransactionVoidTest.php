<?php

namespace Tests\Feature\Sales;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\PaymentService;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Super Admin menghapus (void) transaksi Sales: stok kembali ke Sales, invoice
 * batal, dashboard/laporan menyesuaikan, tercatat di audit log.
 */
class SalesTransactionVoidTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function stockOf($product, $sales): float
    {
        return app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id);
    }

    /** Sales bawa 10, jual 4 @15.000 hari ini (+ kunjungan). */
    private function scenario(): array
    {
        [$salesUser, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(15000);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        Visit::create(['sales_id' => $sales->id, 'customer_id' => $customer->id, 'check_in_at' => now(), 'check_in_condition' => 'normal', 'status' => Visit::STATUS_COMPLETED]);

        $trx = app(SalesTransactionService::class)->create($sales->id, $salesUser->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ]);

        return [$salesUser, $sales, $customer, $product, $trx];
    }

    public function test_super_admin_void_returns_stock_cancels_invoice_and_updates_reports(): void
    {
        [$salesUser, $sales, , $product, $trx] = $this->scenario();
        $super = $this->makeSuperAdminUser();
        $this->assertSame(6.0, $this->stockOf($product, $sales));

        $this->actingAs($salesUser)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => $r['summary']['ec'] === 1 && (float) $r['summary']['total_sales'] === 60000.0);

        $this->actingAs($super)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'Sales terlanjur transaksi'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $trx->refresh();
        $this->assertSame(SalesTransaction::STATUS_CANCELLED, $trx->status);
        $this->assertTrue($trx->isVoided());
        $this->assertSame($super->id, $trx->voided_by);
        $this->assertSame('Sales terlanjur transaksi', $trx->void_reason);
        $this->assertSame(10.0, $this->stockOf($product, $sales));
        $this->assertSame(Invoice::STATUS_CANCELLED, $trx->invoice->status);

        // Laporan/dashboard: transaksi tidak terhitung lagi, kunjungan tetap Call Made.
        $this->actingAs($salesUser)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => $r['summary']['ec'] === 0 && $r['summary']['visits'] === 1 && (float) $r['summary']['total_sales'] === 0.0);
        $this->actingAs($salesUser)->get(route('sales.dashboard'))
            ->assertViewHas('transaksiHariIni', 0)->assertViewHas('penjualanHariIni', fn ($v) => (float) $v === 0.0);

        $log = AuditLog::where('action', 'void')->where('document_id', $trx->id)->firstOrFail();
        $this->assertSame($super->id, $log->user_id);
        $this->assertSame('Sales terlanjur transaksi', $log->after['reason']);
    }

    public function test_void_twice_does_not_return_stock_twice_and_reason_is_required(): void
    {
        [, $sales, , $product, $trx] = $this->scenario();
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->post(route('admin.sales.transactions.void', $trx), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(SalesTransaction::STATUS_COMPLETED, $trx->fresh()->status);

        $this->actingAs($super)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'salah transaksi']);
        $this->actingAs($super)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'salah transaksi'])->assertSessionHas('error');

        $this->assertSame(10.0, $this->stockOf($product, $sales));
        $this->assertSame(1, AuditLog::where('action', 'void')->where('document_id', $trx->id)->count());
    }

    public function test_only_super_admin_can_void(): void
    {
        [$salesUser, $sales, , $product, $trx] = $this->scenario();
        $admin = $this->makeAdminUser($sales->branch);

        $this->actingAs($admin)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'coba hapus'])->assertForbidden();
        $this->actingAs($salesUser)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'coba hapus'])->assertForbidden();

        $this->assertSame(SalesTransaction::STATUS_COMPLETED, $trx->fresh()->status);
        $this->assertSame(6.0, $this->stockOf($product, $sales));
    }

    public function test_unsettled_payment_is_removed_and_cancelled_invoice_rejects_new_payment(): void
    {
        [, $sales, , , $trx] = $this->scenario();
        $super = $this->makeSuperAdminUser();
        $invoice = $trx->invoice;

        app(PaymentService::class)->create($invoice, ['amount' => 20000], $super->id);
        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());

        $this->actingAs($super)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'salah transaksi'])
            ->assertSessionHasNoErrors()->assertSessionHas('warnings');

        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->status);
        $this->assertEquals(0, $invoice->paid_amount);

        $this->expectException(ValidationException::class);
        app(PaymentService::class)->create($invoice, ['amount' => 1000], $super->id);
    }

    public function test_detail_page_shows_delete_button_only_to_super_admin_and_void_info_after(): void
    {
        [, $sales, , , $trx] = $this->scenario();
        $super = $this->makeSuperAdminUser();
        $admin = $this->makeAdminUser($sales->branch);

        $this->actingAs($super)->get(route('admin.sales.transactions.show', $trx))->assertOk()->assertSee('Hapus Transaksi');
        $this->actingAs($admin)->get(route('admin.sales.transactions.show', $trx))->assertOk()->assertDontSee('Hapus Transaksi');

        $this->actingAs($super)->post(route('admin.sales.transactions.void', $trx), ['reason' => 'salah transaksi']);
        $this->actingAs($super)->get(route('admin.sales.transactions.show', $trx))
            ->assertOk()->assertSee('dihapus oleh')->assertSee('salah transaksi');
    }
}
