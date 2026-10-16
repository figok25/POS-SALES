<?php

namespace Tests\Feature\LiveSalesFieldOps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Monitoring hanya memakai satu Filter Depo (yang global di atas halaman).
 */
class LiveMonitoringSingleBranchFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_page_has_only_global_depo_filter_and_polls_with_it(): void
    {
        $branch = $this->makeBranch();

        $this->actingAs($this->makeSuperAdminUser())
            ->get(route('admin.operations.live-monitoring.index', ['branch' => $branch->id]))
            ->assertOk()
            ->assertSee('id="bfBranch"', false)
            ->assertDontSee('id="branch-filter"', false)
            ->assertSee('const branch = "'.$branch->id.'"', false);
    }
}
