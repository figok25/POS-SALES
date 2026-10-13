<?php

namespace Tests\Feature\Sales;

use App\Models\SalesTransaction;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Filter lengkap menu Kunjungan Admin (tanggal, Depo, Sales, kondisi, hasil, cari).
 */
class AdminVisitFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function visit($sales, $customer, string $date, string $condition = Visit::CONDITION_NORMAL, string $status = Visit::STATUS_COMPLETED): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'check_in_at' => $date.' 09:00:00',
            'check_out_at' => $status === Visit::STATUS_COMPLETED ? $date.' 09:30:00' : null,
            'check_in_condition' => $condition, 'status' => $status,
        ]);
    }

    private function trx($sales, $customer, string $date): SalesTransaction
    {
        $t = new SalesTransaction([
            'code' => 'TRX-V-'.uniqid(), 'sales_id' => $sales->id, 'branch_id' => $sales->branch_id,
            'customer_id' => $customer->id, 'subtotal' => 1000, 'discount' => 0, 'tax' => 0, 'total' => 1000,
            'status' => SalesTransaction::STATUS_COMPLETED, 'source' => SalesTransaction::SOURCE_SALES,
        ]);
        $t->created_at = $date.' 09:15:00';
        $t->updated_at = $date.' 09:15:00';
        $t->save();

        return $t;
    }

    public function test_filters_by_date_range_sales_and_search(): void
    {
        $admin = $this->makeAdminUser();
        $s1 = $this->makeSales(null, $admin->branch);
        $s2 = $this->makeSales(null, $admin->branch);
        $c1 = $this->makeCustomer($s1->id, $admin->branch);
        $c2 = $this->makeCustomer($s2->id, $admin->branch);

        $this->visit($s1, $c1, '2026-10-10');
        $this->visit($s1, $c1, '2026-10-12');
        $this->visit($s2, $c2, '2026-10-12');

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.index', ['date_from' => '2026-10-11', 'date_to' => '2026-10-13']))->assertOk();
        $this->assertSame(2, $res->viewData('summary')['count']);

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.index', ['sales_id' => $s2->id]))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee($c2->name)->assertDontSee($c1->name);

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.index', ['q' => $c1->name]))->assertOk();
        $this->assertSame(2, $res->viewData('summary')['count']);

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.index', ['date_from' => 'bukan-tanggal']))->assertOk();
        $this->assertSame(3, $res->viewData('summary')['count']);
    }

    public function test_filters_by_condition_and_outcome(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $a = $this->makeCustomer($sales->id, $admin->branch);
        $b = $this->makeCustomer($sales->id, $admin->branch);
        $c = $this->makeCustomer($sales->id, $admin->branch);
        $d = $this->makeCustomer($sales->id, $admin->branch);

        $this->visit($sales, $a, '2026-10-12');                                   // EC
        $this->trx($sales, $a, '2026-10-12');
        $this->visit($sales, $b, '2026-10-12');                                   // call made
        $this->visit($sales, $c, '2026-10-12', Visit::CONDITION_CLOSED);          // tutup
        $this->visit($sales, $d, '2026-10-12', Visit::CONDITION_NORMAL, Visit::STATUS_ONGOING); // berjalan

        $count = fn (array $q) => $this->actingAs($admin)->get(route('admin.sales.visits.index', $q))->assertOk()->viewData('summary')['count'];

        $this->assertSame(1, $count(['outcome' => 'transaction']));
        $this->assertSame(1, $count(['outcome' => 'no_transaction']));
        $this->assertSame(1, $count(['outcome' => 'closed']));
        $this->assertSame(1, $count(['outcome' => 'ongoing']));
        $this->assertSame(1, $count(['condition' => 'closed']));
        $this->assertSame(3, $count(['condition' => 'normal']));
        $this->assertSame(1, $count(['status' => 'ongoing']));
        $this->assertSame(3, $count(['status' => 'completed']));
    }

    public function test_super_admin_filters_by_depo_and_admin_is_locked(): void
    {
        $superAdmin = $this->makeSuperAdminUser();
        $branchA = $this->defaultBranch();
        $branchB = $this->makeBranch('CBG-VB', 'Depo VB');
        $sA = $this->makeSales(null, $branchA);
        $sB = $this->makeSales(null, $branchB);
        $cA = $this->makeCustomer($sA->id, $branchA);
        $cB = $this->makeCustomer($sB->id, $branchB);

        $this->visit($sA, $cA, '2026-10-12');
        $this->visit($sB, $cB, '2026-10-12');

        $res = $this->actingAs($superAdmin)->get(route('admin.sales.visits.index'))->assertOk();
        $this->assertSame(2, $res->viewData('summary')['count']);

        $res = $this->actingAs($superAdmin)->get(route('admin.sales.visits.index', ['branch' => $branchB->id]))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee($cB->name)->assertDontSee($cA->name);

        $admin = $this->makeAdminUser($branchA);
        $res = $this->actingAs($admin)->get(route('admin.sales.visits.index', ['branch' => $branchB->id]))->assertOk();
        $this->assertSame(1, $res->viewData('summary')['count']);
        $res->assertSee($cA->name)->assertDontSee($cB->name);
    }
}
