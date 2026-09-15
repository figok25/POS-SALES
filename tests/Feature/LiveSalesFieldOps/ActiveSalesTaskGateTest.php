<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\SalesTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Sales Field Operations - Test middleware EnsureActiveSalesTask
 * (Blueprint #13.4, #13.9): "LOGIN ≠ BOLEH BEKERJA".
 */
class ActiveSalesTaskGateTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_sales_without_active_task_is_redirected_from_operational_pages(): void
    {
        [$user] = $this->makeSalesUser();
        // Tidak ada SalesTask sama sekali untuk sales ini.

        $this->actingAs($user)
            ->get(route('sales.tagging.create'))
            ->assertRedirect(route('sales.task.show'));

        $this->actingAs($user)
            ->get(route('sales.visits.create'))
            ->assertRedirect(route('sales.task.show'));

        $this->actingAs($user)
            ->get(route('sales.transactions.create'))
            ->assertRedirect(route('sales.task.show'));

        $this->actingAs($user)
            ->get(route('sales.stock.index'))
            ->assertRedirect(route('sales.task.show'));
    }

    public function test_sales_with_draft_task_still_locked(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_DRAFT);

        $this->actingAs($user)
            ->get(route('sales.visits.create'))
            ->assertRedirect(route('sales.task.show'));
    }

    public function test_sales_with_ready_to_work_task_can_access_operational_pages(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_READY_TO_WORK);

        $this->actingAs($user)->get(route('sales.visits.create'))->assertOk();
        $this->actingAs($user)->get(route('sales.tagging.create'))->assertOk();
        $this->actingAs($user)->get(route('sales.transactions.create'))->assertOk();
        $this->actingAs($user)->get(route('sales.stock.index'))->assertOk();
    }

    public function test_sales_with_working_task_can_access_operational_pages(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);

        $this->actingAs($user)->get(route('sales.visits.create'))->assertOk();
    }

    public function test_completed_task_no_longer_counts_as_active(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_COMPLETED);

        $this->actingAs($user)
            ->get(route('sales.visits.create'))
            ->assertRedirect(route('sales.task.show'));
    }

    public function test_viewing_transaction_history_is_not_gated(): void
    {
        [$user] = $this->makeSalesUser();
        // Tanpa task aktif, index/riwayat transaksi tetap boleh diakses
        // (hanya membuat transaksi BARU yang di-gate).

        $this->actingAs($user)
            ->get(route('sales.transactions.index'))
            ->assertOk();
    }
}
