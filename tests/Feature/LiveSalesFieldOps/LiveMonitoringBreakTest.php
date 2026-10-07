<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\Sales;
use App\Models\SalesBreak;
use App\Models\SalesCurrentLocation;
use App\Models\SalesLocationHistory;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Monitoring (khusus Super Admin): alert Sales diam >= 15 menit dan
 * tombol Istirahat (Sales istirahat tidak dihitung diam).
 */
class LiveMonitoringBreakTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -7.9797;
    private const LNG = 112.6304;

    /** Sales yang sedang bertugas: Task working + sesi tracking aktif + posisi terakhir 1 menit lalu. */
    private function onDutySales(?string $status = SalesCurrentLocation::STATUS_ACTIVE): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);

        $session = SalesTrackingSession::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'sales_task_id' => $task->id,
            'started_at' => now()->subHours(2), 'status' => SalesTrackingSession::STATUS_ACTIVE,
        ]);

        SalesCurrentLocation::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG, 'last_seen_at' => now()->subMinute(), 'status' => $status,
        ]);

        return [$user, $sales, $session];
    }

    /** Titik riwayat tiap $stepMinutes menit mundur sampai $spanMinutes, jarak ~$driftMeters per titik. */
    private function history(Sales $sales, SalesTrackingSession $session, int $spanMinutes, float $driftMeters = 0): void
    {
        $i = 0;
        for ($m = 1; $m <= $spanMinutes; $m += 2) {
            SalesLocationHistory::create([
                'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'tracking_session_id' => $session->id,
                'location_event_id' => (string) Str::uuid(),
                'latitude' => self::LAT + ($i * $driftMeters / 111000),
                'longitude' => self::LNG,
                'recorded_at' => now()->subMinutes($m), 'received_at' => now(),
            ]);
            $i++;
        }
    }

    private function liveData($superAdmin, int $salesId): array
    {
        $json = $this->actingAs($superAdmin)->getJson(route('api.admin.live-sales'))->assertOk()->json();

        return collect($json['data'])->firstWhere('sales_id', $salesId);
    }

    // ------------------------------------------------------------- diam

    public function test_stationary_sales_for_20_minutes_triggers_idle_alert(): void
    {
        [, $sales, $session] = $this->onDutySales();
        $this->history($sales, $session, 22);

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertSame('idle', $row['effective_status']);
        $this->assertTrue($row['idle_alert']);
        $this->assertGreaterThanOrEqual(15, $row['idle_minutes']);
    }

    public function test_stationary_for_only_8_minutes_is_not_an_alert(): void
    {
        [, $sales, $session] = $this->onDutySales();
        $this->history($sales, $session, 8);

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertSame('active', $row['effective_status']);
        $this->assertFalse($row['idle_alert']);
    }

    public function test_moving_sales_is_never_idle(): void
    {
        [, $sales, $session] = $this->onDutySales();
        $this->history($sales, $session, 40, 200); // bergeser ~200 m tiap titik

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertSame('active', $row['effective_status']);
        $this->assertFalse($row['idle_alert']);
    }

    public function test_sales_at_customer_is_not_idle(): void
    {
        [, $sales, $session] = $this->onDutySales(SalesCurrentLocation::STATUS_AT_CUSTOMER);
        $this->history($sales, $session, 30);

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertSame('at_customer', $row['effective_status']);
        $this->assertFalse($row['idle_alert']);
    }

    public function test_old_location_is_signal_lost_not_idle(): void
    {
        [, $sales, $session] = $this->onDutySales();
        $this->history($sales, $session, 30);
        SalesCurrentLocation::where('sales_id', $sales->id)->update(['last_seen_at' => now()->subMinutes(10)]);

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertSame('signal_lost', $row['effective_status']);
        $this->assertFalse($row['idle_alert']);
    }

    // --------------------------------------------------------- istirahat

    public function test_break_requires_active_tracking(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertStatus(422);
        $this->assertSame(0, SalesBreak::count());
    }

    public function test_sales_on_break_is_not_idle_and_shows_break_minutes(): void
    {
        [$user, $sales, $session] = $this->onDutySales();
        $this->history($sales, $session, 30);

        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertCreated()->assertJsonPath('on_break', true);
        SalesBreak::where('sales_id', $sales->id)->update(['started_at' => now()->subMinutes(20)]);

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertSame('on_break', $row['effective_status']);
        $this->assertFalse($row['idle_alert']);
        $this->assertGreaterThanOrEqual(20, $row['break_minutes']);
        $this->assertFalse($row['break_overdue']);
    }

    public function test_break_longer_than_limit_is_flagged_overdue(): void
    {
        [$user, $sales] = $this->onDutySales();
        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertCreated();
        SalesBreak::where('sales_id', $sales->id)->update(['started_at' => now()->subMinutes(75)]);

        $row = $this->liveData($this->makeSuperAdminUser(), $sales->id);

        $this->assertTrue($row['break_overdue']);
    }

    public function test_break_start_is_idempotent_and_end_returns_to_active(): void
    {
        [$user, $sales] = $this->onDutySales();

        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertCreated();
        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertOk()->assertJsonPath('on_break', true);
        $this->assertSame(1, SalesBreak::count());

        $this->actingAs($user)->getJson(route('api.sales.break.status'))->assertJsonPath('on_break', true);

        $this->actingAs($user)->postJson(route('api.sales.break.end'))->assertOk()->assertJsonPath('on_break', false);
        $this->assertNotNull(SalesBreak::first()->ended_at);
        $this->assertSame(SalesCurrentLocation::STATUS_ACTIVE, SalesCurrentLocation::where('sales_id', $sales->id)->value('status'));
    }

    public function test_location_ping_during_break_keeps_on_break_status(): void
    {
        [$user, $sales, $session] = $this->onDutySales();
        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertCreated();

        $this->actingAs($user)->postJson(route('api.sales.location'), [
            'location_event_id' => (string) Str::uuid(), 'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG, 'recorded_at' => now()->toIso8601String(),
        ])->assertOk();

        $this->assertSame(SalesCurrentLocation::STATUS_ON_BREAK, SalesCurrentLocation::where('sales_id', $sales->id)->value('status'));
    }

    public function test_break_cannot_start_during_ongoing_visit(): void
    {
        [$user, $sales] = $this->onDutySales();
        $customer = $this->makeCustomer($sales->id);
        Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id, 'check_in_at' => now(),
            'check_in_condition' => Visit::CONDITION_NORMAL, 'status' => Visit::STATUS_ONGOING,
        ]);

        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertStatus(422);
    }

    public function test_return_stock_style_stop_closes_open_break(): void
    {
        [$user, $sales] = $this->onDutySales();
        $this->actingAs($user)->postJson(route('api.sales.break.start'))->assertCreated();

        app(\App\Services\TrackingSessionService::class)->stopForSales($sales->id);

        $this->assertNull(SalesBreak::openFor($sales->id));
    }

    // ------------------------------------------------- hanya Super Admin

    public function test_live_monitoring_is_super_admin_only(): void
    {
        [, $sales] = $this->onDutySales();
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->get(route('admin.operations.live-monitoring.index'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.admin.live-sales'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.admin.sales.locations', $sales))->assertForbidden();

        $super = $this->makeSuperAdminUser();
        $this->actingAs($super)->get(route('admin.operations.live-monitoring.index'))->assertOk();
        $this->actingAs($super)->getJson(route('api.admin.live-sales'))->assertOk();
    }

    public function test_menu_item_visible_only_to_super_admin(): void
    {
        $label = 'Live Monitoring Sales';

        $this->actingAs($this->makeAdminUser())->get(route('admin.dashboard'))->assertOk()->assertDontSee($label);
        $this->actingAs($this->makeSuperAdminUser())->get(route('admin.dashboard'))->assertOk()->assertSee($label);
    }

    public function test_sales_pages_render_the_break_button(): void
    {
        [$user] = $this->onDutySales();

        $this->actingAs($user)->get(route('sales.dashboard'))->assertOk()->assertSee('Mulai Istirahat');
        $this->actingAs($user)->get(route('sales.tracking.show'))->assertOk()->assertSee('Mulai Istirahat');
    }
}
