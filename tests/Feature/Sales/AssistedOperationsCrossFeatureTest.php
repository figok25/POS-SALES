<?php

namespace Tests\Feature\Sales;

use App\Models\AuditLog;
use App\Models\BkbDistribusi;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Visit;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Lintas fitur: input bantuan Admin (transaksi + check-in) -> revisi -> laporan,
 * dengan stok konsisten, tanpa duplikat, audit log lengkap, dan izin terkunci.
 */
class AssistedOperationsCrossFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function stockOf($product, string $type, int $id): float
    {
        return app(StockService::class)->getQuantity($product->id, $type, $id);
    }

    /** Admin, Sales (+user), outlet, produk @15.000; Sales membawa 10. */
    private function world(): array
    {
        $admin = $this->makeAdminUser();
        [$salesUser, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(15000);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        return [$admin, $salesUser, $sales, $customer, $product];
    }

    private function enterTrx($admin, $sales, $customer, $product, float $qty, string $day, array $extra = [])
    {
        return $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), array_merge([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'transaction_date' => $day,
            'entry_reason' => 'Sales lupa input',
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'price' => 15000]],
        ], $extra));
    }

    private function assistVisit($admin, $sales, $customer, string $day)
    {
        return $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), [
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'visit_date' => $day,
            'visit_time' => '10:00',
            'condition' => 'normal',
            'reason' => 'Sales lupa check-in',
        ]);
    }

    public function test_backdated_assisted_visit_and_transaction_count_as_ec_on_actual_day_and_survive_revision(): void
    {
        [$admin, $salesUser, $sales, $customer, $product] = $this->world();
        $day = now()->subDay()->toDateString();

        $this->assistVisit($admin, $sales, $customer, $day)->assertSessionHasNoErrors();
        $this->enterTrx($admin, $sales, $customer, $product, 4, $day)->assertSessionHasNoErrors();
        $this->assertSame(6.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));

        $report = fn () => $this->actingAs($salesUser)->get(route('sales.transactions.daily', ['date' => $day]))->assertOk();

        $report()->assertViewHas('report', fn (array $r) => $r['summary']['visits'] === 1
            && $r['summary']['ec'] === 1
            && (float) $r['summary']['total_sales'] === 60000.0);

        // Hari ini tidak ikut terhitung.
        $this->actingAs($salesUser)->get(route('sales.transactions.daily'))->assertViewHas('report', fn (array $r) => $r['summary']['visits'] === 0 && $r['summary']['ec'] === 0);

        // Admin merevisi 4 -> 3: stok kembali 1, total laporan 45.000, EC tetap 1.
        $trx = SalesTransaction::firstOrFail();
        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), [
            'reason' => 'Salah input jumlah',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'price' => 15000]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(7.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $report()->assertViewHas('report', fn (array $r) => $r['summary']['ec'] === 1 && (float) $r['summary']['total_sales'] === 45000.0);

        // Audit log lengkap, pelaku Admin.
        foreach ([['create', SalesTransaction::class, $trx->id], ['revise', SalesTransaction::class, $trx->id], ['assisted_check_in', Visit::class, Visit::firstOrFail()->id]] as [$action, $type, $id]) {
            $log = AuditLog::where('action', $action)->where('document_type', $type)->where('document_id', $id)->first();
            $this->assertNotNull($log, "audit $action hilang");
            $this->assertSame($admin->id, $log->user_id);
        }
    }

    public function test_duplicates_are_blocked_across_both_assisted_paths_and_change_nothing(): void
    {
        [$admin, , $sales, $customer, $product] = $this->world();
        $day = now()->subDay()->toDateString();

        $this->enterTrx($admin, $sales, $customer, $product, 4, $day)->assertSessionHasNoErrors();
        $this->enterTrx($admin, $sales, $customer, $product, 4, $day)->assertSessionHasErrors();
        $this->assertSame(1, SalesTransaction::count());
        $this->assertSame(6.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));

        $this->assistVisit($admin, $sales, $customer, $day)->assertSessionHasNoErrors();
        $this->assistVisit($admin, $sales, $customer, $day)->assertSessionHasErrors('customer_id');
        $this->assertSame(1, Visit::count());
        $this->assertSame(1, AuditLog::where('action', 'assisted_check_in')->count());
    }

    public function test_permissions_are_locked_for_sales_role_other_depo_admin_and_guests(): void
    {
        [$admin, $salesUser, $sales, $customer, $product] = $this->world();
        $day = now()->subDay()->toDateString();
        $this->enterTrx($admin, $sales, $customer, $product, 2, $day)->assertSessionHasNoErrors();
        $trx = SalesTransaction::firstOrFail();

        $otherAdmin = $this->makeAdminUser($this->makeBranch('CBG-LAIN', 'Depo Lain'));
        $revise = ['reason' => 'x y z', 'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 15000]]];

        // Role Sales: semua jalur bantuan/revisi terlarang.
        $this->actingAs($salesUser)->get(route('admin.sales.transactions.create-for-sales'))->assertForbidden();
        $this->actingAs($salesUser)->get(route('admin.sales.visits.create-for-sales'))->assertForbidden();
        $this->actingAs($salesUser)->put(route('admin.sales.transactions.update', $trx), $revise)->assertForbidden();

        // Admin Depo lain: tidak bisa merevisi / menginput untuk Sales Depo ini.
        $this->actingAs($otherAdmin)->put(route('admin.sales.transactions.update', $trx), $revise)->assertForbidden();
        $this->enterTrx($otherAdmin, $sales, $customer, $product, 1, $day)->assertForbidden();
        $this->assistVisit($otherAdmin, $sales, $customer, $day)->assertForbidden();

        // Data tidak berubah.
        $this->assertSame(1, SalesTransaction::count());
        $this->assertSame(0, Visit::count());
        $this->assertSame(8.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_bkb_revisions_conserve_total_stock_and_leave_movement_trail(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();
        $stock = app(StockService::class);
        $stock->increase($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, 100, 'adjustment_in');

        $bkb = BkbDistribusi::create(['code' => 'BKB-X1', 'warehouse_id' => $wh->id, 'sales_id' => $sales->id, 'status' => BkbDistribusi::STATUS_APPLIED]);
        $bkb->items()->create(['product_id' => $product->id, 'quantity' => 20]);
        $stock->transfer($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, Stock::LOCATION_SALES, $sales->id, 20, 'bkb_apply', BkbDistribusi::class, $bkb->id);

        foreach ([15, 25, 25, 10] as $qty) {
            $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), [
                'reason' => 'Koreksi jumlah', 'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            ])->assertSessionHasNoErrors();

            $w = $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id);
            $s = $this->stockOf($product, Stock::LOCATION_SALES, $sales->id);
            $this->assertSame(100.0, $w + $s, 'total stok harus tetap');
            $this->assertSame((float) $qty, $s);
        }

        $this->assertGreaterThan(0, StockMovement::where('product_id', $product->id)->count());
        $this->assertSame(4, AuditLog::where('action', 'revise')->where('document_id', $bkb->id)->count()); // setiap revisi tercatat
    }

    public function test_transactions_export_reflects_admin_entry_and_revised_total(): void
    {
        [$admin, , $sales, $customer, $product] = $this->world();
        $this->enterTrx($admin, $sales, $customer, $product, 4, now()->subDay()->toDateString())->assertSessionHasNoErrors();
        $trx = SalesTransaction::firstOrFail();

        $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), [
            'reason' => 'Salah input jumlah',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'price' => 15000]],
        ])->assertSessionHasNoErrors();

        $csv = $this->actingAs($admin)->get(route('admin.sales.transactions.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($trx->code, $csv);
        $this->assertStringContainsString('45000', $csv);
        $this->assertStringNotContainsString('60000', $csv);
    }
}
