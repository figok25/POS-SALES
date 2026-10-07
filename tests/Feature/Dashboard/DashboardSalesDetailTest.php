<?php

namespace Tests\Feature\Dashboard;

use App\Models\Customer;
use App\Models\Sales;
use App\Models\SalesTaskStock;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Dashboard Admin: rincian produk di tabel "Penjualan per Sales" dan tabel
 * baru "Kunjungan per Sales" (Call Made & EC).
 *
 * Definisi (sama dengan kartu KPI): 1 call = Sales + Toko + Hari.
 *  - Call Made = hanya kunjungan (check-in, TANPA transaksi selesai)
 *  - EC        = kunjungan + transaksi COMPLETED di hari yang sama
 *  - Total     = Call Made + EC (seluruh toko yang dikunjungi)
 */
class DashboardSalesDetailTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /**
     * @param  array<int, array{product: \App\Models\Product, qty: float, price: float}>  $lines
     */
    private function transaction(Sales $sales, Customer $customer, array $lines, string $status = SalesTransaction::STATUS_COMPLETED): SalesTransaction
    {
        static $n = 0;
        $n++;

        $subtotal = collect($lines)->sum(fn ($l) => $l['qty'] * $l['price']);

        $trx = SalesTransaction::create([
            'code' => "TRX-DSH-{$n}",
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
            'status' => $status,
        ]);

        foreach ($lines as $l) {
            SalesTransactionItem::create([
                'sales_transaction_id' => $trx->id,
                'product_id' => $l['product']->id,
                'quantity' => $l['qty'],
                'price' => $l['price'],
                'subtotal' => $l['qty'] * $l['price'],
            ]);
        }

        return $trx;
    }

    private function visit(Sales $sales, Customer $customer, $checkInAt = null): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => $checkInAt ?? now(),
            'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    // ---------------------------------------------------------------
    // Rincian produk per Sales
    // ---------------------------------------------------------------

    public function test_sales_performance_lists_products_with_carried_sold_and_value(): void
    {
        $branch = $this->makeBranch('CBG-D', 'Depo D');
        [, $sales] = $this->makeSalesUser($branch);
        $customer = $this->makeCustomer($sales->id);
        $productA = $this->makeProduct(5000);
        $productB = $this->makeProduct(2000);

        // Sales membawa produk A (10, diverifikasi 9) dan B (20, belum diverifikasi).
        $task = $this->makeSalesTask($sales);
        SalesTaskStock::create(['sales_task_id' => $task->id, 'product_id' => $productA->id, 'quantity_assigned' => 10, 'quantity_verified' => 9]);
        SalesTaskStock::create(['sales_task_id' => $task->id, 'product_id' => $productB->id, 'quantity_assigned' => 20]);

        $this->transaction($sales, $customer, [
            ['product' => $productA, 'qty' => 3, 'price' => 5000],
            ['product' => $productB, 'qty' => 5, 'price' => 2000],
        ]);
        $this->transaction($sales, $customer, [['product' => $productA, 'qty' => 1, 'price' => 5000]]);
        // Transaksi dibatalkan TIDAK boleh ikut terhitung.
        $this->transaction($sales, $customer, [['product' => $productA, 'qty' => 50, 'price' => 5000]], SalesTransaction::STATUS_CANCELLED);

        $response = $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($productA->name)
            ->assertSee($productB->name);

        $row = $response->viewData('salesPerformance')['rows']->firstWhere('id', $sales->id);

        // Ringkasan baris Sales (perilaku lama tetap benar).
        $this->assertEquals(29, $row['carried_qty']);   // 9 (verified) + 20 (assigned)
        $this->assertEquals(9, $row['sold_qty']);       // 3 + 5 + 1
        $this->assertEquals(30000, $row['sold_value']); // 15.000 + 10.000 + 5.000

        // Rincian per produk.
        $products = collect($row['products'])->keyBy('name');
        $this->assertCount(2, $products);

        $this->assertEquals(9, $products[$productA->name]['carried_qty']);
        $this->assertEquals(4, $products[$productA->name]['sold_qty']);
        $this->assertEquals(20000, $products[$productA->name]['sold_value']);

        $this->assertEquals(20, $products[$productB->name]['carried_qty']);
        $this->assertEquals(5, $products[$productB->name]['sold_qty']);
        $this->assertEquals(10000, $products[$productB->name]['sold_value']);
    }

    public function test_product_detail_includes_products_sold_but_not_in_task_stock(): void
    {
        $branch = $this->makeBranch('CBG-D', 'Depo D');
        [, $sales] = $this->makeSalesUser($branch);
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(3000);

        $this->transaction($sales, $customer, [['product' => $product, 'qty' => 2, 'price' => 3000]]);

        $response = $this->actingAs($this->makeAdminUser($branch))->get(route('admin.dashboard'))->assertOk();
        $row = $response->viewData('salesPerformance')['rows']->firstWhere('id', $sales->id);

        $this->assertCount(1, $row['products']);
        $this->assertEquals(0, $row['products'][0]['carried_qty']);
        $this->assertEquals(2, $row['products'][0]['sold_qty']);
        $this->assertEquals(6000, $row['products'][0]['sold_value']);
    }

    // ---------------------------------------------------------------
    // Kunjungan per Sales: Call Made & EC
    // ---------------------------------------------------------------

    public function test_visit_coverage_counts_call_made_and_ec_per_sales(): void
    {
        $branch = $this->makeBranch('CBG-D', 'Depo D');
        [, $sales] = $this->makeSalesUser($branch);
        $product = $this->makeProduct(5000);

        $c1 = $this->makeCustomer($sales->id); // dikunjungi 2x hari ini + transaksi selesai -> EC
        $c2 = $this->makeCustomer($sales->id); // dikunjungi, tanpa transaksi -> Call Made
        $c3 = $this->makeCustomer($sales->id); // dikunjungi KEMARIN saja -> tidak masuk periode
        $c4 = $this->makeCustomer($sales->id); // dikunjungi + transaksi DIBATALKAN -> Call Made
        $c5 = $this->makeCustomer($sales->id); // transaksi tanpa kunjungan -> bukan call

        $this->visit($sales, $c1);
        $this->visit($sales, $c1);              // kunjungan berulang di hari yang sama = 1 call
        $this->visit($sales, $c2);
        $this->visit($sales, $c3, now()->subDay());
        $this->visit($sales, $c4);

        $line = [['product' => $product, 'qty' => 1, 'price' => 5000]];
        $this->transaction($sales, $c1, $line);
        $this->transaction($sales, $c4, $line, SalesTransaction::STATUS_CANCELLED);
        $this->transaction($sales, $c5, $line);

        // Rentang KPI disamakan dengan tabel (hari ini); tanpa ini KPI memakai
        // bulan berjalan sehingga kunjungan "kemarin" ikut terhitung di KPI.
        $today = now()->toDateString();
        $response = $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.dashboard', ['date_from' => $today, 'date_to' => $today]))
            ->assertOk();
        $coverage = $response->viewData('visitCoverage');
        $row = $coverage['rows']->firstWhere('id', $sales->id);

        $this->assertSame(3, $row['call_made']);    // total kunjungan (termasuk EC): c1, c2, c4
        $this->assertSame(1, $row['ec']);           // kunjungan + transaksi: c1
        $this->assertSame(3, $row['total_calls']);  // c1, c2, c4
        $this->assertSame(33, $row['ec_pct']);      // 1 dari 3
        $this->assertSame(3, $row['stores']);

        $this->assertSame(3, $coverage['totals']['call_made']);
        $this->assertSame(1, $coverage['totals']['ec']);
        $this->assertSame(3, $coverage['totals']['total_calls']);

        // Konsisten dengan kartu KPI agregat di atasnya.
        $this->assertSame(3, $response->viewData('kpi')['visit_total']);
        $this->assertSame(3, $response->viewData('kpi')['call_made']);
        $this->assertSame(1, $response->viewData('kpi')['effective_call']);

        $response->assertSee('Kunjungan per Sales');
    }

    public function test_visit_coverage_has_one_row_per_sales_and_filter_limits_it(): void
    {
        $branch = $this->makeBranch('CBG-D', 'Depo D');
        [, $salesOne] = $this->makeSalesUser($branch);
        [, $salesTwo] = $this->makeSalesUser($branch);

        $this->visit($salesOne, $this->makeCustomer($salesOne->id));
        $this->visit($salesTwo, $this->makeCustomer($salesTwo->id));
        $this->visit($salesTwo, $this->makeCustomer($salesTwo->id));

        $admin = $this->makeAdminUser($branch);

        $all = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->viewData('visitCoverage');
        $this->assertSame(1, $all['rows']->firstWhere('id', $salesOne->id)['call_made']);
        $this->assertSame(2, $all['rows']->firstWhere('id', $salesTwo->id)['call_made']);
        $this->assertSame(3, $all['totals']['call_made']);   // tanpa transaksi -> semua Call Made
        $this->assertSame(3, $all['totals']['total_calls']);
        $this->assertSame(0, $all['totals']['ec']);

        // Filter tabel (tbl_sales) berlaku untuk kedua tabel sekaligus.
        $filtered = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tbl_sales' => $salesTwo->id]))
            ->assertOk();

        $coverage = $filtered->viewData('visitCoverage');
        $this->assertCount(1, $coverage['rows']);
        $this->assertSame($salesTwo->id, $coverage['rows']->first()['id']);
        $this->assertCount(1, $filtered->viewData('salesPerformance')['rows']);
    }

    public function test_dashboard_renders_when_there_are_transactions_but_no_visits(): void
    {
        // Regresi: kartu KPI EC memanggil intersect() pada kunjungan yang KOSONG
        // (koleksi Eloquent kosong) -> 500 "getKey() on string" begitu ada
        // transaksi pada periode itu tanpa satu pun kunjungan.
        $branch = $this->makeBranch('CBG-D', 'Depo D');
        [, $sales] = $this->makeSalesUser($branch);
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(5000);

        $this->transaction($sales, $customer, [['product' => $product, 'qty' => 2, 'price' => 5000]]);

        $today = now()->toDateString();
        $response = $this->actingAs($this->makeAdminUser($branch))
            ->get(route('admin.dashboard', ['date_from' => $today, 'date_to' => $today]))
            ->assertOk();

        $this->assertSame(0, $response->viewData('kpi')['visit_total']);
        $this->assertSame(0, $response->viewData('kpi')['effective_call']);
        $this->assertSame(1, $response->viewData('kpi')['sales_count']);
        $this->assertSame(0, $response->viewData('visitCoverage')['totals']['call_made']);
        $this->assertSame(0, $response->viewData('visitCoverage')['totals']['total_calls']);
        $this->assertSame(0, $response->viewData('visitCoverage')['totals']['ec']);
    }

    // ---------------------------------------------------------------
    // Isolasi Depo
    // ---------------------------------------------------------------

    public function test_admin_only_sees_own_branch_sales_in_both_tables(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);
        $product = $this->makeProduct(5000);

        foreach ([$salesA, $salesB] as $sales) {
            $customer = $this->makeCustomer($sales->id);
            $this->visit($sales, $customer);
            $this->transaction($sales, $customer, [['product' => $product, 'qty' => 1, 'price' => 5000]]);
        }

        $response = $this->actingAs($this->makeAdminUser($branchA))->get(route('admin.dashboard'))->assertOk();

        $this->assertSame([$salesA->id], $response->viewData('visitCoverage')['rows']->pluck('id')->all());
        $this->assertSame([$salesA->id], $response->viewData('salesPerformance')['rows']->pluck('id')->all());
        $this->assertSame(1, $response->viewData('visitCoverage')['totals']['ec']);
    }

    public function test_super_admin_sees_all_branches_in_both_tables(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);

        $this->visit($salesA, $this->makeCustomer($salesA->id));
        $this->visit($salesB, $this->makeCustomer($salesB->id));

        $response = $this->actingAs($this->makeSuperAdminUser())->get(route('admin.dashboard'))->assertOk();

        $ids = $response->viewData('visitCoverage')['rows']->pluck('id')->sort()->values()->all();
        $this->assertSame([$salesA->id, $salesB->id], $ids);
        $this->assertSame(2, $response->viewData('visitCoverage')['totals']['total_calls']);
    }
}
