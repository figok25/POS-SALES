<?php

namespace Tests\Feature\Sales;

use App\Models\KpiPeriod;
use App\Models\KpiProduct;
use App\Models\KpiTarget;
use App\Models\SalesTask;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Visit;
use App\Services\KpiAchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Target & Pencapaian: Call Made (termasuk yang transaksi), EC, Absensi per
 * hari kerja Senin-Sabtu, Total Penjualan semua produk + produk KPI.
 */
class KpiAchievementTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    // Senin 5 Okt 2026 s.d. Minggu 11 Okt 2026.
    private const START = '2026-10-05';
    private const END = '2026-10-11';

    private function period($branch = null): KpiPeriod
    {
        $branch ??= $this->defaultBranch();

        return KpiPeriod::create(['branch_id' => $branch->id, 'name' => 'Minggu 41', 'start_date' => self::START, 'end_date' => self::END]);
    }

    private function visit($sales, $customer, string $date, string $condition = Visit::CONDITION_NORMAL): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => "{$date} 09:00:00",
            'check_out_at' => "{$date} 09:30:00",
            'check_in_condition' => $condition,
            'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    private function sale($sales, $customer, string $date, array $items): void
    {
        static $n = 0;
        $n++;

        $trx = new SalesTransaction([
            'code' => "TRX-KPI-{$n}", 'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);
        $trx->created_at = "{$date} 09:15:00";
        $trx->updated_at = "{$date} 09:15:00";
        $trx->save();

        foreach ($items as [$product, $qty]) {
            SalesTransactionItem::create([
                'sales_transaction_id' => $trx->id, 'product_id' => $product->id,
                'quantity' => $qty, 'price' => 1000, 'subtotal' => 1000 * $qty,
            ]);
        }
    }

    private function task($sales, string $date, string $status = SalesTask::STATUS_COMPLETED): void
    {
        static $n = 0;
        $n++;
        SalesTask::create(['code' => "TASK-KPI-{$n}", 'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'task_date' => $date, 'status' => $status]);
    }

    private function scenario(): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $a = $this->makeCustomer($sales->id);
        $b = $this->makeCustomer($sales->id);
        $c = $this->makeCustomer($sales->id);
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        KpiProduct::create(['product_id' => $p1->id, 'sort_order' => 10]);

        $this->visit($sales, $a, '2026-10-05');                                  // + transaksi => EC
        $this->sale($sales, $a, '2026-10-05', [[$p1, 3], [$p2, 2]]);
        $this->visit($sales, $b, '2026-10-05');                                  // tanpa transaksi
        $this->visit($sales, $c, '2026-10-06', Visit::CONDITION_CLOSED);         // toko tutup

        $this->task($sales, '2026-10-05');                                       // Senin: hadir
        $this->task($sales, '2026-10-06', SalesTask::STATUS_WORKING);            // Selasa: hadir
        $this->task($sales, '2026-10-07', SalesTask::STATUS_DRAFT);              // draft: tidak dihitung
        $this->task($sales, '2026-10-11');                                       // Minggu: bukan hari kerja

        return [$user, $sales, $p1, $p2];
    }

    public function test_actuals_are_computed_per_sales(): void
    {
        [, $sales, $p1] = $this->scenario();
        $period = $this->period();
        KpiTarget::create(['kpi_period_id' => $period->id, 'call_made' => 195, 'ec' => 20, 'absensi' => 6, 'product_targets' => [$p1->id => 110]]);

        $result = app(KpiAchievementService::class)->build($period);
        $row = $result['rows'][0];

        $this->assertSame($sales->id, $row['sales']->id);
        $this->assertSame(3, $row['actual']['call_made']);       // 2 buka + 1 tutup
        $this->assertSame(1, $row['actual']['ec']);              // hanya toko A
        $this->assertSame(2, $row['actual']['absensi']);         // Senin & Selasa
        $this->assertSame(3.0, $row['actual']['volume']);        // total produk KPI saja
        $this->assertSame(110.0, $row['target']['volume']);      // jumlah target produk KPI
        $this->assertSame(3.0, $row['actual']['products'][$p1->id]);
        $this->assertSame(192, $row['gap']['call_made']);        // 195 - 3
        $this->assertSame(107.0, $row['gap']['volume']);
        $this->assertSame(2.7, $row['achieve']);
    }

    public function test_admin_dashboard_coverage_matches_kpi_numbers(): void
    {
        $this->scenario();
        $admin = $this->makeAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'tbl_view' => 'all',
            'date_from' => self::START, 'date_to' => self::END,
        ]))->assertOk();

        $totals = $response->viewData('visitCoverage')['totals'];

        $this->assertSame(3, $totals['call_made']);   // 2 buka + 1 tutup, sama dengan KPI
        $this->assertSame(1, $totals['ec']);
    }

    public function test_closed_visits_can_be_excluded_by_config(): void
    {
        $this->scenario();
        config(['kpi.call_made_includes_closed' => false]);

        $row = app(KpiAchievementService::class)->build($this->period())['rows'][0];

        $this->assertSame(2, $row['actual']['call_made']);
    }

    public function test_visits_outside_the_period_are_ignored(): void
    {
        [, $sales] = $this->makeSalesUser();
        $this->visit($sales, $this->makeCustomer($sales->id), '2026-10-12');

        $row = app(KpiAchievementService::class)->build($this->period())['rows'][0];

        $this->assertSame(0, $row['actual']['call_made']);
    }

    public function test_same_target_applies_to_every_sales_and_volume_sums_kpi_products(): void
    {
        [, $a] = $this->makeSalesUser();
        [, $b] = $this->makeSalesUser();
        $vanila = $this->makeProduct();
        $coklat = $this->makeProduct();
        KpiProduct::create(['product_id' => $vanila->id, 'sort_order' => 10]);
        KpiProduct::create(['product_id' => $coklat->id, 'sort_order' => 20]);

        $period = $this->period();
        KpiTarget::create(['kpi_period_id' => $period->id, 'call_made' => 195, 'ec' => 20, 'absensi' => 6,
            'product_targets' => [$vanila->id => 110, $coklat->id => 60]]);

        $result = app(KpiAchievementService::class)->build($period);

        $this->assertCount(2, $result['rows']);
        foreach ($result['rows'] as $row) {
            $this->assertSame(195, $row['target']['call_made']);
            $this->assertSame(170.0, $row['target']['volume']);
        }
        $this->assertSame(340.0, $result['totals']['target']['volume']);
    }

    public function test_other_branch_sales_are_not_listed(): void
    {
        $this->makeSalesUser();
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->makeSalesUser($branchB);

        $this->assertCount(1, app(KpiAchievementService::class)->build($this->period())['rows']);
    }

    // ------------------------------------------------------------ halaman

    public function test_admin_can_create_period_set_targets_and_view_table(): void
    {
        [, $sales, $p1] = $this->scenario();
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->post(route('admin.sales.kpi.periods.store'), [
            'start_date' => self::START, 'end_date' => '2026-10-10',
        ])->assertSessionHasNoErrors();

        $period = KpiPeriod::firstOrFail();
        $this->assertStringContainsString('Minggu ke-41', $period->name);

        $this->actingAs($admin)->get(route('admin.sales.kpi.targets.edit', $period))->assertOk();

        $this->actingAs($admin)->put(route('admin.sales.kpi.targets.update', $period), [
            'name' => 'Minggu 41', 'start_date' => self::START, 'end_date' => '2026-10-10',
            'call_made' => 195, 'ec' => 20, 'absensi' => 6,
            'products' => [$p1->id => 110],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, KpiTarget::count());
        $this->assertSame(195, KpiTarget::first()->call_made);
        $this->assertSame(110.0, KpiTarget::first()->productTarget($p1->id));

        $this->actingAs($admin)->get(route('admin.sales.kpi.index', ['period' => $period->id]))
            ->assertOk()->assertSee($sales->name)->assertSee('Minggu 41');

        $this->actingAs($admin)->get(route('admin.sales.kpi.export', $period))->assertOk();
    }

    public function test_admin_cannot_touch_another_branch_period(): void
    {
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $period = $this->period($branchB);
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->get(route('admin.sales.kpi.targets.edit', $period))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.sales.kpi.export', $period))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.sales.kpi.periods.destroy', $period))->assertForbidden();
    }

    public function test_admin_can_manage_kpi_products(): void
    {
        $admin = $this->makeAdminUser();
        $product = $this->makeProduct();

        $this->actingAs($admin)->get(route('admin.sales.kpi.products.index'))->assertOk();
        $this->actingAs($admin)->post(route('admin.sales.kpi.products.store'), ['product_id' => $product->id])->assertSessionHasNoErrors();
        $this->assertSame(1, KpiProduct::count());

        $this->actingAs($admin)->post(route('admin.sales.kpi.products.store'), ['product_id' => $product->id])->assertSessionHasErrors('product_id');

        $this->actingAs($admin)->delete(route('admin.sales.kpi.products.destroy', KpiProduct::first()))->assertRedirect();
        $this->assertSame(0, KpiProduct::count());
    }

    public function test_sales_sees_only_own_progress(): void
    {
        [$user, $sales] = $this->scenario();
        [, $other] = $this->makeSalesUser();
        $period = $this->period();
        KpiTarget::create(['kpi_period_id' => $period->id, 'call_made' => 195, 'ec' => 20, 'absensi' => 6]);

        $this->actingAs($user)->get(route('sales.kpi.index', ['period' => $period->id]))
            ->assertOk()->assertSee('Call Made')->assertSee('3 / 195');
    }

    public function test_sales_cannot_open_admin_kpi_pages(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->get(route('admin.sales.kpi.index'))->assertForbidden();
    }
}
