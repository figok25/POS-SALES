<?php

namespace Tests\Feature\Distribution;

use App\Models\BranchTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * PERBAIKAN: sebelumnya BranchTransferRequest hanya mengecek
 * from_warehouse_id != to_warehouse_id (ID Warehouse beda), BUKAN Branch-nya
 * beda. Kalau satu Depo punya lebih dari satu Warehouse, Admin bisa membuat
 * draft Branch Transfer yang from & to-nya sama-sama Warehouse Depo yang
 * sama (mis. Blitar -> Blitar) -- padahal intinya Branch Transfer memang
 * untuk mengirim ke Depo LAIN. Sekarang ditolak eksplisit: Warehouse tujuan
 * wajib beda Branch dari Warehouse asal.
 */
class BranchTransferRequestTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function payload(int $fromWarehouseId, int $toWarehouseId, int $productId): array
    {
        return [
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'items' => [['product_id' => $productId, 'quantity' => 5]],
        ];
    }

    public function test_cannot_create_transfer_between_two_warehouses_in_the_same_branch(): void
    {
        $blitar = $this->makeBranch('CBG-BLITAR', 'Depo Blitar');
        $gudangUtama = $this->makeWarehouse($blitar);
        $gudangCadangan = $this->makeWarehouse($blitar); // Warehouse KEDUA, masih Branch Blitar yang sama.
        $product = $this->makeProduct();
        $admin = $this->makeAdminUser($blitar);

        // ID Warehouse beda (lolos rule `different`), tapi Branch-nya sama -- harus ditolak.
        $this->actingAs($admin)
            ->post(route('admin.distribution.branch-transfer.store'), $this->payload($gudangUtama->id, $gudangCadangan->id, $product->id))
            ->assertSessionHasErrors('to_warehouse_id');

        $this->assertSame(0, BranchTransfer::count());
    }

    public function test_can_create_transfer_between_two_different_branches(): void
    {
        $blitar = $this->makeBranch('CBG-BLITAR2', 'Depo Blitar');
        $trenggalek = $this->makeBranch('CBG-TRENGGALEK', 'Depo Trenggalek');
        $fromWh = $this->makeWarehouse($blitar);
        $toWh = $this->makeWarehouse($trenggalek);
        $product = $this->makeProduct();
        $admin = $this->makeAdminUser($blitar);

        $this->actingAs($admin)
            ->post(route('admin.distribution.branch-transfer.store'), $this->payload($fromWh->id, $toWh->id, $product->id))
            ->assertRedirect();

        $this->assertSame(1, BranchTransfer::count());
        $transfer = BranchTransfer::first();
        $this->assertSame($fromWh->id, $transfer->from_warehouse_id);
        $this->assertSame($toWh->id, $transfer->to_warehouse_id);
    }

    public function test_cannot_create_transfer_using_a_warehouse_from_another_branch_as_the_source(): void
    {
        $blitar = $this->makeBranch('CBG-BLITAR3', 'Depo Blitar');
        $trenggalek = $this->makeBranch('CBG-TRENGGALEK3', 'Depo Trenggalek');
        $wh = $this->makeWarehouse($trenggalek); // BUKAN Warehouse Branch Admin.
        $toWh = $this->makeWarehouse($blitar);
        $product = $this->makeProduct();
        $admin = $this->makeAdminUser($blitar); // Admin Blitar.

        // Admin Blitar mencoba memakai Warehouse Trenggalek sebagai ASAL -- harus ditolak (anti-IDOR).
        $this->actingAs($admin)
            ->post(route('admin.distribution.branch-transfer.store'), $this->payload($wh->id, $toWh->id, $product->id))
            ->assertSessionHasErrors('from_warehouse_id');

        $this->assertSame(0, BranchTransfer::count());
    }
}
