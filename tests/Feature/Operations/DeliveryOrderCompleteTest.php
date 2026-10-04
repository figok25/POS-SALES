<?php

namespace Tests\Feature\Operations;

use App\Models\Branch;
use App\Models\DeliveryOrder;
use App\Models\SalesTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Otomatisasi Status DO Sekaligus: Apply (vehicle/driver/route/jadwal) +
 * Dispatch + Delivered dalam satu aksi, per DO maupun massal.
 */
class DeliveryOrderCompleteTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function makeDo(string $status = DeliveryOrder::STATUS_DRAFT, ?Branch $branch = null): DeliveryOrder
    {
        static $n = 0;
        $n++;

        [, $sales] = $this->makeSalesUser($branch);
        $customer = $this->makeCustomer($sales->id);

        $trx = SalesTransaction::create([
            'code' => "TRX-DO-{$n}", 'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'subtotal' => 10000, 'discount' => 0, 'tax' => 0, 'total' => 10000,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);

        return DeliveryOrder::create([
            'code' => "DO-TEST-{$n}",
            'sales_transaction_id' => $trx->id,
            'status' => $status,
        ]);
    }

    public function test_complete_from_draft_dispatches_and_delivers_in_one_action(): void
    {
        $admin = $this->makeAdminUser();
        $do = $this->makeDo();

        $this->actingAs($admin)->post(route('admin.operations.delivery-orders.complete', $do))->assertRedirect();

        $do->refresh();
        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $do->status);
        $this->assertNotNull($do->dispatched_at);
        $this->assertSame($admin->id, $do->dispatched_by);
        $this->assertNotNull($do->delivered_at);
        $this->assertSame($admin->id, $do->delivered_by);
    }

    public function test_complete_from_dispatched_only_marks_delivered(): void
    {
        $admin = $this->makeAdminUser();
        $do = $this->makeDo(DeliveryOrder::STATUS_DISPATCHED);

        $this->actingAs($admin)->post(route('admin.operations.delivery-orders.complete', $do))->assertRedirect();

        $do->refresh();
        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $do->status);
        $this->assertNull($do->dispatched_at);
        $this->assertNotNull($do->delivered_at);
    }

    public function test_complete_rejects_delivered_and_cancelled(): void
    {
        $admin = $this->makeAdminUser();

        foreach ([DeliveryOrder::STATUS_DELIVERED, DeliveryOrder::STATUS_CANCELLED] as $status) {
            $do = $this->makeDo($status);

            $this->actingAs($admin)
                ->post(route('admin.operations.delivery-orders.complete', $do))
                ->assertSessionHas('error');

            $this->assertSame($status, $do->fresh()->status);
        }
    }

    public function test_bulk_complete_handles_mixed_draft_and_dispatched_and_applies_schedule(): void
    {
        $admin = $this->makeAdminUser();
        $draft = $this->makeDo(DeliveryOrder::STATUS_DRAFT);
        $dispatched = $this->makeDo(DeliveryOrder::STATUS_DISPATCHED);
        $cancelled = $this->makeDo(DeliveryOrder::STATUS_CANCELLED);

        $this->actingAs($admin)->post(route('admin.operations.delivery-orders.bulk-complete'), [
            'delivery_order_ids' => [$draft->id, $dispatched->id, $cancelled->id],
            'scheduled_date' => '2026-10-05',
        ])->assertRedirect();

        $draft->refresh();
        $dispatched->refresh();

        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $draft->status);
        $this->assertNotNull($draft->dispatched_at);
        $this->assertSame('2026-10-05', $draft->scheduled_date->toDateString());
        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $dispatched->status);
        $this->assertSame(DeliveryOrder::STATUS_CANCELLED, $cancelled->fresh()->status);
    }

    public function test_bulk_complete_select_all_does_not_touch_other_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $adminA = $this->makeAdminUser($branchA);
        $doA = $this->makeDo(DeliveryOrder::STATUS_DRAFT, $branchA);
        $doB = $this->makeDo(DeliveryOrder::STATUS_DRAFT, $branchB);

        $this->actingAs($adminA)
            ->post(route('admin.operations.delivery-orders.bulk-complete'), ['select_all_draft' => 1])
            ->assertRedirect();

        $this->assertSame(DeliveryOrder::STATUS_DELIVERED, $doA->fresh()->status);
        $this->assertSame(DeliveryOrder::STATUS_DRAFT, $doB->fresh()->status);
    }

    public function test_admin_cannot_complete_other_branch_do(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $adminA = $this->makeAdminUser($branchA);
        $doB = $this->makeDo(DeliveryOrder::STATUS_DRAFT, $branchB);

        $this->actingAs($adminA)
            ->post(route('admin.operations.delivery-orders.complete', $doB))
            ->assertForbidden();

        $this->assertSame(DeliveryOrder::STATUS_DRAFT, $doB->fresh()->status);
    }

    public function test_index_shows_route_filter_and_row_complete_button(): void
    {
        $admin = $this->makeAdminUser();
        $this->makeDo();

        $this->actingAs($admin)
            ->get(route('admin.operations.delivery-orders.index'))
            ->assertOk()
            ->assertSee('name="route_id"', false)
            ->assertSee('data-row-action', false);
    }
}
