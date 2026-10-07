<?php

namespace Tests\Feature\MultiBranch;

use App\Models\BranchTransfer;
use App\Models\BranchTransferItem;
use App\Models\SalesCurrentLocation;
use App\Models\SalesLocationHistory;
use App\Models\SalesTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Multi Branch/Depo - Test Scenario Wajib (Langkah 14 blueprint).
 * Nomor skenario mengikuti penomoran "Test Scenario Wajib" (A-G) &
 * checklist "Langkah 14" pada dokumen prompt implementasi.
 */
class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    // ---------------------------------------------------------------
    // 1-2: Super Admin melihat semua Branch & dapat memilih Branch.
    // ---------------------------------------------------------------

    public function test_super_admin_sees_customers_from_all_branches(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);
        $custA = $this->makeCustomer($salesA->id);
        $custB = $this->makeCustomer($salesB->id);

        $superAdmin = $this->makeSuperAdminUser();

        $this->actingAs($superAdmin)
            ->get(route('admin.master.customers.index'))
            ->assertOk()
            ->assertSee($custA->name)
            ->assertSee($custB->name);
    }

    public function test_super_admin_can_filter_dashboard_by_specific_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);

        $superAdmin = $this->makeSuperAdminUser();

        // branch=all (default) -> kedua Sales ikut muncul di dropdown filter.
        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($salesA->name)
            ->assertSee($salesB->name);

        // branch=<A> -> hanya Sales Branch A yang muncul di dropdown filter.
        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard', ['branch' => $branchA->id]))
            ->assertOk()
            ->assertSee($salesA->name)
            ->assertDontSee($salesB->name);
    }

    // ---------------------------------------------------------------
    // 3-4: Admin A hanya melihat Branch A & tidak bisa buka resource
    // Branch B via URL (Scenario A).
    // ---------------------------------------------------------------

    public function test_admin_a_only_sees_branch_a_customers_in_index(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);
        $custA = $this->makeCustomer($salesA->id);
        $custB = $this->makeCustomer($salesB->id);

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->get(route('admin.master.customers.index'))
            ->assertOk()
            ->assertSee($custA->name)
            ->assertDontSee($custB->name);
    }

    public function test_admin_a_cannot_open_branch_b_customer_via_url(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesB] = $this->makeSalesUser($branchB);
        $custB = $this->makeCustomer($salesB->id);

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->get(route('admin.master.customers.show', $custB))
            ->assertForbidden();

        $this->actingAs($adminA)
            ->put(route('admin.master.customers.update', $custB), [
                'code' => $custB->code, 'name' => 'Diubah paksa', 'is_active' => 1,
            ])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // 5-6: Admin A tidak bisa memaksa branch_id via payload / ?branch=
    // (Scenario B & C).
    // ---------------------------------------------------------------

    public function test_admin_a_cannot_force_branch_id_via_payload_when_creating_sales(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)->post(route('admin.master.sales.store'), [
            'branch_id' => $branchB->id, // mencoba menyuntik Branch lain
            'code' => 'SLS-INJECT-1',
            'name' => 'Sales Suntikan',
            'type' => 'retail', // wajib sejak ada kategori harga Retail/WS
            'is_active' => 1,
        ])->assertRedirect();

        $sales = \App\Models\Sales::where('code', 'SLS-INJECT-1')->firstOrFail();

        // Server HARUS memaksa ke Branch Admin yang login, mengabaikan
        // branch_id dari payload sepenuhnya.
        $this->assertEquals($branchA->id, $sales->branch_id);
        $this->assertNotEquals($branchB->id, $sales->branch_id);
    }

    public function test_admin_a_query_branch_param_is_ignored_not_authorization(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);

        $adminA = $this->makeAdminUser($branchA);

        // Admin A mencoba ?branch=<B> lewat URL - harus TETAP terkunci ke
        // Branch A sendiri (bukan 403/ditolak, bukan juga ikut melihat B -
        // nilainya didiamkan/diabaikan server, lihat BranchContext::resolveForUser).
        $this->actingAs($adminA)
            ->get(route('admin.dashboard', ['branch' => $branchB->id]))
            ->assertOk()
            ->assertSee($salesA->name)
            ->assertDontSee($salesB->name);
    }

    // ---------------------------------------------------------------
    // 9: Hanya Super Admin yang dapat mengelola Users/Roles/Permissions.
    // ---------------------------------------------------------------

    public function test_only_super_admin_can_access_system_user_management(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $adminA = $this->makeAdminUser($branchA);
        $superAdmin = $this->makeSuperAdminUser();

        $this->actingAs($adminA)->get(route('admin.system.users.index'))->assertForbidden();
        $this->actingAs($adminA)->get(route('admin.system.roles.index'))->assertForbidden();
        $this->actingAs($adminA)->get(route('admin.system.permissions.index'))->assertForbidden();

        $this->actingAs($superAdmin)->get(route('admin.system.users.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.system.roles.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.system.permissions.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // 10: Export Admin A hanya berisi Branch A (Scenario F).
    // ---------------------------------------------------------------

    public function test_admin_a_transaction_export_only_contains_branch_a_data(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);
        $custA = $this->makeCustomer($salesA->id);
        $custB = $this->makeCustomer($salesB->id);

        $trxA = SalesTransaction::create([
            'code' => 'TRX-A-1', 'sales_id' => $salesA->id, 'customer_id' => $custA->id,
            'subtotal' => 10000, 'discount' => 0, 'tax' => 0, 'total' => 10000,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);
        SalesTransaction::create([
            'code' => 'TRX-B-1', 'sales_id' => $salesB->id, 'customer_id' => $custB->id,
            'subtotal' => 20000, 'discount' => 0, 'tax' => 0, 'total' => 20000,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);

        $adminA = $this->makeAdminUser($branchA);

        $response = $this->actingAs($adminA)->get(route('admin.sales.transactions.export'));
        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('TRX-A-1', $content);
        $this->assertStringNotContainsString('TRX-B-1', $content);
    }

    // ---------------------------------------------------------------
    // 11-12: Live Monitoring & history endpoint (Scenario E).
    // ---------------------------------------------------------------

    public function test_live_monitoring_is_closed_to_branch_admin_and_super_admin_can_filter_by_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [, $salesA] = $this->makeSalesUser($branchA);
        [, $salesB] = $this->makeSalesUser($branchB);

        SalesCurrentLocation::create([
            'sales_id' => $salesA->id, 'branch_id' => $branchA->id,
            'latitude' => -6.2, 'longitude' => 106.8, 'last_seen_at' => now(),
            'status' => SalesCurrentLocation::STATUS_ACTIVE,
        ]);
        SalesCurrentLocation::create([
            'sales_id' => $salesB->id, 'branch_id' => $branchB->id,
            'latitude' => -7.2, 'longitude' => 110.4, 'last_seen_at' => now(),
            'status' => SalesCurrentLocation::STATUS_ACTIVE,
        ]);

        // Live Monitoring khusus Super Admin: Admin Depo ditolak.
        $this->actingAs($this->makeAdminUser($branchA))->getJson(route('api.admin.live-sales'))->assertForbidden();

        $super = $this->makeSuperAdminUser();

        $all = collect($this->actingAs($super)->getJson(route('api.admin.live-sales'))->assertOk()->json('data'))->pluck('sales_id');
        $this->assertTrue($all->contains($salesA->id));
        $this->assertTrue($all->contains($salesB->id));

        $onlyA = collect($this->actingAs($super)->getJson(route('api.admin.live-sales', ['branch' => $branchA->id]))->assertOk()->json('data'))->pluck('sales_id');
        $this->assertTrue($onlyA->contains($salesA->id));
        $this->assertFalse($onlyA->contains($salesB->id));
    }

    public function test_history_endpoint_rejects_sales_from_other_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        [$userB, $salesB] = $this->makeSalesUser($branchB);

        $trackingSession = \App\Models\SalesTrackingSession::create([
            'sales_id' => $salesB->id, 'branch_id' => $branchB->id,
            'started_at' => now(), 'status' => \App\Models\SalesTrackingSession::STATUS_ACTIVE,
        ]);

        SalesLocationHistory::create([
            'sales_id' => $salesB->id, 'location_event_id' => (string) \Illuminate\Support\Str::uuid(),
            'branch_id' => $branchB->id, 'tracking_session_id' => $trackingSession->id,
            'latitude' => -7.2, 'longitude' => 110.4,
            'recorded_at' => now(), 'received_at' => now(),
        ]);

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->getJson(route('api.admin.sales.locations', ['sales' => $salesB->id]))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // 13: Branch Transfer - source hanya dikelola Branch asal, receive
    // hanya oleh Branch tujuan (Scenario G).
    // ---------------------------------------------------------------

    public function test_branch_transfer_send_allowed_only_for_source_branch_admin(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $warehouseA = $this->makeWarehouse($branchA);
        $warehouseB = $this->makeWarehouse($branchB);
        $product = $this->makeProduct();

        $transfer = BranchTransfer::create([
            'code' => 'BT-TEST-1', 'from_warehouse_id' => $warehouseA->id,
            'to_warehouse_id' => $warehouseB->id, 'status' => BranchTransfer::STATUS_DRAFT,
        ]);
        BranchTransferItem::create([
            'branch_transfer_id' => $transfer->id, 'product_id' => $product->id, 'quantity_sent' => 5,
        ]);

        $adminA = $this->makeAdminUser($branchA); // Branch asal
        $adminB = $this->makeAdminUser($branchB); // Branch tujuan

        // Admin Branch tujuan TIDAK boleh men-Send (itu wewenang Branch asal).
        $this->actingAs($adminB)
            ->post(route('admin.distribution.branch-transfer.send', $transfer))
            ->assertForbidden();

        // Admin Branch asal tetap bisa lihat dokumennya (allowsEither).
        $this->actingAs($adminA)
            ->get(route('admin.distribution.branch-transfer.show', $transfer))
            ->assertOk();
    }

    public function test_branch_transfer_receive_allowed_only_for_destination_branch_admin(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $warehouseA = $this->makeWarehouse($branchA);
        $warehouseB = $this->makeWarehouse($branchB);
        $product = $this->makeProduct();

        $transfer = BranchTransfer::create([
            'code' => 'BT-TEST-2', 'from_warehouse_id' => $warehouseA->id,
            'to_warehouse_id' => $warehouseB->id, 'status' => BranchTransfer::STATUS_SENT, 'sent_at' => now(),
        ]);
        BranchTransferItem::create([
            'branch_transfer_id' => $transfer->id, 'product_id' => $product->id, 'quantity_sent' => 5,
        ]);

        $adminA = $this->makeAdminUser($branchA); // Branch asal
        $adminC = $this->makeAdminUser($this->makeBranch('CBG-C', 'Cabang C')); // Branch ketiga, tidak terlibat sama sekali

        // Admin Branch asal TIDAK boleh men-Receive (itu wewenang Branch tujuan).
        $this->actingAs($adminA)
            ->get(route('admin.distribution.branch-transfer.receive-form', $transfer))
            ->assertForbidden();

        // Admin Branch ketiga yang sama sekali tidak terlibat juga ditolak total.
        $this->actingAs($adminC)
            ->get(route('admin.distribution.branch-transfer.show', $transfer))
            ->assertForbidden();
    }
}
