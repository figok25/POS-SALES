<?php

namespace Tests\Feature\Sales;

use App\Models\KpiPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Superadmin membuat periode KPI untuk Depo tertentu: form di halaman yang
 * sudah difilter ke satu Depo tidak boleh gagal "branch id is required".
 */
class KpiPeriodSuperAdminDepoTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function payload(array $extra = []): array
    {
        return array_merge(['name' => 'MINGGU-43', 'start_date' => '2026-10-19', 'end_date' => '2026-10-24'], $extra);
    }

    public function test_superadmin_creates_period_for_depo_selected_by_filter(): void
    {
        $super = $this->makeSuperAdminUser();
        $depo = $this->makeBranch('CBG-K1', 'Depo K1');

        // Halaman terfilter: form memuat branch di action + hidden branch_id.
        $page = $this->actingAs($super)->get(route('admin.sales.kpi.index', ['branch' => $depo->id]))->assertOk();
        $page->assertSee('name="branch_id" value="'.$depo->id.'"', false);
        $page->assertSee('periods?branch='.$depo->id, false);

        // Persis seperti form di halaman itu (action membawa ?branch=, body memuat branch_id).
        $this->actingAs($super)
            ->post(route('admin.sales.kpi.periods.store', ['branch' => $depo->id]), $this->payload(['branch_id' => $depo->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($depo->id, KpiPeriod::firstOrFail()->branch_id);
    }

    public function test_period_is_created_even_if_only_hidden_branch_id_is_sent(): void
    {
        $super = $this->makeSuperAdminUser();
        $depo = $this->makeBranch('CBG-K2', 'Depo K2');

        $this->actingAs($super)
            ->post(route('admin.sales.kpi.periods.store'), $this->payload(['branch_id' => $depo->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($depo->id, KpiPeriod::firstOrFail()->branch_id);
    }

    public function test_all_depo_view_still_requires_choosing_depo(): void
    {
        $super = $this->makeSuperAdminUser();

        $page = $this->actingAs($super)->get(route('admin.sales.kpi.index'))->assertOk();
        $page->assertSee('Pilih Depo');

        $this->actingAs($super)
            ->post(route('admin.sales.kpi.periods.store'), $this->payload())
            ->assertSessionHasErrors('branch_id');
        $this->assertSame(0, KpiPeriod::count());
    }

    public function test_regular_admin_period_uses_own_depo_and_ignores_posted_branch(): void
    {
        $admin = $this->makeAdminUser();
        $other = $this->makeBranch('CBG-K3', 'Depo K3');

        $this->actingAs($admin)
            ->post(route('admin.sales.kpi.periods.store'), $this->payload(['branch_id' => $other->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($admin->branch_id, KpiPeriod::firstOrFail()->branch_id);
    }
}
