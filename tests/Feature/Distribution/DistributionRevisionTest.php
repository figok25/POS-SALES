<?php

namespace Tests\Feature\Distribution;

use App\Models\AuditLog;
use App\Models\BkbDistribusi;
use App\Models\BtbDistribusi;
use App\Models\SalesTask;
use App\Models\SalesTaskStock;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Revisi & pembatalan BKB/BTB: stok disesuaikan dengan SELISIH, tidak dobel,
 * tercatat di audit log, dan terkunci per Depo.
 */
class DistributionRevisionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function stockOf($product, string $type, int $id): float
    {
        return app(StockService::class)->getQuantity($product->id, $type, $id);
    }

    /** BKB Applied: gudang 100 -> kirim $qty ke Sales. */
    private function appliedBkb(float $qty = 20): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();

        $stock = app(StockService::class);
        $stock->increase($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, 100, 'adjustment_in');

        $bkb = BkbDistribusi::create(['code' => 'BKB-T'.(BkbDistribusi::count() + 1), 'warehouse_id' => $wh->id, 'sales_id' => $sales->id, 'status' => BkbDistribusi::STATUS_APPLIED]);
        $bkb->items()->create(['product_id' => $product->id, 'quantity' => $qty]);
        $stock->transfer($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, Stock::LOCATION_SALES, $sales->id, $qty, 'bkb_apply', BkbDistribusi::class, $bkb->id);

        return [$admin, $sales, $wh, $product, $bkb];
    }

    private function revisePayload($product, float $qty, string $reason = 'Salah input jumlah'): array
    {
        return ['reason' => $reason, 'items' => [['product_id' => $product->id, 'quantity' => $qty]]];
    }

    public function test_bkb_quantity_reduced_returns_difference_to_warehouse(): void
    {
        [$admin, $sales, $wh, $product, $bkb] = $this->appliedBkb(20);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 15))
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertSame(85.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));   // 80 + 5
        $this->assertSame(15.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(15.0, (float) $bkb->fresh()->items->first()->quantity);
        $this->assertSame(1, $bkb->fresh()->revision_count);
    }

    public function test_bkb_same_revision_twice_does_not_double_move_stock(): void
    {
        [$admin, $sales, $wh, $product, $bkb] = $this->appliedBkb(20);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 15));
        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 15));

        $this->assertSame(85.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(15.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_bkb_quantity_increased_moves_extra_from_warehouse(): void
    {
        [$admin, $sales, $wh, $product, $bkb] = $this->appliedBkb(20);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 30));

        $this->assertSame(70.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(30.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_bkb_changing_product_corrects_old_and_new_stock(): void
    {
        [$admin, $sales, $wh, $old, $bkb] = $this->appliedBkb(20);
        $new = $this->makeProduct();
        app(StockService::class)->increase($new->id, Stock::LOCATION_WAREHOUSE, $wh->id, 50, 'adjustment_in');

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($new, 12))
            ->assertSessionHasNoErrors();

        $this->assertSame(100.0, $this->stockOf($old, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(0.0, $this->stockOf($old, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(38.0, $this->stockOf($new, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(12.0, $this->stockOf($new, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_bkb_revision_fails_cleanly_when_sales_already_sold_goods(): void
    {
        [$admin, $sales, $wh, $product, $bkb] = $this->appliedBkb(20);
        // Sales sudah menjual 18, sisa 2 -> tidak bisa dikurangi jadi 5 (butuh kembalikan 15).
        app(StockService::class)->decrease($product->id, Stock::LOCATION_SALES, $sales->id, 18, 'sales_transaction');

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 5))
            ->assertSessionHas('error');

        $this->assertSame(80.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(2.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(20.0, (float) $bkb->fresh()->items->first()->quantity);
        $this->assertSame(0, $bkb->fresh()->revision_count);
    }

    public function test_draft_bkb_revision_does_not_touch_stock(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $wh->id, 100, 'adjustment_in');

        $bkb = BkbDistribusi::create(['code' => 'BKB-D1', 'warehouse_id' => $wh->id, 'sales_id' => $sales->id, 'status' => BkbDistribusi::STATUS_DRAFT]);
        $bkb->items()->create(['product_id' => $product->id, 'quantity' => 20]);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 7))
            ->assertSessionHasNoErrors();

        $this->assertSame(100.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(7.0, (float) $bkb->fresh()->items->first()->quantity);
    }

    public function test_revision_requires_reason(): void
    {
        [$admin, , , $product, $bkb] = $this->appliedBkb(20);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), ['items' => [['product_id' => $product->id, 'quantity' => 15]]])
            ->assertSessionHasErrors('reason');
    }

    public function test_revision_is_written_to_audit_log_with_reason_and_impact(): void
    {
        [$admin, , , $product, $bkb] = $this->appliedBkb(20);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 15, 'Hitung ulang fisik'));

        $log = AuditLog::where('action', 'revise')->where('document_type', BkbDistribusi::class)->where('document_id', $bkb->id)->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Hitung ulang fisik', $log->after['reason']);
        $this->assertTrue($log->after['stock_applied']);
        $this->assertSame(-5.0, (float) $log->after['stock_impact'][0]['delta']);
        $this->assertSame(20.0, (float) $log->before['items'][0]['quantity']);
        $this->assertSame(15.0, (float) $log->after['items'][0]['quantity']);
    }

    public function test_void_applied_bkb_returns_all_stock(): void
    {
        [$admin, $sales, $wh, $product, $bkb] = $this->appliedBkb(20);

        $this->actingAs($admin)->post(route('admin.distribution.bkb.void', $bkb), ['reason' => 'Dokumen dobel'])
            ->assertSessionHas('status');

        $this->assertSame(100.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(0.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(BkbDistribusi::STATUS_CANCELLED, $bkb->fresh()->status);

        // Dibatalkan dua kali tidak mengembalikan stok dua kali.
        $this->actingAs($admin)->post(route('admin.distribution.bkb.void', $bkb), ['reason' => 'Ulang'])
            ->assertSessionHas('error');
        $this->assertSame(100.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
    }

    public function test_void_bkb_blocked_when_bound_to_sales_task(): void
    {
        [$admin, $sales, $wh, $product, $bkb] = $this->appliedBkb(20);
        $task = $this->makeSalesTask($sales);
        $task->forceFill(['bkb_distribusi_id' => $bkb->id])->save();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.void', $bkb), ['reason' => 'Salah'])
            ->assertSessionHas('error');

        $this->assertSame(BkbDistribusi::STATUS_APPLIED, $bkb->fresh()->status);
        $this->assertSame(80.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
    }

    public function test_btb_quantity_reduced_takes_difference_back_from_warehouse(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();
        $stock = app(StockService::class);

        // Sales bawa 30; pulang input sisa 20 (seharusnya 15) -> BTB Applied.
        $stock->increase($product->id, Stock::LOCATION_SALES, $sales->id, 30, 'bkb_apply');
        $btb = BtbDistribusi::create(['code' => 'BTB-T1', 'sales_id' => $sales->id, 'warehouse_id' => $wh->id, 'status' => BtbDistribusi::STATUS_APPLIED]);
        $btb->items()->create(['product_id' => $product->id, 'quantity' => 20]);
        $stock->transfer($product->id, Stock::LOCATION_SALES, $sales->id, Stock::LOCATION_WAREHOUSE, $wh->id, 20, 'btb_apply', BtbDistribusi::class, $btb->id);

        $this->actingAs($admin)->put(route('admin.distribution.btb.revise', $btb), $this->revisePayload($product, 15, 'Sisa sebenarnya 15'))
            ->assertSessionHasNoErrors();

        $this->assertSame(15.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));  // 20 - 5
        $this->assertSame(15.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));   // 10 + 5
        $this->assertSame(15.0, (float) $btb->fresh()->items->first()->quantity);
    }

    public function test_void_applied_btb_moves_stock_back_to_sales(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $wh = $this->makeWarehouse();
        $product = $this->makeProduct();
        $stock = app(StockService::class);

        $stock->increase($product->id, Stock::LOCATION_SALES, $sales->id, 20, 'bkb_apply');
        $btb = BtbDistribusi::create(['code' => 'BTB-T2', 'sales_id' => $sales->id, 'warehouse_id' => $wh->id, 'status' => BtbDistribusi::STATUS_APPLIED]);
        $btb->items()->create(['product_id' => $product->id, 'quantity' => 20]);
        $stock->transfer($product->id, Stock::LOCATION_SALES, $sales->id, Stock::LOCATION_WAREHOUSE, $wh->id, 20, 'btb_apply', BtbDistribusi::class, $btb->id);

        $this->actingAs($admin)->post(route('admin.distribution.btb.void', $btb), ['reason' => 'BTB salah Sales'])
            ->assertSessionHas('status');

        $this->assertSame(0.0, $this->stockOf($product, Stock::LOCATION_WAREHOUSE, $wh->id));
        $this->assertSame(20.0, $this->stockOf($product, Stock::LOCATION_SALES, $sales->id));
        $this->assertSame(BtbDistribusi::STATUS_CANCELLED, $btb->fresh()->status);
    }

    public function test_admin_of_other_depo_cannot_revise(): void
    {
        [, , , $product, $bkb] = $this->appliedBkb(20);
        $otherAdmin = $this->makeAdminUser($this->makeBranch('CBG-LAIN', 'Depo Lain'));

        $this->actingAs($otherAdmin)->get(route('admin.distribution.bkb.edit', $bkb))->assertForbidden();
        $this->actingAs($otherAdmin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 1))->assertForbidden();
        $this->actingAs($otherAdmin)->post(route('admin.distribution.bkb.void', $bkb), ['reason' => 'x y z'])->assertForbidden();
    }

    public function test_super_admin_can_revise_any_depo_and_pages_render(): void
    {
        [, , , $product, $bkb] = $this->appliedBkb(20);
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->get(route('admin.distribution.bkb.edit', $bkb))->assertOk();
        $this->actingAs($super)->get(route('admin.distribution.bkb.show', $bkb))->assertOk()->assertSee('Revisi BKB');
        $this->actingAs($super)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 10))
            ->assertSessionHasNoErrors();
        $this->actingAs($super)->get(route('admin.distribution.bkb.show', $bkb))->assertOk()->assertSee('Riwayat Revisi');
    }
    private function taskFor($bkb, $sales, $product, float $qty, string $status): SalesTask
    {
        $task = SalesTask::create([
            'code' => 'TSK-'.(SalesTask::count() + 1), 'sales_id' => $sales->id, 'bkb_distribusi_id' => $bkb->id,
            'branch_id' => $sales->branch_id, 'task_date' => now()->toDateString(), 'status' => $status,
        ]);
        SalesTaskStock::create(['sales_task_id' => $task->id, 'product_id' => $product->id, 'quantity_assigned' => $qty]);

        return $task;
    }

    public function test_bkb_revision_rebuilds_unverified_task_stock_list(): void
    {
        [$admin, $sales, , $product, $bkb] = $this->appliedBkb(20);
        $task = $this->taskFor($bkb, $sales, $product, 20, SalesTask::STATUS_DOCUMENT_AVAILABLE);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 12))
            ->assertSessionHasNoErrors();

        $lines = $task->fresh()->taskStocks;
        $this->assertCount(1, $lines);
        $this->assertSame(12.0, (float) $lines->first()->quantity_assigned);
        $this->assertNull($lines->first()->quantity_verified);
        $this->assertSame(SalesTask::STATUS_DOCUMENT_AVAILABLE, $task->fresh()->status);
    }

    public function test_bkb_revision_resets_half_verified_task_back_to_document_available(): void
    {
        [$admin, $sales, , $product, $bkb] = $this->appliedBkb(20);
        $task = $this->taskFor($bkb, $sales, $product, 20, SalesTask::STATUS_STOCK_VARIANCE);
        $task->taskStocks()->update(['quantity_verified' => 18, 'verified_at' => now()]);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 25))
            ->assertSessionHasNoErrors();

        $this->assertSame(SalesTask::STATUS_DOCUMENT_AVAILABLE, $task->fresh()->status);
        $line = $task->fresh()->taskStocks->first();
        $this->assertSame(25.0, (float) $line->quantity_assigned);
        $this->assertNull($line->quantity_verified);
    }

    public function test_bkb_revision_updates_running_task_and_keeps_verification(): void
    {
        [$admin, $sales, , $product, $bkb] = $this->appliedBkb(20);
        $task = $this->taskFor($bkb, $sales, $product, 20, SalesTask::STATUS_WORKING);
        $task->taskStocks()->update(['quantity_verified' => 20, 'verified_at' => now()]);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 14))
            ->assertSessionHasNoErrors();

        $line = $task->fresh()->taskStocks->first();
        $this->assertSame(14.0, (float) $line->quantity_assigned);
        $this->assertSame(14.0, (float) $line->quantity_verified);   // tadinya sama, ikut angka baru
        $this->assertSame(SalesTask::STATUS_WORKING, $task->fresh()->status);
    }

    public function test_bkb_revision_leaves_completed_task_untouched(): void
    {
        [$admin, $sales, , $product, $bkb] = $this->appliedBkb(20);
        $task = $this->taskFor($bkb, $sales, $product, 20, SalesTask::STATUS_COMPLETED);

        $this->actingAs($admin)->put(route('admin.distribution.bkb.revise', $bkb), $this->revisePayload($product, 5))
            ->assertSessionHasNoErrors();

        $this->assertSame(20.0, (float) $task->fresh()->taskStocks->first()->quantity_assigned);
    }
}
