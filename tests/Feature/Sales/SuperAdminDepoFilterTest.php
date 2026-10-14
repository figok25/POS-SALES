<?php

namespace Tests\Feature\Sales;

use App\Models\BkbDistribusi;
use App\Models\BtbDistribusi;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\AuditLogger;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Filter Depo Super Admin di menu-menu per-Depo: dipaksakan backend, bekerja
 * bersama cari/tanggal/status/halaman/export, dan tidak bisa dibajak Admin biasa.
 */
class SuperAdminDepoFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function twoDepos(): array
    {
        $a = $this->defaultBranch();
        $b = $this->makeBranch('CBG-B', 'Depo B');

        return [$a, $b];
    }

    private function bkb($branch, string $code, string $date, string $status = BkbDistribusi::STATUS_DRAFT): BkbDistribusi
    {
        $wh = $this->makeWarehouse($branch);
        $sales = $this->makeSales(null, $branch);

        return BkbDistribusi::create(['code' => $code, 'warehouse_id' => $wh->id, 'sales_id' => $sales->id, 'status' => $status, 'document_date' => $date]);
    }

    private function trx($branch, $sales, $customer, string $code): SalesTransaction
    {
        return SalesTransaction::create([
            'code' => $code, 'sales_id' => $sales->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id,
            'subtotal' => 1000, 'discount' => 0, 'tax' => 0, 'total' => 1000,
            'status' => SalesTransaction::STATUS_COMPLETED, 'source' => SalesTransaction::SOURCE_SALES,
        ]);
    }

    public function test_selector_shown_to_super_admin_only_on_branch_scoped_menus(): void
    {
        [$a, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();
        $admin = $this->makeAdminUser($a);

        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['q' => 'abc', 'status' => 'draft']))
            ->assertOk()->assertSee('aria-label="Filter Depo"', false)->assertSee('Depo B')
            ->assertSee('name="q" value="abc"', false);

        $this->actingAs($admin)->get(route('admin.distribution.bkb.index'))
            ->assertOk()->assertDontSee('aria-label="Filter Depo"', false);

        // Master global (mis. Produk) bukan menu per-Depo: tidak ada selector.
        $this->actingAs($super)->get(route('admin.master.products.index'))
            ->assertOk()->assertDontSee('aria-label="Filter Depo"', false);
    }

    public function test_sidebar_links_keep_chosen_depo(): void
    {
        [, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => $b->id]))
            ->assertOk()->assertSee('branch='.$b->id, false);
    }

    public function test_bkb_depo_filter_combined_with_search_date_status_and_pagination(): void
    {
        [$a, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();

        $this->bkb($a, 'BKB-A-001', '2026-10-10');
        $this->bkb($a, 'BKB-A-002', '2026-10-12', BkbDistribusi::STATUS_APPLIED);
        $this->bkb($b, 'BKB-B-001', '2026-10-12');

        $res = $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => $a->id]))->assertOk();
        $res->assertSee('BKB-A-001')->assertSee('BKB-A-002')->assertDontSee('BKB-B-001');

        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => 'all']))
            ->assertSee('BKB-A-001')->assertSee('BKB-B-001');

        // cari + Depo
        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => $a->id, 'q' => 'A-002']))
            ->assertSee('BKB-A-002')->assertDontSee('BKB-A-001')->assertDontSee('BKB-B-001');

        // tanggal + Depo
        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => $a->id, 'date_from' => '2026-10-11', 'date_to' => '2026-10-13']))
            ->assertSee('BKB-A-002')->assertDontSee('BKB-A-001')->assertDontSee('BKB-B-001');

        // status + Depo
        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => $a->id, 'status' => 'applied']))
            ->assertSee('BKB-A-002')->assertDontSee('BKB-A-001');

        // pagination membawa branch
        for ($i = 0; $i < 16; $i++) {
            $this->bkb($a, sprintf('BKB-PG-%02d', $i), '2026-10-09');
        }
        $this->actingAs($super)->get(route('admin.distribution.bkb.index', ['branch' => $a->id]))
            ->assertOk()->assertSee('branch='.$a->id.'&amp;page=2', false);
    }

    public function test_btb_depo_filter_and_search(): void
    {
        [$a, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();

        foreach ([[$a, 'BTB-A-1'], [$b, 'BTB-B-1']] as [$branch, $code]) {
            $wh = $this->makeWarehouse($branch);
            $sales = $this->makeSales(null, $branch);
            BtbDistribusi::create(['code' => $code, 'warehouse_id' => $wh->id, 'sales_id' => $sales->id, 'status' => BtbDistribusi::STATUS_DRAFT]);
        }

        $this->actingAs($super)->get(route('admin.distribution.btb.index', ['branch' => $b->id]))
            ->assertSee('BTB-B-1')->assertDontSee('BTB-A-1');
        $this->actingAs($super)->get(route('admin.distribution.btb.index', ['branch' => 'all', 'q' => 'BTB-A']))
            ->assertSee('BTB-A-1')->assertDontSee('BTB-B-1');
    }

    public function test_warehouse_stock_filtered_by_depo(): void
    {
        [$a, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();
        $whA = $this->makeWarehouse($a);
        $whB = $this->makeWarehouse($b);
        $pA = $this->makeProduct();
        $pB = $this->makeProduct();
        app(StockService::class)->increase($pA->id, Stock::LOCATION_WAREHOUSE, $whA->id, 5, 'adjustment_in');
        app(StockService::class)->increase($pB->id, Stock::LOCATION_WAREHOUSE, $whB->id, 7, 'adjustment_in');

        $this->actingAs($super)->get(route('admin.inventory.stock.index', ['branch' => $a->id]))
            ->assertSee($pA->name)->assertDontSee($pB->name);
        $this->actingAs($super)->get(route('admin.inventory.movements.index', ['branch' => $b->id]))
            ->assertSee($pB->name)->assertDontSee($pA->name);
        $this->actingAs($super)->get(route('admin.inventory.movements.index', ['branch' => $b->id, 'date_from' => now()->addDay()->toDateString()]))
            ->assertDontSee($pB->name);
    }

    public function test_transactions_list_and_export_follow_depo(): void
    {
        [$a, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();
        $sA = $this->makeSales(null, $a);
        $sB = $this->makeSales(null, $b);
        $this->trx($a, $sA, $this->makeCustomer($sA->id, $a), 'TRX-DEPO-A');
        $this->trx($b, $sB, $this->makeCustomer($sB->id, $b), 'TRX-DEPO-B');

        $this->actingAs($super)->get(route('admin.sales.transactions.index', ['branch' => $a->id]))
            ->assertSee('TRX-DEPO-A')->assertDontSee('TRX-DEPO-B');

        $csv = $this->actingAs($super)->get(route('admin.sales.transactions.export', ['branch' => $b->id]))
            ->assertOk()->streamedContent();
        $this->assertStringContainsString('TRX-DEPO-B', $csv);
        $this->assertStringNotContainsString('TRX-DEPO-A', $csv);
    }

    public function test_audit_log_filtered_by_depo(): void
    {
        [$a, $b] = $this->twoDepos();
        $super = $this->makeSuperAdminUser();

        AuditLogger::log('marker_a', 'ModulA', null, null, null, null, $super->id, $a->id);
        AuditLogger::log('marker_b', 'ModulB', null, null, null, null, $super->id, $b->id);

        $this->actingAs($super)->get(route('admin.system.audit-log.index', ['branch' => $a->id]))
            ->assertSee('marker_a')->assertDontSee('marker_b');
        $this->actingAs($super)->get(route('admin.system.audit-log.index', ['branch' => 'all']))
            ->assertSee('marker_a')->assertSee('marker_b');
    }

    public function test_regular_admin_cannot_override_depo_by_query_string(): void
    {
        [$a, $b] = $this->twoDepos();
        $admin = $this->makeAdminUser($a);

        $this->bkb($a, 'BKB-OWN-1', '2026-10-10');
        $this->bkb($b, 'BKB-OTHER-1', '2026-10-10');

        $this->actingAs($admin)->get(route('admin.distribution.bkb.index', ['branch' => $b->id]))
            ->assertOk()->assertSee('BKB-OWN-1')->assertDontSee('BKB-OTHER-1');
        $this->actingAs($admin)->get(route('admin.distribution.bkb.index', ['branch' => 'all']))
            ->assertSee('BKB-OWN-1')->assertDontSee('BKB-OTHER-1');
    }
}
