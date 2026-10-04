<?php

namespace Tests\Feature\Finance;

use App\Models\BtbDistribusi;
use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\Stock;
use App\Services\SalesTransactionService;
use App\Services\SettlementService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 7 - Test HTTP/Authorization untuk Payment, Settlement, Cash
 * Ledger (Blueprint #42 Definition of Done: "Testing wajib untuk ...
 * authorization").
 */
class FinanceAccessTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    protected function makeInvoiceViaHttpFixtures(): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(10000);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        $trx = app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        return [$user, $sales, $trx->invoice];
    }

    public function test_sales_cannot_access_admin_finance_pages(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->get(route('admin.finance.payments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.finance.settlements.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.finance.cash-ledgers.index'))->assertForbidden();
    }

    public function test_admin_can_view_finance_pages(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->get(route('admin.finance.payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.finance.settlements.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.finance.cash-ledgers.index'))->assertOk();
    }

    public function test_admin_can_record_payment_via_http_and_invoice_updates(): void
    {
        [, , $invoice] = $this->makeInvoiceViaHttpFixtures();
        $admin = $this->makeAdminUser();

        // Halaman detail invoice harus render dengan tombol "Catat
        // Payment" & riwayat payment tanpa error, sebelum & sesudah dibayar.
        $this->actingAs($admin)
            ->get(route('admin.sales.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Catat Payment');

        $this->actingAs($admin)
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 20000,
                'method' => 'cash',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.sales.invoices.show', $invoice));

        $this->assertEquals(Invoice::STATUS_PAID, $invoice->fresh()->status);

        $this->actingAs($admin)
            ->get(route('admin.sales.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Catat Payment'); // sudah lunas, tombol harus hilang
    }

    public function test_sales_can_record_payment_for_own_invoice_but_not_others(): void
    {
        [$userA, , $invoiceA] = $this->makeInvoiceViaHttpFixtures();
        [$userB] = $this->makeSalesUser();

        // Sales A boleh untuk invoice miliknya sendiri.
        $this->actingAs($userA)
            ->post(route('sales.payments.store', $invoiceA), [
                'amount' => 20000,
                'method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertGreaterThan(0, (float) $invoiceA->fresh()->paid_amount);

        // Sales B tidak boleh untuk invoice milik Sales A.
        $this->actingAs($userB)
            ->post(route('sales.payments.store', $invoiceA), [
                'amount' => 10000,
                'method' => 'cash',
            ])
            ->assertForbidden();
    }

    /**
     * BTB retur milik Sales ini (sisa seluruh Sales Stock), berstatus Draft.
     */
    protected function makeDraftBtbForAllStock($sales, $warehouse): BtbDistribusi
    {
        static $n = 0;
        $n++;

        $stock = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->firstOrFail();

        $btb = BtbDistribusi::create([
            'code' => "BTB-FA-{$n}",
            'sales_id' => $sales->id,
            'warehouse_id' => $warehouse->id,
            'status' => BtbDistribusi::STATUS_DRAFT,
            'source' => BtbDistribusi::SOURCE_MANUAL,
        ]);
        $btb->items()->create(['product_id' => $stock->product_id, 'quantity' => $stock->quantity]);

        return $btb;
    }

    public function test_full_settlement_flow_via_http(): void
    {
        [$user, $sales, $invoice] = $this->makeInvoiceViaHttpFixtures(); // sisa Sales Stock 8
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();

        // Bayar invoice cash dulu supaya ada cash_expected.
        $this->actingAs($user)->post(route('sales.payments.store', $invoice), [
            'amount' => 20000, 'method' => 'cash',
        ]);

        // Tidak ada pembuatan draft manual: URL lama hanya mengalihkan ke daftar.
        $this->assertDatabaseCount('settlements', 0);
        $this->actingAs($admin)
            ->get(route('admin.finance.settlements.create'))
            ->assertRedirect(route('admin.finance.settlements.index'));

        // Admin meng-Apply BTB -> Settlement Draft terbentuk otomatis & mengklaim BTB-nya.
        $btb = $this->makeDraftBtbForAllStock($sales, $warehouse);
        $productId = $btb->items->first()->product_id;

        $this->actingAs($admin)
            ->post(route('admin.distribution.btb.apply', $btb))
            ->assertRedirect();

        $settlement = Settlement::where('sales_id', $sales->id)->firstOrFail();
        $this->assertSame($settlement->id, $btb->fresh()->settlement_id);
        $this->assertEquals(20000, $settlement->cash_expected);

        $this->actingAs($admin)
            ->get(route('admin.finance.settlements.index'))
            ->assertOk()
            ->assertSee($settlement->code)
            ->assertSee('Cek &amp; Apply', false);

        $this->actingAs($admin)
            ->get(route('admin.finance.settlements.edit', $settlement))
            ->assertOk()
            ->assertSee($settlement->code)
            ->assertSee($btb->code);

        $this->actingAs($admin)
            ->post(route('admin.finance.settlements.apply', $settlement), [
                'cash_deposited' => 20000,
            ])
            ->assertRedirect(route('admin.finance.settlements.index'));

        $this->assertEquals('applied', $settlement->fresh()->status);

        // Barang dipindahkan SEKALI, oleh BTB (8), bukan oleh Settlement.
        $stockService = app(StockService::class);
        $this->assertEquals(0, $stockService->getQuantity($productId, Stock::LOCATION_SALES, $sales->id));
        $this->assertEquals(8, $stockService->getQuantity($productId, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertEquals(0, $settlement->fresh('items')->items->first()->variance_qty);
    }

    public function test_settlement_apply_is_blocked_via_http_while_btb_pending(): void
    {
        [$user, $sales, $invoice] = $this->makeInvoiceViaHttpFixtures();
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();

        $this->actingAs($user)->post(route('sales.payments.store', $invoice), [
            'amount' => 20000, 'method' => 'cash',
        ]);

        $btb = $this->makeDraftBtbForAllStock($sales, $warehouse);
        $settlement = app(SettlementService::class)->ensureDraft($sales->id, $warehouse->id, $admin->id);

        $this->actingAs($admin)
            ->get(route('admin.finance.settlements.edit', $settlement))
            ->assertOk()
            ->assertSee('belum bisa di-Apply');

        $this->actingAs($admin)
            ->post(route('admin.finance.settlements.apply', $settlement), ['cash_deposited' => 20000])
            ->assertSessionHasErrors('status');

        $this->assertEquals('draft', $settlement->fresh()->status);
        $this->assertEquals('draft', $btb->fresh()->status);
    }
}
