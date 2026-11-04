<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\Customer;
use App\Models\Sales;
use App\Models\SalesCurrentLocation;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Monitoring: info kunjungan berjalan (nama toko, lama, jarak, alert
 * "jauh dari toko" untuk kasus Sales pulang tapi lupa check-out).
 */
class LiveMonitoringVisitInfoTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -7.9797;
    private const LNG = 112.6304;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-08 11:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function onDutySales(string $status = SalesCurrentLocation::STATUS_AT_CUSTOMER): Sales
    {
        [, $sales] = $this->makeSalesUser();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);

        $session = SalesTrackingSession::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'sales_task_id' => $task->id,
            'started_at' => now()->subHours(2), 'status' => SalesTrackingSession::STATUS_ACTIVE,
        ]);

        SalesCurrentLocation::create([
            'sales_id' => $sales->id, 'branch_id' => $sales->branch_id, 'tracking_session_id' => $session->id,
            'latitude' => self::LAT, 'longitude' => self::LNG, 'last_seen_at' => now()->subMinute(), 'status' => $status,
        ]);

        return $sales;
    }

    /** Toko di posisi Sales + $offsetMeters ke utara (1 derajat ~ 111 km). */
    private function customerAt(Sales $sales, float $offsetMeters): Customer
    {
        $customer = $this->makeCustomer($sales->id);
        $customer->update([
            'latitude' => self::LAT + ($offsetMeters / 111000),
            'longitude' => self::LNG,
        ]);

        return $customer;
    }

    private function ongoingVisit(Sales $sales, Customer $customer, int $minutesAgo): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id, 'check_in_at' => now()->subMinutes($minutesAgo),
            'check_in_condition' => Visit::CONDITION_NORMAL, 'status' => Visit::STATUS_ONGOING,
        ]);
    }

    private function row(int $salesId): array
    {
        $json = $this->actingAs($this->makeSuperAdminUser())->getJson(route('api.admin.live-sales'))->assertOk()->json();

        return collect($json['data'])->firstWhere('sales_id', $salesId);
    }

    public function test_ongoing_visit_shows_store_name_duration_and_point(): void
    {
        $sales = $this->onDutySales();
        $customer = $this->customerAt($sales, 20);
        $this->ongoingVisit($sales, $customer, 25);

        $row = $this->row($sales->id);

        $this->assertSame($customer->name, $row['visit_customer_name']);
        $this->assertSame(25, $row['visit_minutes']);
        $this->assertEqualsWithDelta((float) $customer->latitude, $row['visit_latitude'], 0.000001);
        $this->assertEqualsWithDelta((float) $customer->longitude, $row['visit_longitude'], 0.000001);
        $this->assertLessThan(50, $row['visit_distance_meters']);
        $this->assertFalse($row['visit_far_alert']);
        $this->assertFalse($row['visit_long_alert']);
    }

    public function test_sales_far_from_store_triggers_far_alert(): void
    {
        $sales = $this->onDutySales();
        $this->ongoingVisit($sales, $this->customerAt($sales, 2000), 120);

        $row = $this->row($sales->id);

        $this->assertSame('at_customer', $row['effective_status']);
        $this->assertGreaterThan(1500, $row['visit_distance_meters']);
        $this->assertTrue($row['visit_far_alert']);
    }

    public function test_long_visit_triggers_long_alert_but_not_far_alert(): void
    {
        $sales = $this->onDutySales();
        $this->ongoingVisit($sales, $this->customerAt($sales, 10), 90);

        $row = $this->row($sales->id);

        $this->assertSame(90, $row['visit_minutes']);
        $this->assertTrue($row['visit_long_alert']);
        $this->assertFalse($row['visit_far_alert']);
    }

    public function test_no_ongoing_visit_gives_empty_visit_fields(): void
    {
        $sales = $this->onDutySales(SalesCurrentLocation::STATUS_ACTIVE);

        $row = $this->row($sales->id);

        $this->assertNull($row['visit_customer_name']);
        $this->assertNull($row['visit_minutes']);
        $this->assertNull($row['visit_distance_meters']);
        $this->assertFalse($row['visit_far_alert']);
        $this->assertFalse($row['visit_long_alert']);
    }

    public function test_finished_visit_is_ignored(): void
    {
        $sales = $this->onDutySales(SalesCurrentLocation::STATUS_ACTIVE);
        $visit = $this->ongoingVisit($sales, $this->customerAt($sales, 2000), 60);
        $visit->update(['status' => Visit::STATUS_COMPLETED, 'check_out_at' => now()]);

        $row = $this->row($sales->id);

        $this->assertNull($row['visit_customer_name']);
        $this->assertFalse($row['visit_far_alert']);
    }

    public function test_store_without_coordinates_falls_back_to_check_in_point(): void
    {
        $sales = $this->onDutySales();
        $customer = $this->makeCustomer($sales->id); // tanpa koordinat
        $visit = $this->ongoingVisit($sales, $customer, 10);
        $visit->update([
            'check_in_latitude' => self::LAT + (2000 / 111000),
            'check_in_longitude' => self::LNG,
        ]);

        $row = $this->row($sales->id);

        $this->assertSame($customer->name, $row['visit_customer_name']);
        $this->assertTrue($row['visit_far_alert']);
    }

    public function test_store_without_any_coordinates_has_no_distance_and_no_far_alert(): void
    {
        $sales = $this->onDutySales();
        $this->ongoingVisit($sales, $this->makeCustomer($sales->id), 10);

        $row = $this->row($sales->id);

        $this->assertNull($row['visit_distance_meters']);
        $this->assertFalse($row['visit_far_alert']);
    }
}
