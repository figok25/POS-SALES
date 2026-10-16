<?php

namespace Tests\Feature\Sales;

use App\Models\Category;
use App\Models\KpiPeriod;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\KpiAchievementService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Transaksi tambahan outlet yang diinput Admin atas nama Sales (stok Sales terpotong):
 * dihitung sebagai Call Made + EC Sales pada tanggalnya, masuk laporan Sales, dibedakan
 * dari transaksi langsung Sales, dan TIDAK menyentuh data kunjungan (kuota mingguan).
 */
class AdminTransactionCountsAsCallTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

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

    private function enter($admin, $sales, $customer, $product, float $qty = 2, ?string $day = null)
    {
        return $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), [
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'transaction_date' => $day ?? now()->toDateString(),
            'entry_reason' => 'Toko telepon minta tambahan barang',
            'items' => [['product_id' => $product->id, 'quantity' => $qty, 'price' => 15000]],
        ]);
    }

    private function visit($sales, $customer, $at): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'check_in_at' => $at, 'check_out_at' => \Illuminate\Support\Carbon::parse($at)->addMinutes(20),
            'check_in_condition' => Visit::CONDITION_NORMAL, 'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    public function test_extra_purchase_after_weekly_visit_counts_as_call_and_ec_without_touching_visits(): void
    {
        [$admin, $salesUser, $sales, $customer, $product] = $this->world();

        // Sales sudah mengunjungi toko ini (kuota mingguan terpakai) di hari sebelumnya.
        $this->visit($sales, $customer, now()->subDays(2)->setTime(9, 0));

        // Hari ini toko telepon minta tambahan: Admin input atas nama Sales.
        $this->enter($admin, $sales, $customer, $product, 2)->assertSessionHasNoErrors();

        // Stok Sales terpotong, tidak ada data kunjungan baru (kuota mingguan tidak berubah).
        $this->assertSame(8.0, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(1, Visit::count());

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame($sales->id, $trx->sales_id);
        $this->assertSame($admin->id, $trx->created_by);
        $this->assertSame(SalesTransaction::SOURCE_ADMIN, $trx->source);
        $this->assertTrue($trx->isEnteredForSales());

        // Laporan harian Sales: 1 kunjungan (call) + 1 EC pada hari transaksi.
        $this->actingAs($salesUser)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => $r['summary']['visits'] === 1
                && $r['summary']['ec'] === 1
                && (float) $r['summary']['total_sales'] === 30000.0);

        // Dashboard Sales.
        $this->actingAs($salesUser)->get(route('sales.dashboard'))->assertOk()
            ->assertViewHas('kunjunganHariIni', 1)
            ->assertViewHas('ecHariIni', 1);

        // Dashboard Admin: KPI & tabel kunjungan per Sales.
        $today = now()->toDateString();
        $res = $this->actingAs($admin)->get(route('admin.dashboard', ['date_from' => $today, 'date_to' => $today, 'tbl_view' => 'today']))->assertOk();
        $kpi = $res->viewData('kpi');
        $this->assertSame(1, $kpi['call_made']);
        $this->assertSame(1, $kpi['effective_call']);
        $this->assertSame(1, $kpi['visit_stores']);

        $row = $res->viewData('visitCoverage')['rows']->firstWhere('id', $sales->id);
        $this->assertSame(1, $row['call_made']);
        $this->assertSame(1, $row['ec']);
        $this->assertSame(100, $row['ec_pct']);

        // Penjualan per Sales memisahkan transaksi input Admin dari transaksi langsung Sales.
        $perf = $res->viewData('salesPerformance')['rows']->firstWhere('id', $sales->id);
        $this->assertSame(1, $perf['trx_count']);
        $this->assertSame(1, $perf['admin_trx_count']);
        $this->assertSame(30000.0, $perf['admin_trx_total']);
    }

    public function test_same_day_visit_plus_admin_transaction_is_not_double_counted(): void
    {
        [$admin, $salesUser, $sales, $customer, $product] = $this->world();

        $this->visit($sales, $customer, now()->setTime(8, 0));
        $this->enter($admin, $sales, $customer, $product, 1)->assertSessionHasNoErrors();

        $res = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(1, $res->viewData('kpi')['call_made']);
        $this->assertSame(1, $res->viewData('kpi')['effective_call']);

        $this->actingAs($salesUser)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => $r['summary']['visits'] === 1 && $r['summary']['ec'] === 1);
    }

    public function test_kpi_achievement_counts_admin_entered_transaction_once(): void
    {
        [$admin, , $sales, $customer, $product] = $this->world();

        $this->visit($sales, $customer, now()->subDays(2)->setTime(9, 0));
        $this->enter($admin, $sales, $customer, $product, 1)->assertSessionHasNoErrors();

        $period = KpiPeriod::create([
            'branch_id' => $sales->branch_id, 'name' => 'Minggu Uji',
            'start_date' => now()->subDays(3)->toDateString(), 'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $row = app(KpiAchievementService::class)->build($period)['rows'][0];

        $this->assertSame(2, $row['actual']['call_made']); // kunjungan 2 hari lalu + call hari ini dari input Admin
        $this->assertSame(1, $row['actual']['ec']);        // hanya hari ini yang ada transaksi
    }

    public function test_admin_entered_transaction_without_ec_product_does_not_add_a_call(): void
    {
        [, , $sales, $customer] = $this->world();
        $admin = $this->makeAdminUser();

        $rokok = $this->makeProduct();
        $rokok->update(['category_id' => Category::create(['code' => 'RKO', 'name' => 'Rokok', 'counts_as_ec' => true])->id]);
        $kopi = $this->makeProduct();
        $kopi->update(['category_id' => Category::create(['code' => 'KPI', 'name' => 'Kopi', 'counts_as_ec' => false])->id]);
        app(StockService::class)->increase($kopi->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        $this->enter($admin, $sales, $customer, $kopi, 2)->assertSessionHasNoErrors();

        $res = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(0, $res->viewData('kpi')['call_made']);
        $this->assertSame(0, $res->viewData('kpi')['effective_call']);
        // Tetap tercatat sebagai penjualan Sales.
        $this->assertSame(1, $res->viewData('kpi')['sales_count']);
    }

    public function test_voided_admin_entry_no_longer_counts(): void
    {
        [$admin, , $sales, $customer, $product] = $this->world();
        $this->enter($admin, $sales, $customer, $product, 1)->assertSessionHasNoErrors();

        SalesTransaction::query()->update(['status' => SalesTransaction::STATUS_CANCELLED]);

        $kpi = $this->actingAs($admin)->get(route('admin.dashboard'))->viewData('kpi');
        $this->assertSame(0, $kpi['call_made']);
        $this->assertSame(0, $kpi['effective_call']);
    }

    public function test_lists_and_reports_show_who_performed_the_transaction(): void
    {
        [$admin, $salesUser, $sales, $customer, $product] = $this->world();
        $this->enter($admin, $sales, $customer, $product, 1)->assertSessionHasNoErrors();
        $trx = SalesTransaction::firstOrFail();

        // Daftar transaksi Admin: penanda "Input Admin".
        $this->actingAs($admin)->get(route('admin.sales.transactions.index'))->assertOk()->assertSee('Input Admin');

        // Export CSV: sumber + pelaksana (Admin) berbeda dari pemilik penjualan (Sales).
        $csv = $this->actingAs($admin)->get(route('admin.sales.transactions.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Pelaksana', $csv);
        $this->assertStringContainsString('Input Admin untuk Sales', $csv);
        $this->assertStringContainsString($admin->name, $csv);
        $this->assertStringContainsString($sales->name, $csv);

        // Sales App: transaksi terlihat (dan bisa dicetak) + tanda dicatat Admin.
        $this->actingAs($salesUser)->get(route('sales.transactions.index'))->assertOk()
            ->assertSee($trx->code)->assertSee('Dicatat Admin');
        $this->actingAs($salesUser)->get(route('sales.transactions.show', $trx))->assertOk()
            ->assertSee('Dicatat oleh')->assertSee('Cetak Struk');
    }

    public function test_kasir_depo_page_links_to_transaction_for_sales(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->get(route('admin.depo.create'))->assertOk()
            ->assertSee('Transaksi atas Nama Sales')
            ->assertSee(route('admin.sales.transactions.create-for-sales'), false);
    }
}
