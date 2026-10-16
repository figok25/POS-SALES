<?php

namespace Tests\Feature\Dashboard;

use App\Models\Sales;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Dashboard Admin: tiga tabel (Penjualan per Sales, Laporan Global per Depo,
 * Kunjungan per Sales) masing-masing punya filter & export SENDIRI.
 */
class DashboardBoxFiltersTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function sale(Sales $sales, $product, float $qty, float $price, $when): SalesTransaction
    {
        static $n = 0;
        $n++;

        $trx = new SalesTransaction([
            'code' => "TRX-BOX-{$n}", 'sales_id' => $sales->id, 'branch_id' => $sales->branch_id,
            'subtotal' => $qty * $price, 'discount' => 0, 'tax' => 0, 'total' => $qty * $price,
            'status' => SalesTransaction::STATUS_COMPLETED, 'source' => SalesTransaction::SOURCE_SALES,
        ]);
        $trx->created_at = $when;
        $trx->updated_at = $when;
        $trx->save();

        SalesTransactionItem::create([
            'sales_transaction_id' => $trx->id, 'product_id' => $product->id,
            'quantity' => $qty, 'price' => $price, 'subtotal' => $qty * $price,
        ]);

        return $trx;
    }

    private function visit(Sales $sales, $when = null): Visit
    {
        $customer = $this->makeCustomer($sales->id, \App\Models\Branch::find($sales->branch_id));

        return Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'check_in_at' => $when ?? now(), 'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    /** Dua Sales satu Depo: ALFA (kemarin 4 + hari ini 1 pcs) dan BETA (hari ini 2 pcs + 1 kunjungan hari ini). */
    private function scenario(): array
    {
        $admin = $this->makeAdminUser();
        $alfa = $this->makeSales(null, $admin->branch);
        $beta = $this->makeSales(null, $admin->branch);
        $alfa->update(['name' => 'ALFA SALES']);
        $beta->update(['name' => 'BETA SALES']);
        $product = $this->makeProduct(1000);

        $this->sale($alfa, $product, 4, 1000, now()->subDay()->setTime(10, 0));
        $this->sale($alfa, $product, 1, 1000, now()->setTime(9, 0));
        $this->sale($beta, $product, 2, 1000, now()->setTime(9, 30));
        $this->visit($beta, now()->setTime(9, 0));

        return [$admin, $alfa, $beta];
    }

    public function test_each_box_has_its_own_period_independent_of_top_filter_and_other_boxes(): void
    {
        [$admin] = $this->scenario();
        $yesterday = now()->subDay()->toDateString();

        // Hanya box Penjualan diganti ke kemarin; Laporan Global & Kunjungan tetap hari ini.
        // Filter utama (date_from/date_to) sengaja diset ke periode lain: tidak boleh memengaruhi box.
        $res = $this->actingAs($admin)->get(route('admin.dashboard', [
            'date_from' => '2020-01-01', 'date_to' => '2020-01-31',
            'ps_from' => $yesterday, 'ps_to' => $yesterday,
        ]))->assertOk();

        $this->assertEquals(1, $res->viewData('salesPerformance')['totals']['trx_count']);   // hanya ALFA kemarin
        $this->assertEquals(3, $res->viewData('depoReport')['blocks']->first()['totals']['sold']);   // hari ini: 1 + 2
        $this->assertEquals(1, $res->viewData('visitCoverage')['totals']['call_made']);       // 1 kunjungan hari ini
    }

    public function test_sales_filter_of_one_box_does_not_touch_the_others(): void
    {
        [$admin, $alfa] = $this->scenario();

        $res = $this->actingAs($admin)->get(route('admin.dashboard', ['ps_sales' => $alfa->id]))->assertOk();

        $this->assertCount(1, $res->viewData('salesPerformance')['rows']);
        $this->assertCount(2, $res->viewData('visitCoverage')['rows']);
        $this->assertEquals(3, $res->viewData('depoReport')['blocks']->first()['totals']['sold']);
    }

    public function test_global_report_box_supports_date_range_and_sales_filter(): void
    {
        [$admin, $alfa] = $this->scenario();

        $report = $this->actingAs($admin)->get(route('admin.dashboard', [
            'rg_from' => now()->subDay()->toDateString(), 'rg_to' => now()->toDateString(), 'rg_sales' => $alfa->id,
        ]))->assertOk()->viewData('depoReport');

        $this->assertEquals(5, $report['blocks']->first()['totals']['sold']);   // ALFA: 4 + 1
        $this->assertStringContainsString(' - ', $report['label']);
    }

    public function test_super_admin_picks_depo_per_box_and_admin_cannot(): void
    {
        $branchA = $this->defaultBranch();
        $branchB = $this->makeBranch('CBG-BOX', 'Depo BOX');
        $sA = $this->makeSales(null, $branchA);
        $sB = $this->makeSales(null, $branchB);
        $super = $this->makeSuperAdminUser();

        $res = $this->actingAs($super)->get(route('admin.dashboard', ['ps_branch' => $branchB->id]))->assertOk();
        $this->assertEquals([$sB->id], $res->viewData('salesPerformance')['rows']->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$sA->id, $sB->id], $res->viewData('visitCoverage')['rows']->pluck('id')->all());

        $admin = $this->makeAdminUser($branchA);
        $res = $this->actingAs($admin)->get(route('admin.dashboard', ['ps_branch' => $branchB->id]))->assertOk();
        $this->assertNotContains($sB->id, $res->viewData('salesPerformance')['rows']->pluck('id')->all());
    }

    public function test_sales_from_another_depo_is_ignored_in_box_filter(): void
    {
        $branchB = $this->makeBranch('CBG-BOX2', 'Depo BOX2');
        $sB = $this->makeSales(null, $branchB);
        [$admin] = $this->scenario();

        $res = $this->actingAs($admin)->get(route('admin.dashboard', ['ps_sales' => $sB->id]))->assertOk();

        $this->assertCount(2, $res->viewData('salesPerformance')['rows']);   // filter tidak berlaku, Depo sendiri
    }

    public function test_page_shows_three_separate_filters_and_exports(): void
    {
        [$admin] = $this->scenario();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('name="ps_from"', false)
            ->assertSee('name="rg_from"', false)
            ->assertSee('name="kv_from"', false)
            ->assertSee('dashboard/export/sales', false)
            ->assertSee('dashboard/export/report', false)
            ->assertSee('dashboard/export/visits', false)
            ->assertDontSee('mengikuti filter tabel Penjualan per Sales');
    }

    public function test_box_export_contains_only_that_box_and_follows_its_filter(): void
    {
        [$admin, $alfa] = $this->scenario();
        $today = now()->toDateString();

        $xlsx = $this->actingAs($admin)->get(route('admin.dashboard.export-box', [
            'box' => 'sales', 'format' => 'xlsx', 'ps_from' => $today, 'ps_to' => $today, 'ps_sales' => $alfa->id,
        ]));
        $xlsx->assertOk();
        $book = IOFactory::load($this->tmp($xlsx->getContent()));
        $this->assertSame(['Penjualan per Sales', 'Rincian Produk'], $book->getSheetNames());
        $cells = collect($book->getSheet(0)->toArray())->flatten();
        $this->assertTrue($cells->contains('ALFA SALES'));
        $this->assertFalse($cells->contains('BETA SALES'));

        $visits = $this->actingAs($admin)->get(route('admin.dashboard.export-box', ['box' => 'visits', 'format' => 'xlsx']));
        $this->assertSame(['Kunjungan per Sales'], IOFactory::load($this->tmp($visits->getContent()))->getSheetNames());

        $report = $this->actingAs($admin)->get(route('admin.dashboard.export-box', ['box' => 'report', 'format' => 'xlsx']));
        $this->assertSame(['Laporan Depo', 'Setoran Depo'], IOFactory::load($this->tmp($report->getContent()))->getSheetNames());

        foreach (['sales', 'report', 'visits'] as $box) {
            $pdf = $this->actingAs($admin)->get(route('admin.dashboard.export-box', ['box' => $box, 'format' => 'pdf']));
            $pdf->assertOk();
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        }
    }

    public function test_box_export_rejects_unknown_box_and_sales_users(): void
    {
        [$admin] = $this->scenario();
        [$salesUser] = $this->makeSalesUser();

        $this->actingAs($admin)->get('/admin/dashboard/export/other?format=xlsx')->assertNotFound();
        $this->actingAs($salesUser)->get(route('admin.dashboard.export-box', ['box' => 'sales', 'format' => 'xlsx']))->assertForbidden();
    }

    private function tmp(string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($tmp, $content);

        return $tmp;
    }
}
