<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\SalesCurrentLocation;
use App\Models\SalesDeviceEvent;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Status HP Sales (baterai, GPS, izin lokasi, jaringan): disimpan dari
 * /location & /tracking/heartbeat, dan dipakai Live Monitoring untuk menduga
 * kenapa Sales Sinyal Hilang / Offline.
 */
class DeviceStateTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -8.0954;
    private const LNG = 112.1609; // Blitar

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-05 11:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Sales bertugas dengan sesi aktif dan posisi terakhir $lastSeenMinutesAgo menit lalu. */
    private function onDuty(int $lastSeenMinutesAgo = 1): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);
        $session = SalesTrackingSession::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'sales_task_id' => $task->id,
            'started_at' => now()->subHours(3), 'status' => SalesTrackingSession::STATUS_ACTIVE,
        ]);
        SalesCurrentLocation::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG,
            'last_seen_at' => now()->subMinutes($lastSeenMinutesAgo), 'status' => SalesCurrentLocation::STATUS_ACTIVE,
        ]);

        return [$user, $sales, $session];
    }

    private function row(int $salesId): array
    {
        $json = $this->actingAs($this->makeSuperAdminUser())->getJson(route('api.admin.live-sales'))->assertOk()->json();

        return collect($json['data'])->firstWhere('sales_id', $salesId);
    }

    public function test_location_update_stores_device_state_on_current_and_history(): void
    {
        [$user, , $session] = $this->onDuty();

        $this->actingAs($user)->postJson(route('api.sales.location'), [
            'location_event_id' => (string) Str::uuid(),
            'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG,
            'recorded_at' => now()->toIso8601String(),
            'battery_level' => 63, 'is_charging' => false, 'gps_enabled' => true,
            'location_permission' => 'always', 'network_type' => 'mobile',
        ])->assertOk();

        $this->assertDatabaseHas('sales_current_locations', ['battery_level' => 63, 'gps_enabled' => true, 'location_permission' => 'always', 'network_type' => 'mobile']);
        $this->assertDatabaseHas('sales_location_histories', ['battery_level' => 63, 'gps_enabled' => true, 'network_type' => 'mobile']);
    }

    public function test_location_update_without_device_fields_still_works(): void
    {
        [$user, , $session] = $this->onDuty();

        $this->actingAs($user)->postJson(route('api.sales.location'), [
            'location_event_id' => (string) Str::uuid(),
            'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG,
            'recorded_at' => now()->toIso8601String(),
        ])->assertOk();

        $this->assertDatabaseHas('sales_current_locations', ['battery_level' => null, 'device_seen_at' => null]);
    }

    public function test_invalid_device_values_never_break_the_location_update(): void
    {
        [$user, , $session] = $this->onDuty();

        $this->actingAs($user)->postJson(route('api.sales.location'), [
            'location_event_id' => (string) Str::uuid(),
            'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG,
            'recorded_at' => now()->toIso8601String(),
            'battery_level' => 250, 'network_type' => 'satelit', 'gps_enabled' => 'mungkin',
        ])->assertOk();

        $this->assertDatabaseHas('sales_location_histories', ['battery_level' => null, 'network_type' => null]);
    }

    public function test_heartbeat_updates_device_state_without_touching_location_or_status(): void
    {
        [$user, $sales] = $this->onDuty(lastSeenMinutesAgo: 12);
        $before = SalesCurrentLocation::where('sales_id', $sales->id)->first();

        $this->actingAs($user)->postJson(route('api.sales.tracking.heartbeat'), [
            'battery_level' => 41, 'gps_enabled' => false, 'location_permission' => 'always', 'network_type' => 'wifi',
        ])->assertOk()->assertJson(['success' => true]);

        $after = SalesCurrentLocation::where('sales_id', $sales->id)->first();
        $this->assertSame(41, $after->battery_level);
        $this->assertFalse($after->gps_enabled);
        $this->assertNotNull($after->device_seen_at);
        $this->assertTrue($after->last_seen_at->equalTo($before->last_seen_at));   // lokasi TIDAK dianggap baru
        $this->assertSame($before->status, $after->status);
    }

    public function test_heartbeat_records_an_event(): void
    {
        [$user, $sales] = $this->onDuty();

        $this->actingAs($user)->postJson(route('api.sales.tracking.heartbeat'), [
            'event' => 'gps_off', 'gps_enabled' => false, 'battery_level' => 55,
        ])->assertOk();

        $this->assertDatabaseHas('sales_device_events', ['sales_id' => $sales->id, 'event' => 'gps_off', 'battery_level' => 55]);
    }

    public function test_unknown_event_is_ignored(): void
    {
        [$user] = $this->onDuty();

        $this->actingAs($user)->postJson(route('api.sales.tracking.heartbeat'), ['event' => 'hack', 'battery_level' => 50])->assertOk();

        $this->assertDatabaseCount('sales_device_events', 0);
    }

    public function test_older_queued_heartbeat_does_not_overwrite_newer_state(): void
    {
        [$user, $sales] = $this->onDuty();

        $this->actingAs($user)->postJson(route('api.sales.tracking.heartbeat'), ['battery_level' => 30, 'occurred_at' => now()->toIso8601String()])->assertOk();
        $this->actingAs($user)->postJson(route('api.sales.tracking.heartbeat'), ['battery_level' => 90, 'occurred_at' => now()->subMinutes(20)->toIso8601String()])->assertOk();

        $this->assertSame(30, SalesCurrentLocation::where('sales_id', $sales->id)->value('battery_level'));
    }

    // ---------------------------------------------------- dugaan alasan

    public function test_gps_turned_off_is_the_suspected_reason(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 12);
        SalesCurrentLocation::where('sales_id', $sales->id)->update([
            'gps_enabled' => false, 'battery_level' => 70, 'device_seen_at' => now()->subMinute(),
        ]);

        $row = $this->row($sales->id);

        $this->assertSame('signal_lost', $row['effective_status']);
        $this->assertSame('gps_off', $row['offline_reason']);
    }

    public function test_gps_off_event_right_before_losing_contact_is_the_reason(): void
    {
        [, $sales, $session] = $this->onDuty(lastSeenMinutesAgo: 45);
        SalesCurrentLocation::where('sales_id', $sales->id)->update(['device_seen_at' => now()->subMinutes(44), 'battery_level' => 60]);
        SalesDeviceEvent::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'tracking_session_id' => $session->id,
            'event' => SalesDeviceEvent::GPS_OFF, 'occurred_at' => now()->subMinutes(45), 'received_at' => now()->subMinutes(45),
        ]);

        $row = $this->row($sales->id);

        $this->assertSame('offline', $row['effective_status']);
        $this->assertSame('gps_off', $row['offline_reason']);
        $this->assertSame('gps_off', $row['device_last_event']);
    }

    public function test_permission_revoked_is_the_suspected_reason(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 12);
        SalesCurrentLocation::where('sales_id', $sales->id)->update([
            'gps_enabled' => true, 'location_permission' => 'denied', 'device_seen_at' => now()->subMinute(),
        ]);

        $this->assertSame('permission_denied', $this->row($sales->id)['offline_reason']);
    }

    public function test_very_low_battery_and_no_contact_means_battery_dead(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 40);
        SalesCurrentLocation::where('sales_id', $sales->id)->update([
            'gps_enabled' => true, 'battery_level' => 3, 'is_charging' => false, 'device_seen_at' => now()->subMinutes(40),
        ]);

        $row = $this->row($sales->id);

        $this->assertSame('offline', $row['effective_status']);
        $this->assertSame('battery_dead', $row['offline_reason']);
        $this->assertStringContainsString('3%', $row['offline_reason_label']);
    }

    public function test_charging_phone_is_not_called_battery_dead(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 40);
        SalesCurrentLocation::where('sales_id', $sales->id)->update([
            'battery_level' => 3, 'is_charging' => true, 'device_seen_at' => now()->subMinutes(40),
        ]);

        $this->assertSame('no_contact', $this->row($sales->id)['offline_reason']);
    }

    public function test_alive_phone_with_gps_on_but_no_points_is_app_alive_no_gps(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 12);
        SalesCurrentLocation::where('sales_id', $sales->id)->update([
            'gps_enabled' => true, 'location_permission' => 'always', 'battery_level' => 80, 'device_seen_at' => now()->subMinute(),
        ]);

        $this->assertSame('app_alive_no_gps', $this->row($sales->id)['offline_reason']);
    }

    public function test_lost_contact_entirely_is_no_contact(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 40);
        SalesCurrentLocation::where('sales_id', $sales->id)->update(['battery_level' => 55, 'device_seen_at' => now()->subMinutes(40)]);

        $this->assertSame('no_contact', $this->row($sales->id)['offline_reason']);
    }

    public function test_old_app_without_device_state_gives_no_guess(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 40);

        $row = $this->row($sales->id);

        $this->assertSame('offline', $row['effective_status']);
        $this->assertNull($row['offline_reason']);
    }

    public function test_no_guess_while_sales_is_active(): void
    {
        [, $sales] = $this->onDuty(lastSeenMinutesAgo: 1);
        SalesCurrentLocation::where('sales_id', $sales->id)->update(['gps_enabled' => false, 'device_seen_at' => now()]);

        $this->assertNull($this->row($sales->id)['offline_reason']);
    }

    public function test_low_battery_flag_is_set_only_when_not_charging(): void
    {
        [, $sales] = $this->onDuty();
        SalesCurrentLocation::where('sales_id', $sales->id)->update(['battery_level' => 12, 'is_charging' => false]);
        $this->assertTrue($this->row($sales->id)['device_battery_low']);

        SalesCurrentLocation::where('sales_id', $sales->id)->update(['is_charging' => true]);
        $this->assertFalse($this->row($sales->id)['device_battery_low']);
    }
}
