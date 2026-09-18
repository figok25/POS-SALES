<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\RoutingUsage;
use App\Models\User;
use App\Services\Routing\TomTomClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section 98 (Testing Checklist), disederhanakan jadi automated test.
 *
 * Cara jalankan (di project Laravel kamu setelah file ini disalin ke tests/Feature/):
 *   php artisan test --filter=SalesRoutingFlowTest
 *
 * Catatan: test ini MENG-MOCK TomTomClient (tidak memanggil TomTom asli),
 * supaya bisa jalan tanpa API key dan tanpa memakan quota.
 */
class SalesRoutingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeSales(): User
    {
        return User::factory()->create([
            'role' => 'sales',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_sales_login_returns_token(): void
    {
        $sales = $this->makeSales();

        $response = $this->postJson('/api/sales/login', [
            'email' => $sales->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_non_sales_user_cannot_login_to_sales_app(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);

        $response = $this->postJson('/api/sales/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_location_batch_updates_last_known_position(): void
    {
        $sales = $this->makeSales();

        $response = $this->actingAs($sales, 'sanctum')->postJson('/api/sales/locations/batch', [
            'points' => [
                [
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                    'accuracy_meters' => 10,
                    'speed_mps' => 0,
                    'bearing' => 0,
                    'captured_at' => now()->valueOf(),
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $sales->refresh();
        $this->assertEquals(-6.2, (float) $sales->last_latitude);
        $this->assertEquals('ACTIVE', $sales->tracking_status);
    }

    public function test_checkin_requires_customer_to_be_assigned_today(): void
    {
        $sales = $this->makeSales();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($sales, 'sanctum')->postJson('/api/sales/visits/check-in', [
            'customer_id' => $customer->id,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
        ]);

        $response->assertStatus(403);
    }

    public function test_checkin_and_checkout_flow(): void
    {
        $sales = $this->makeSales();
        $customer = Customer::factory()->create();
        CustomerAssignment::factory()->create([
            'user_id' => $sales->id,
            'customer_id' => $customer->id,
            'assigned_date' => now()->toDateString(),
        ]);

        $checkIn = $this->actingAs($sales, 'sanctum')->postJson('/api/sales/visits/check-in', [
            'customer_id' => $customer->id,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
        ]);
        $checkIn->assertOk()->assertJson(['success' => true]);
        $visitId = $checkIn->json('visit_id');

        $checkOut = $this->actingAs($sales, 'sanctum')->postJson('/api/sales/visits/check-out', [
            'visit_id' => $visitId,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
        ]);
        $checkOut->assertOk()->assertJson(['success' => true]);
    }

    public function test_route_falls_back_when_monthly_budget_exceeded(): void
    {
        config(['routing.monthly_hard_budget' => 1]);
        RoutingUsage::create(['period_month' => now()->format('Y-m'), 'request_count' => 1]);

        $sales = $this->makeSales();
        $customer = Customer::factory()->create();
        CustomerAssignment::factory()->create([
            'user_id' => $sales->id,
            'customer_id' => $customer->id,
            'assigned_date' => now()->toDateString(),
        ]);

        $this->mock(TomTomClient::class, function ($mock) {
            $mock->shouldNotReceive('calculateRoute');
        });

        $response = $this->actingAs($sales, 'sanctum')->getJson('/api/sales/routes/today');

        $response->assertOk();
        $this->assertEquals('fallback', $response->json('source'));
    }

    public function test_reroute_is_blocked_within_cooldown(): void
    {
        config(['routing.reroute_cooldown_seconds' => 300]);

        $sales = $this->makeSales();
        $customer = Customer::factory()->create();
        CustomerAssignment::factory()->create([
            'user_id' => $sales->id,
            'customer_id' => $customer->id,
            'assigned_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->mock(TomTomClient::class, function ($mock) {
            $mock->shouldReceive('calculateRoute')->once()->andReturn([
                'distance_meters' => 1000,
                'duration_seconds' => 120,
                'geometry' => [],
                'legs' => [],
            ]);
        });

        // Panggilan pertama: bikin route awal (sekaligus jadi "existing" utk cek cooldown)
        $this->actingAs($sales, 'sanctum')->getJson('/api/sales/routes/today')->assertOk();

        // Panggilan reroute LANGSUNG setelahnya -> harus DITAHAN cooldown,
        // TomTomClient TIDAK dipanggil lagi (mock di atas cuma expect ->once()).
        $reroute = $this->actingAs($sales, 'sanctum')->postJson('/api/sales/routes/reroute', [
            'latitude' => -6.21,
            'longitude' => 106.81,
        ]);

        $reroute->assertOk();
    }
}
