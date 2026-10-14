<?php

namespace Tests\Feature\Dashboard;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Price;
use App\Models\SalesTaskStock;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Dashboard Admin: Laporan Global Seluruh Sales per Depo (harian / mingguan).
 */
class DepoReportTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function sale($sales, $product, float $qty, float $price, $when, float $free = 0, ?string $method = null): SalesTransaction
    {
        static $n = 0;
        $n++;

        $trx = new SalesTransaction([
            'code' => "TRX-RPT-{$n}", 'sales_id' => $sales->id, 'branch_id' => $sales->branch_id,
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
        if ($free > 0) {
            SalesTransactionItem::create([
                'sales_transaction_id' => $trx->id, 'product_id' => $product->id,
                'quantity' => $free, 'price' => 0, 'subtotal' => 0, 'is_free' => true,
            ]);
        }

        if ($method) {
            $inv = Invoice::create([
                'sales_transaction_id' => $trx->id, 'code' => "INV-RPT-{$n}", 'sales_id' => $sales->id,
                'branch_id' => $sales->branch_id, 'consumer_name' => 'Umum', 'date' => $when->toDateString(),
                'subtotal' => $qty * $price, 'discount' => 0, 'tax' => 0, 'grand_total' => $qty * $price,
                'paid_amount' => $qty * $price, 'status' => Invoice::STATUS_PAID,
            ]);
            Payment::create([
                'code' => "PAY-RPT-{$n}", 'invoice_id' => $inv->id, 'amount' => $qty * $price,
                'method' => $method, 'paid_at' => $when->toDateString(),
            ]);
        }

        return $trx;
    }

    private function carry($sales, $product, float $qty, $date): void
    {
        $task = $this->makeSalesTask($sales);
        $task->update(['task_date' => $date->toDateString()]);
        SalesTaskStock::create([
            'sales_task_id' => $task->id, 'product_id' => $product->id,
            'quantity_assigned' => $qty, 'quantity_verified' => $qty,
        ]);
    }

    public function test_daily_report_per_depo_with_totals_and_payment_split(): void
    {
        $admin = $this->makeAdminUser();
        $s1 = $this->makeSales(null, $admin->branch);
        $s2 = $this->makeSales(null, $admin->branch);
        $product = $this->makeProduct(9000);
        Price::where('product_id', $product->id)->update(['price_type' => Price::TYPE_RETAIL]);

        $this->carry($s1, $product, 50, now());
        $this->carry($s2, $product, 30, now());
        $this->sale($s1, $product, 20, 9000, now(), 2, Payment::METHOD_CASH);
        $this->sale($s2, $product, 10, 9000, now(), 0, Payment::METHOD_TRANSFER);
        $this->sale($s2, $product, 5, 9000, now()); // belum dibayar

        $res = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $report = $res->viewData('depoReport');
        $blk = $report['blocks']->first();
        $row = $blk['rows']->first();

        $this->assertEquals(9000, $row['price']);
        $this->assertEquals(80, $row['start']);
        $this->assertEquals(35, $row['sold']);
        $this->assertEquals(2, $row['free']);
        $this->assertEquals(43, $row['left']);              // 80 - 35 - 2
        $this->assertEquals(315000, $row['total']);
        $this->assertEquals(180000, $blk['cash']);
        $this->assertEquals(90000, $blk['transfer']);
        $this->assertEquals(270000, $blk['settled']);
        $this->assertEquals(45000, $blk['unpaid']);

        $res->assertSee('REPORT GLOBAL ALL SALES')->assertSee('TOTAL SETORAN ALL SALES');
    }

    public function test_weekly_view_covers_whole_iso_week_but_daily_does_not(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $product = $this->makeProduct(1000);

        $monday = now()->startOfWeek()->setTime(9, 0);
        $this->sale($sales, $product, 4, 1000, $monday);
        $this->sale($sales, $product, 6, 1000, $monday->copy()->addDays(2));
        $this->sale($sales, $product, 100, 1000, $monday->copy()->subWeek()); // minggu lalu

        $week = $this->actingAs($admin)->get(route('admin.dashboard', [
            'rpt_view' => 'week', 'rpt_date' => $monday->copy()->addDays(3)->toDateString(),
        ]))->assertOk()->viewData('depoReport');
        $this->assertEquals(10, $week['blocks']->first()['totals']['sold']);
        $this->assertStringContainsString('WEEK', $week['label']);

        $day = $this->actingAs($admin)->get(route('admin.dashboard', [
            'rpt_view' => 'date', 'rpt_date' => $monday->toDateString(),
        ]))->assertOk()->viewData('depoReport');
        $this->assertEquals(4, $day['blocks']->first()['totals']['sold']);
    }

    public function test_admin_sees_only_own_depo_and_super_admin_sees_each_depo_with_activity(): void
    {
        $branchA = $this->defaultBranch();
        $branchB = $this->makeBranch('CBG-RPT', 'Depo RPT');
        $sA = $this->makeSales(null, $branchA);
        $sB = $this->makeSales(null, $branchB);
        $product = $this->makeProduct(2000);

        $this->sale($sA, $product, 3, 2000, now());
        $this->sale($sB, $product, 7, 2000, now());

        $admin = $this->makeAdminUser($branchA);
        $blocks = $this->actingAs($admin)->get(route('admin.dashboard', ['branch' => $branchB->id]))
            ->assertOk()->viewData('depoReport')['blocks'];
        $this->assertCount(1, $blocks);
        $this->assertSame($branchA->id, $blocks->first()['branch']->id);
        $this->assertEquals(3, $blocks->first()['totals']['sold']);

        $super = $this->makeSuperAdminUser();
        $blocks = $this->actingAs($super)->get(route('admin.dashboard'))->assertOk()->viewData('depoReport')['blocks'];
        $this->assertCount(2, $blocks);
        $this->assertEquals([3.0, 7.0], $blocks->map(fn ($b) => $b['totals']['sold'])->sort()->values()->all());
    }
}
