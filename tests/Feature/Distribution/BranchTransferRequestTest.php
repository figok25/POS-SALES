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

    /**
     * PERBAIKAN (form, bukan cuma validasi): dropdown "Warehouse Asal"
     * sekarang dibatasi ke Branch Admin sendiri saja, dan dropdown
     * "Warehouse Tujuan" tidak lagi menampilkan Branch sendiri -- supaya
     * Admin tidak bisa memilih Blitar -> Blitar dari form sama sekali
     * (sebelumnya baru ketahuan setelah submit lewat pesan error).
     */
    public function test_create_form_limits_source_warehouse_to_the_admins_own_branch(): void
    {
        $blitar = $this->makeBranch('CBG-BLITAR4', 'Depo Blitar');
        $trenggalek = $this->makeBranch('CBG-TRENGGALEK4', 'Depo Trenggalek');
        $gudangBlitar = $this->makeWarehouse($blitar);
        $gudangTrenggalek = $this->makeWarehouse($trenggalek);
        $admin = $this->makeAdminUser($blitar);

        $response = $this->actingAs($admin)->get(route('admin.distribution.branch-transfer.create'))->assertOk();

        // Warehouse Asal: HANYA Warehouse Branch sendiri (Blitar).
        $response->assertViewHas('fromWarehouses', fn ($list) => $list->pluck('id')->all() === [$gudangBlitar->id]);
        // Warehouse Tujuan: semua Branch LAIN, tapi BUKAN Branch sendiri.
        $response->assertViewHas('toWarehouses', fn ($list) => $list->pluck('id')->all() === [$gudangTrenggalek->id]);
    }

    public function test_create_form_lets_super_admin_pick_any_branch_as_source(): void
    {
        $blitar = $this->makeBranch('CBG-BLITAR5', 'Depo Blitar');
        $trenggalek = $this->makeBranch('CBG-TRENGGALEK5', 'Depo Trenggalek');
        $gudangBlitar = $this->makeWarehouse($blitar);
        $gudangTrenggalek = $this->makeWarehouse($trenggalek);
        $superAdmin = $this->makeSuperAdminUser();

        $response = $this->actingAs($superAdmin)->get(route('admin.distribution.branch-transfer.create'))->assertOk();

        $response->assertViewHas('fromWarehouses', fn ($list) => $list->pluck('id')->sort()->values()->all()
            === collect([$gudangBlitar->id, $gudangTrenggalek->id])->sort()->values()->all());
        $response->assertViewHas('toWarehouses', fn ($list) => $list->pluck('id')->sort()->values()->all()
            === collect([$gudangBlitar->id, $gudangTrenggalek->id])->sort()->values()->all());
    }
}
