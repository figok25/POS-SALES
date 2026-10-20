<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\BkbDistribusi;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Sales Task yang sudah Applied/Released tapi Sales ternyata tidak jadi
 * berangkat/tidak masuk kerja: Admin bisa membatalkan Task sekaligus
 * mengembalikan stock BKB ke Warehouse lewat satu aksi
 * (SalesTaskController::voidApplied -> DistributionRevisionService::voidBkb).
 */
class SalesTaskVoidAppliedTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /** BKB Applied (gudang 100 -> kirim $qty ke Sales) + Sales Task yang mengikatnya. */
    private function appliedTask(float $qty = 20, string $taskStatus = SalesTask::STATUS_DOCUMENT_AVAILABLE): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, 100, 'adjustment_in');

        $bkb = BkbDistribusi::create([
            'code' => 'BKB-VT'.(BkbDistribusi::count() + 1),
            'warehouse_id' => $wh->id,
            'sales_id' => $sales->id,
            'status' => BkbDistribusi::STATUS_APPLIED,
        ]);
        $bkb->items()->create(['product_id' => $product->id, 'quantity' => $qty]);
        $stock->transfer($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, Stock::LOCATION_SALES, $sales->id, $qty, 'bkb_apply', BkbDistribusi::class, $bkb->id);

        $task = SalesTask::create([
            'code' => 'TASK-VT'.(SalesTask::count() + 1),
            'sales_id' => $sales->id,
            'bkb_distribusi_id' => $bkb->id,
            'branch_id' => $sales->branch_id,
            'task_date' => now()->toDateString(),
            'status' => $taskStatus,
            'applied_by' => $admin->id,
            'applied_at' => now(),
        ]);

        return [$admin, $sales, $wh, $product, $bkb, $task];
    }

    private function stockOf($product, string $type, int $id): float
    {
        return app(StockService::class)->getQuantity($product->id, $type, $id);
    }

    public function test_admin_can_void_applied_task_and_stock_returns_to_warehouse(): void
    {
        [$admin, $sales, $wh, $product, $bkb, $task] = $this->appliedTask(20);

        $session = SalesTrackingSession::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'sales_task_id' => $task->id,
            'started_at' => now(), 'status' => SalesTrackingSession::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.void-applied', $task), ['reason' => 'Sales tidak masuk kerja'])
            ->assertSessionHas('status');

        $this->assertSame(SalesTask::STATUS_CANCELLED, $task->fresh()->status);
        $this->assertSame($admin->id, $task->fresh()->cancelled_by);
        $this->assertNotNull($task->fresh()->cancelled_at);
        $this->assertStringContainsString('Sales tidak masuk kerja', $task->fresh()->notes);

        $this->assertSame(BkbDistribusi::STATUS_CANCELLED, $bkb->fresh()->status);
        $this->assertSame(100.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(0.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));

        $this->assertSame(SalesTrackingSession::STATUS_COMPLETED, $session->fresh()->status);
    }

    public function test_cannot_void_draft_task(): void
    {
        [$admin, $sales, $wh, $product, $bkb, $task] = $this->appliedTask(20, SalesTask::STATUS_DRAFT);

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.void-applied', $task), ['reason' => 'Coba batalkan draft'])
            ->assertSessionHas('error');

        $this->assertSame(SalesTask::STATUS_DRAFT, $task->fresh()->status);
        $this->assertSame(BkbDistribusi::STATUS_APPLIED, $bkb->fresh()->status);
        $this->assertSame(80.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
    }

    public function test_cannot_void_completed_or_cancelled_task(): void
    {
        [$admin, , , , , $completed] = $this->appliedTask(20, SalesTask::STATUS_COMPLETED);
        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.void-applied', $completed), ['reason' => 'Coba batalkan'])
            ->assertSessionHas('error');
        $this->assertSame(SalesTask::STATUS_COMPLETED, $completed->fresh()->status);

        [$admin2, , , , , $cancelled] = $this->appliedTask(20, SalesTask::STATUS_CANCELLED);
        $this->actingAs($admin2)
            ->post(route('admin.sales-tasks.void-applied', $cancelled), ['reason' => 'Coba batalkan lagi'])
            ->assertSessionHas('error');
    }

    public function test_void_applied_fails_cleanly_when_sales_already_sold_goods(): void
    {
        [$admin, $sales, $wh, $product, $bkb, $task] = $this->appliedTask(20);

        // Sales sudah sempat jual sebagian (stock Sales berkurang di luar alur BKB).
        app(StockService::class)->decrease($product->id, Stock::LOCATION_SALES, $sales->id, 15, 'sale');

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.void-applied', $task), ['reason' => 'Sales tidak masuk'])
            ->assertSessionHas('error');

        // Tidak ada perubahan setengah jalan: Task & BKB tetap seperti semula.
        $this->assertSame(SalesTask::STATUS_DOCUMENT_AVAILABLE, $task->fresh()->status);
        $this->assertSame(BkbDistribusi::STATUS_APPLIED, $bkb->fresh()->status);
        $this->assertSame(80.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(5.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_reason_is_required(): void
    {
        [$admin, , , , , $task] = $this->appliedTask(20);

        $this->actingAs($admin)
            ->post(route('admin.sales-tasks.void-applied', $task), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(SalesTask::STATUS_DOCUMENT_AVAILABLE, $task->fresh()->status);
    }

    public function test_admin_of_other_branch_cannot_void(): void
    {
        [, , , , , $task] = $this->appliedTask(20);
        $otherBranch = $this->makeBranch('CBG-OTHER-VT', 'Cabang Lain');
        $otherAdmin = $this->makeAdminUser($otherBranch);

        $this->actingAs($otherAdmin)
            ->post(route('admin.sales-tasks.void-applied', $task), ['reason' => 'Coba dari branch lain'])
            ->assertForbidden();

        $this->assertSame(SalesTask::STATUS_DOCUMENT_AVAILABLE, $task->fresh()->status);
    }
}
