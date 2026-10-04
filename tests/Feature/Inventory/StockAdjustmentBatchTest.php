<?php

namespace Tests\Feature\Inventory;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Stock Adjustment: mode "Satu produk" (draft tunggal) dan "Paket" (banyak
 * produk dengan batch_code sama). Apply bisa per draft, per paket, atau
 * massal; satu baris gagal tidak membatalkan baris lain.
 */
class StockAdjustmentBatchTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function payload(string $mode, int $warehouseId, array $lines, string $type = 'in'): array
    {
        return [
            'mode' => $mode,
            'location_type' => Stock::LOCATION_WAREHOUSE,
            'location_id' => $warehouseId,
            'type' => $type,
            'reason' => 'Koreksi test',
            'items' => $lines,
        ];
    }

    private function qty(int $productId, int $warehouseId): float
    {
        return (float) app(StockService::class)->getQuantity($productId, Stock::LOCATION_WAREHOUSE, $warehouseId);
    }

    public function test_single_mode_creates_one_draft_without_batch_code(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $p = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('single', $warehouse->id, [['product_id' => $p->id, 'quantity' => 10]])
        )->assertRedirect(route('admin.inventory.adjustments.index'));

        $this->assertDatabaseCount('stock_adjustments', 1);
        $row = StockAdjustment::first();
        $this->assertNull($row->batch_code);
        $this->assertSame(StockAdjustment::STATUS_DRAFT, $row->status);
        // Draft tidak mengubah stok.
        $this->assertEquals(0, $this->qty($p->id, $warehouse->id));
    }

    public function test_batch_mode_creates_one_draft_per_product_with_same_batch_code(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();
        $c = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 10],
                ['product_id' => $b->id, 'quantity' => 20],
                ['product_id' => $c->id, 'quantity' => 30],
            ])
        )->assertRedirect(route('admin.inventory.adjustments.index'));

        $this->assertDatabaseCount('stock_adjustments', 3);
        $codes = StockAdjustment::pluck('batch_code')->unique();
        $this->assertCount(1, $codes);
        $this->assertStringStartsWith('BATCH-', $codes->first());
    }

    public function test_batch_requires_at_least_two_distinct_products(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [['product_id' => $a->id, 'quantity' => 5]])
        )->assertSessionHasErrors('items');

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 5],
                ['product_id' => $a->id, 'quantity' => 7],
            ])
        )->assertSessionHasErrors('items.0.product_id');

        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_single_mode_rejects_more_than_one_product(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('single', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 5],
                ['product_id' => $b->id, 'quantity' => 5],
            ])
        )->assertSessionHasErrors('items');
    }

    public function test_apply_batch_applies_all_drafts_and_changes_stock(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 10],
                ['product_id' => $b->id, 'quantity' => 25],
            ])
        );
        $batchCode = StockAdjustment::first()->batch_code;

        $this->actingAs($admin)
            ->post(route('admin.inventory.adjustments.batch.apply', $batchCode))
            ->assertRedirect();

        $this->assertEquals(10, $this->qty($a->id, $warehouse->id));
        $this->assertEquals(25, $this->qty($b->id, $warehouse->id));
        $this->assertSame(0, StockAdjustment::where('status', StockAdjustment::STATUS_DRAFT)->count());
    }

    public function test_apply_batch_continues_when_one_row_has_insufficient_stock(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        // Hanya produk A yang punya stok awal.
        app(StockService::class)->increase($a->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 50, 'stock_adjustment');

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 20],
                ['product_id' => $b->id, 'quantity' => 20],
            ], 'out')
        );
        $batchCode = StockAdjustment::first()->batch_code;

        $this->actingAs($admin)
            ->post(route('admin.inventory.adjustments.batch.apply', $batchCode))
            ->assertSessionHas('bulkApplyFailures');

        $this->assertEquals(30, $this->qty($a->id, $warehouse->id));
        $this->assertEquals(0, $this->qty($b->id, $warehouse->id));

        // Baris yang gagal tetap Draft, yang berhasil Applied.
        $this->assertSame(StockAdjustment::STATUS_APPLIED, StockAdjustment::where('product_id', $a->id)->first()->status);
        $this->assertSame(StockAdjustment::STATUS_DRAFT, StockAdjustment::where('product_id', $b->id)->first()->status);
    }

    public function test_single_and_batch_drafts_can_be_applied_together_via_bulk_apply(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $solo = $this->makeProduct();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('single', $warehouse->id, [['product_id' => $solo->id, 'quantity' => 5]])
        );
        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 6],
                ['product_id' => $b->id, 'quantity' => 7],
            ])
        );

        $this->actingAs($admin)
            ->post(route('admin.inventory.adjustments.bulk-apply'), ['select_all_draft' => 1])
            ->assertRedirect();

        $this->assertEquals(5, $this->qty($solo->id, $warehouse->id));
        $this->assertEquals(6, $this->qty($a->id, $warehouse->id));
        $this->assertEquals(7, $this->qty($b->id, $warehouse->id));
    }

    public function test_applying_single_row_inside_a_batch_leaves_the_rest_as_draft(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 10],
                ['product_id' => $b->id, 'quantity' => 10],
            ])
        );
        $rowA = StockAdjustment::where('product_id', $a->id)->first();

        $this->actingAs($admin)
            ->post(route('admin.inventory.adjustments.apply', $rowA))
            ->assertRedirect();

        $this->assertEquals(10, $this->qty($a->id, $warehouse->id));
        $this->assertEquals(0, $this->qty($b->id, $warehouse->id));
        $this->assertSame(1, StockAdjustment::where('status', StockAdjustment::STATUS_DRAFT)->count());
    }

    public function test_destroy_batch_deletes_only_draft_rows(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 10],
                ['product_id' => $b->id, 'quantity' => 10],
            ])
        );
        $batchCode = StockAdjustment::first()->batch_code;
        $rowA = StockAdjustment::where('product_id', $a->id)->first();

        // A sudah di-Apply -> tidak boleh ikut terhapus.
        $this->actingAs($admin)->post(route('admin.inventory.adjustments.apply', $rowA));

        $this->actingAs($admin)
            ->delete(route('admin.inventory.adjustments.batch.destroy', $batchCode))
            ->assertRedirect(route('admin.inventory.adjustments.index'));

        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertSame($a->id, StockAdjustment::first()->product_id);
        $this->assertEquals(10, $this->qty($a->id, $warehouse->id));
    }

    public function test_admin_cannot_apply_or_delete_batch_of_another_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $adminA = $this->makeAdminUser($branchA);
        $adminB = $this->makeAdminUser($branchB);
        $warehouseB = $this->makeWarehouse($branchB);
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($adminB)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouseB->id, [
                ['product_id' => $a->id, 'quantity' => 10],
                ['product_id' => $b->id, 'quantity' => 10],
            ])
        );
        $batchCode = StockAdjustment::first()->batch_code;

        // Admin A tidak boleh menerapkan / menghapus paket milik Branch B.
        $this->actingAs($adminA)->post(route('admin.inventory.adjustments.batch.apply', $batchCode));
        $this->actingAs($adminA)->delete(route('admin.inventory.adjustments.batch.destroy', $batchCode));

        $this->assertSame(2, StockAdjustment::where('status', StockAdjustment::STATUS_DRAFT)->count());
        $this->assertEquals(0, $this->qty($a->id, $warehouseB->id));
    }

    public function test_index_renders_batch_header_row(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'),
            $this->payload('batch', $warehouse->id, [
                ['product_id' => $a->id, 'quantity' => 10],
                ['product_id' => $b->id, 'quantity' => 10],
            ])
        );
        $batchCode = StockAdjustment::first()->batch_code;

        $this->actingAs($admin)
            ->get(route('admin.inventory.adjustments.index'))
            ->assertOk()
            ->assertSee($batchCode)
            ->assertSee('2 produk');
    }
}
