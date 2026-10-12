<?php

namespace Tests\Feature\Dashboard;

use App\Models\Customer;
use App\Models\Sales;
use App\Models\SalesTransaction;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * EC dipecah: EC Toko Baru (transaksi selesai pertama toko itu) dan
 * EC Toko Repeat (toko sudah pernah order di hari sebelumnya).
 */
class EffectiveCallSplitTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function trx(Sales $sales, Customer $customer, $when, string $status = SalesTransaction::STATUS_COMPLETED): SalesTransaction
    {
        static $n = 0;
        $n++;

        $trx = SalesTransaction::create([
            'code' => "TRX-ECS-{$n}",
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'subtotal' => 1000, 'discount' => 0, 'tax' => 0, 'total' => 1000,
            'status' => $status,
        ]);
        $trx->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();

        return $trx;
    }

    private function visit(Sales $sales, Customer $customer): void
    {
        Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => now(),
            'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    public function test_admin_dashboard_splits_ec_into_new_and_repeat_stores(): void
    {
        $branch = $this->makeBranch('CBG-E', 'Depo E');
        [, $sales] = $this->makeSalesUser($branch);

        $newStore = $this->makeCustomer($sales->id);      // order pertama hari ini -> EC baru
        $repeatStore = $this->makeCustomer($sales->id);   // sudah order kemarin -> EC repeat
        $cancelledBefore = $this->makeCustomer($sales->id); // order kemarin DIBATALKAN -> hari ini tetap baru
        $noOrder = $this->makeCustomer($sales->id);       // hanya kunjungan -> bukan EC

        $this->trx($sales, $repeatStore, now()->subDay());
        $this->trx($sales, $cancelledBefore, now()->subDay(), SalesTransaction::STATUS_CANCELLED);

        foreach ([$newStore, $repeatStore, $cancelledBefore, $noOrder] as $c) {
            $this->visit($sales, $c);
        }
        $this->trx($sales, $newStore, now());
        $this->trx($sales, $newStore, now());   // order 2x di hari pertama tetap 1 EC baru
        $this->trx($sales, $repeatStore, now());
        $this->trx($sales, $cancelledBefore, now());

        $today = now()->toDateString();
        $response = $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.dashboard', ['date_from' => $today, 'date_to' => $today]))
            ->assertOk();

        $kpi = $response->viewData('kpi');
        $this->assertSame(3, $kpi['effective_call']);
        $this->assertSame(2, $kpi['ec_new']);
        $this->assertSame(1, $kpi['ec_repeat']);

        $row = $response->viewData('visitCoverage')['rows']->firstWhere('id', $sales->id);
        $this->assertSame(3, $row['ec']);
        $this->assertSame(2, $row['ec_new']);
        $this->assertSame(1, $row['ec_repeat']);

        $totals = $response->viewData('visitCoverage')['totals'];
        $this->assertSame(2, $totals['ec_new']);
        $this->assertSame(1, $totals['ec_repeat']);

        $response->assertSee('EC Toko Baru')->assertSee('EC Toko Repeat');
    }

    public function test_store_first_ordered_via_another_sales_counts_as_repeat(): void
    {
        $branch = $this->makeBranch('CBG-F', 'Depo F');
        [, $salesA] = $this->makeSalesUser($branch);
        [, $salesB] = $this->makeSalesUser($branch);

        $store = $this->makeCustomer($salesA->id);
        $this->trx($salesA, $store, now()->subDays(3));

        $this->visit($salesB, $store);
        $this->trx($salesB, $store, now());

        $today = now()->toDateString();
        $kpi = $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.dashboard', ['date_from' => $today, 'date_to' => $today]))
            ->assertOk()->viewData('kpi');

        $this->assertSame(1, $kpi['effective_call']);
        $this->assertSame(0, $kpi['ec_new']);
        $this->assertSame(1, $kpi['ec_repeat']);
    }

    public function test_sales_dashboard_shows_today_ec_split(): void
    {
        [$user, $sales] = $this->makeSalesUser();

        $newStore = $this->makeCustomer($sales->id);
        $repeatStore = $this->makeCustomer($sales->id);
        $this->trx($sales, $repeatStore, now()->subDays(2));

        $this->visit($sales, $newStore);
        $this->visit($sales, $repeatStore);
        $this->trx($sales, $newStore, now());
        $this->trx($sales, $repeatStore, now());

        $response = $this->actingAs($user)->get(route('sales.dashboard'))->assertOk();

        $this->assertSame(2, $response->viewData('ecHariIni'));
        $this->assertSame(1, $response->viewData('ecBaruHariIni'));
        $this->assertSame(1, $response->viewData('ecRepeatHariIni'));
        $response->assertSee('EC Toko Baru')->assertSee('EC Toko Repeat');
    }
}
