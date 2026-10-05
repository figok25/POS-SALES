<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\Sales;
use App\Models\SalesCurrentLocation;
use App\Models\SalesLocationHistory;
use App\Models\SalesTrackingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * php artisan tracking:prune -- hapus riwayat lokasi lama.
 */
class PruneLocationHistoryTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private Sales $sales;

    private SalesTrackingSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = $this->makeBranch();
        [, $this->sales] = $this->makeSalesUser($branch);

        $this->session = SalesTrackingSession::create([
            'sales_id' => $this->sales->id,
            'branch_id' => $branch->id,
            'started_at' => now(),
            'status' => SalesTrackingSession::STATUS_ACTIVE,
        ]);
    }

    private function point(int $daysAgo): SalesLocationHistory
    {
        return SalesLocationHistory::create([
            'sales_id' => $this->sales->id,
            'location_event_id' => (string) Str::uuid(),
            'branch_id' => $this->sales->branch_id,
            'tracking_session_id' => $this->session->id,
            'latitude' => -7.25,
            'longitude' => 112.75,
            'recorded_at' => now()->subDays($daysAgo),
            'received_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_prune_deletes_only_rows_older_than_retention(): void
    {
        $old = $this->point(61);
        $inside = $this->point(59);
        $today = $this->point(0);

        $this->artisan('tracking:prune', ['--days' => 60])->assertSuccessful();

        $this->assertDatabaseMissing('sales_location_histories', ['id' => $old->id]);
        $this->assertDatabaseHas('sales_location_histories', ['id' => $inside->id]);
        $this->assertDatabaseHas('sales_location_histories', ['id' => $today->id]);
    }

    public function test_dry_run_counts_but_deletes_nothing(): void
    {
        $old = $this->point(90);

        $this->artisan('tracking:prune', ['--days' => 60, '--dry-run' => true])
            ->expectsOutputToContain('1 baris')
            ->assertSuccessful();

        $this->assertDatabaseHas('sales_location_histories', ['id' => $old->id]);
    }

    public function test_default_retention_comes_from_config(): void
    {
        config(['sales.location_retention_days' => 10]);

        $old = $this->point(11);
        $inside = $this->point(9);

        $this->artisan('tracking:prune')->assertSuccessful();

        $this->assertDatabaseMissing('sales_location_histories', ['id' => $old->id]);
        $this->assertDatabaseHas('sales_location_histories', ['id' => $inside->id]);
    }

    public function test_prune_never_touches_current_location_or_sessions(): void
    {
        SalesCurrentLocation::create([
            'sales_id' => $this->sales->id,
            'branch_id' => $this->sales->branch_id,
            'latitude' => -7.25,
            'longitude' => 112.75,
            'last_seen_at' => now()->subDays(200),
            'status' => SalesCurrentLocation::STATUS_ACTIVE,
        ]);
        $this->point(200);

        $this->artisan('tracking:prune', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseCount('sales_location_histories', 0);
        $this->assertDatabaseCount('sales_current_locations', 1);
        $this->assertDatabaseHas('sales_tracking_sessions', ['id' => $this->session->id]);
    }

    public function test_invalid_days_is_rejected(): void
    {
        $old = $this->point(500);

        $this->artisan('tracking:prune', ['--days' => 0])->assertFailed();

        $this->assertDatabaseHas('sales_location_histories', ['id' => $old->id]);
    }
}
