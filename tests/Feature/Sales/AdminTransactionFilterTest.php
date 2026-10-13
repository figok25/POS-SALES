<?php

namespace Tests\Feature\Sales;

use App\Models\SalesTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Filter lengkap menu Transaksi Penjualan Admin (tanggal, Depo, Sales, status, cari).
 */
class AdminTransactionFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private int $seq = 0;

    private function trx(array $attrs, string $date): SalesTransaction
    {
        $this->seq++;

        $t = new SalesTransaction(array_merge([
            'code' => 'TRX-F-'.$this->seq,
            'subtotal' => 10000, 'discount' => 0, 'tax' => 0, 'total' => 10000,
            'status' => SalesTransaction::STATUS_COMPLETED,
            'source' => SalesTransaction::SOURCE_SALES,
        ], $attrs));
        $t->created_at = $date.' 10:00:00';
        $t->updated_at = $date.' 10:00:00';
        $t->save();

        return $t;
    }

    public function test_filters_by_date_range_and_shows_summary(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $this->trx(['sales_id' => $sales->id, 'branch_id' => $admin->branch_id], '2026-10-10');
        $this->trx(['sales_id' => $sales->id, 'branch_id' => $admin->branch_id], '2026-10-12');
        $this->trx(['sales_id' => $sales->id, 'branch_id' => $admin->branch_id], '2026-10-14');

        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', [
            'date_from' => '2026-10-11', 'date_to' => '2026-10-13',
        ]))->assertOk();

        $res->assertSee('TRX-F-2')->assertDontSee('TRX-F-1')->assertDontSee('TRX-F-3');
        $this->assertSame(1, $res->viewData('summary')['count']);

        // Hanya tanggal awal.
        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['date_from' => '2026-10-12']))->assertOk();
        $this->assertSame(2, $res->viewData('summary')['count']);

        // Tanggal tidak valid diabaikan.
        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['date_from' => 'abc']))->assertOk();
        $this->assertSame(3, $res->viewData('summary')['count']);
    }

    public function test_filters_by_status_sales_and_search(): void
    {
        $admin = $this->makeAdminUser();
        $s1 = $this->makeSales(null, $admin->branch);
        $s2 = $this->makeSales(null, $admin->branch);
        $c1 = $this->makeCustomer($s1->id, $admin->branch);

        $this->trx(['sales_id' => $s1->id, 'branch_id' => $admin->branch_id, 'customer_id' => $c1->id], '2026-10-12');
        $this->trx(['sales_id' => $s2->id, 'branch_id' => $admin->branch_id, 'status' => SalesTransaction::STATUS_CANCELLED, 'total' => 5000], '2026-10-12');

        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['status' => 'cancelled']))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee('TRX-F-2')->assertDontSee('TRX-F-1');

        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['sales_id' => $s1->id]))->assertOk();
        $res->assertSee('TRX-F-1')->assertDontSee('TRX-F-2');
        $this->assertEquals(10000, $res->viewData('summary')['total']);

        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['q' => $c1->name]))->assertOk();
        $res->assertSee('TRX-F-1')->assertDontSee('TRX-F-2');

        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['q' => 'TRX-F-2']))->assertOk();
        $res->assertSee('TRX-F-2')->assertDontSee('TRX-F-1');
    }

    public function test_super_admin_can_filter_by_depo_but_admin_is_locked(): void
    {
        $superAdmin = $this->makeSuperAdminUser();
        $branchA = $this->defaultBranch();
        $branchB = $this->makeBranch('CBG-B', 'Depo B');
        $sA = $this->makeSales(null, $branchA);
        $sB = $this->makeSales(null, $branchB);

        $this->trx(['sales_id' => $sA->id, 'branch_id' => $branchA->id], '2026-10-12');
        $this->trx(['sales_id' => $sB->id, 'branch_id' => $branchB->id], '2026-10-12');

        $res = $this->actingAs($superAdmin)->get(route('admin.sales.transactions.index'))->assertOk();
        $this->assertSame(2, $res->viewData('summary')['count']);

        $res = $this->actingAs($superAdmin)->get(route('admin.sales.transactions.index', ['branch' => $branchB->id]))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee('TRX-F-2')->assertDontSee('TRX-F-1');

        // Admin Depo A tidak bisa melihat Depo B walau memaksa ?branch=.
        $admin = $this->makeAdminUser($branchA);
        $res = $this->actingAs($admin)->get(route('admin.sales.transactions.index', ['branch' => $branchB->id]))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee('TRX-F-1')->assertDontSee('TRX-F-2');
    }

    public function test_depo_transactions_page_filters_by_date_and_stays_separate(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse($admin->branch);
        $sales = $this->makeSales(null, $admin->branch);

        $this->trx(['sales_id' => null, 'branch_id' => $admin->branch_id, 'warehouse_id' => $warehouse->id, 'source' => SalesTransaction::SOURCE_ADMIN], '2026-10-12');
        $this->trx(['sales_id' => null, 'branch_id' => $admin->branch_id, 'warehouse_id' => $warehouse->id, 'source' => SalesTransaction::SOURCE_ADMIN], '2026-10-05');
        $this->trx(['sales_id' => $sales->id, 'branch_id' => $admin->branch_id], '2026-10-12');

        $res = $this->actingAs($admin)->get(route('admin.depo.transactions.index', ['date_from' => '2026-10-10']))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee('TRX-F-1')->assertDontSee('TRX-F-2')->assertDontSee('TRX-F-3');
    }

    public function test_export_follows_filters(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $this->trx(['sales_id' => $sales->id, 'branch_id' => $admin->branch_id], '2026-10-10');
        $this->trx(['sales_id' => $sales->id, 'branch_id' => $admin->branch_id], '2026-10-12');

        $content = $this->actingAs($admin)
            ->get(route('admin.sales.transactions.export', ['date_from' => '2026-10-12']))
            ->assertOk()->streamedContent();

        $this->assertStringContainsString('TRX-F-2', $content);
        $this->assertStringNotContainsString('TRX-F-1', $content);
    }
}
