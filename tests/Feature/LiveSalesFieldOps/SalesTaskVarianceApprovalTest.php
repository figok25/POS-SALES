<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\BkbDistribusi;
use App\Models\SalesTask;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * PERBAIKAN (laporan "Stok Dibawa" vs "Kembali" tidak nyambung di
 * Dashboard): approve selisih stock (SalesTaskController::approveVariance)
 * sekarang WAJIB mengoreksi Sales Stock asli ke angka quantity_verified
 * yang disetujui, bukan cuma mengubah status Task. Sebelumnya
 * quantity_verified cuma catatan laporan, stock asli tidak pernah ikut
 * berubah -- sehingga Dashboard "Stok Dibawa" (pakai quantity_verified)
 * bisa lebih besar dari stock yang sungguhan dimiliki Sales.
 */
class SalesTaskVarianceApprovalTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /** BKB Applied (gudang 100 -> kirim $assigned ke Sales) + Task stock_variance ($verified != $assigned). */
    private function varianceTask(float $assigned, float $verified): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, 100, 'adjustment_in');

        $bkb = BkbDistribusi::create([
            'code' => 'BKB-VAR'.(BkbDistribusi::count() + 1),
            'warehouse_id' => $wh->id,
            'sales_id' => $sales->id,
            'status' => BkbDistribusi::STATUS_APPLIED,
        ]);
        $bkb->items()->create(['product_id' => $product->id, 'quantity' => $assigned]);
        $stock->transfer($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, Stock::LOCATION_SALES, $sales->id, $assigned, 'bkb_apply', BkbDistribusi::class, $bkb->id);

        $task = SalesTask::create([
            'code' => 'TASK-VAR'.(SalesTask::count() + 1),
            'sales_id' => $sales->id,
            'bkb_distribusi_id' => $bkb->id,
            'branch_id' => $sales->branch_id,
            'task_date' => now()->toDateString(),
            'status' => SalesTask::STATUS_STOCK_VARIANCE,
            'applied_by' => $admin->id,
            'applied_at' => now(),
        ]);
        $task->taskStocks()->create([
            'product_id' => $product->id,
            'quantity_assigned' => $assigned,
            'quantity_verified' => $verified,
            'verified_at' => now(),
        ]);

        return [$admin, $sales, $wh, $product, $bkb, $task];
    }

    private function stockOf($product, string $type, int $id): float
    {
        return app(StockService::class)->getQuantity($product->id, $type, $id);
    }

    public function test_approving_higher_verified_quantity_corrects_sales_stock_up(): void
    {
        [$admin, $sales, $wh, $product, , $task] = $this->varianceTask(20, 28);

        $this->assertSame(20.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.approve-variance', $task), ['approval_notes' => 'Sudah dicek ulang'])
            ->assertSessionHas('status');

        $this->assertSame(SalesTask::STATUS_READY_TO_WORK, $task->fresh()->status);
        $this->assertStringContainsString('Variance disetujui', $task->fresh()->notes);

        // Stock Sales ikut terkoreksi ke angka yang disetujui (28), warehouse tidak berubah lagi.
        $this->assertSame(28.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(80.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'location_type' => Stock::LOCATION_SALES,
            'location_id' => $sales->id,
            'movement_type' => 'stock_correction',
            'direction' => 'in',
            'quantity' => 8,
        ]);
    }

    public function test_approving_lower_verified_quantity_corrects_sales_stock_down(): void
    {
        [$admin, $sales, , $product, , $task] = $this->varianceTask(20, 15);

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.approve-variance', $task), [])
            ->assertSessionHas('status');

        $this->assertSame(15.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(SalesTask::STATUS_READY_TO_WORK, $task->fresh()->status);
    }

    public function test_cannot_approve_variance_when_task_not_in_variance_status(): void
    {
        [$admin, $sales, , $product, , $task] = $this->varianceTask(20, 20);
        $task->update(['status' => SalesTask::STATUS_READY_TO_WORK]);

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.approve-variance', $task), [])
            ->assertSessionHas('error');

        // Tidak ada koreksi stock yang terjadi.
        $this->assertSame(20.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(0, StockMovement::where('movement_type', 'stock_correction')->count());
    }

    public function test_admin_of_other_branch_cannot_approve_variance(): void
    {
        [, , , , , $task] = $this->varianceTask(20, 28);
        $otherBranch = $this->makeBranch('CBG-OTHER-VAR', 'Cabang Lain');
        $otherAdmin = $this->makeAdminUser($otherBranch);

        $this->actingAs($otherAdmin)
            ->post(route('admin.sales-tasks.approve-variance', $task), [])
            ->assertForbidden();

        $this->assertSame(SalesTask::STATUS_STOCK_VARIANCE, $task->fresh()->status);
    }
}
