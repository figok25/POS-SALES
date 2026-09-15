<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Contracts\RoutingEngine;
use App\Exceptions\RoutingUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Sales Field Operations - Test Routing Engine abstraction
 * (Blueprint #67, #83 Provider Abstraction) & endpoint Basic Route.
 */
class RoutingEngineTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    protected function fakeOrsSuccess(): void
    {
        Http::fake([
            'api.openrouteservice.org/*' => Http::response([
                'features' => [[
                    'geometry' => ['coordinates' => [[112.63, -7.98], [112.631, -7.981]]],
                    'properties' => ['summary' => ['distance' => 4200.5, 'duration' => 780.2]],
                ]],
            ], 200),
        ]);
    }

    public function test_routing_engine_returns_route_result_on_success(): void
    {
        $this->fakeOrsSuccess();
        config(['services.routing.api_key' => 'dummy-key']);

        $result = app(RoutingEngine::class)->calculateRoute(-7.98, 112.63, -7.981, 112.631);

        $this->assertEquals(4200.5, $result->distanceMeters);
        $this->assertEquals(780.2, $result->durationSeconds);
        $this->assertCount(2, $result->geometry);
    }

    public function test_routing_engine_throws_when_api_key_missing(): void
    {
        config(['services.routing.api_key' => null]);

        $this->expectException(RoutingUnavailableException::class);

        app(RoutingEngine::class)->calculateRoute(-7.98, 112.63, -7.981, 112.631);
    }

    public function test_routing_engine_throws_when_provider_returns_error(): void
    {
        config(['services.routing.api_key' => 'dummy-key']);
        Http::fake(['api.openrouteservice.org/*' => Http::response(['error' => 'no route found'], 404)]);

        $this->expectException(RoutingUnavailableException::class);

        app(RoutingEngine::class)->calculateRoute(-7.98, 112.63, -7.981, 112.631);
    }

    public function test_route_endpoint_returns_geometry_for_own_customer(): void
    {
        $this->fakeOrsSuccess();
        config(['services.routing.api_key' => 'dummy-key']);
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $customer->update(['latitude' => -7.981, 'longitude' => 112.631]);

        $this->actingAs($user)
            ->postJson(route('api.sales.route.customer', $customer), [
                'latitude' => -7.98, 'longitude' => 112.63,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.distance_meters', 4200.5);
    }

    public function test_route_endpoint_rejects_customer_without_location(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id); // tanpa lat/lng

        $this->actingAs($user)
            ->postJson(route('api.sales.route.customer', $customer), [
                'latitude' => -7.98, 'longitude' => 112.63,
            ])
            ->assertStatus(422);
    }

    public function test_route_endpoint_rejects_customer_of_another_sales(): void
    {
        [$userA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $customerOfB = $this->makeCustomer($salesB->id);
        $customerOfB->update(['latitude' => -7.981, 'longitude' => 112.631]);

        $this->actingAs($userA)
            ->postJson(route('api.sales.route.customer', $customerOfB), [
                'latitude' => -7.98, 'longitude' => 112.63,
            ])
            ->assertForbidden();
    }

    public function test_route_endpoint_returns_friendly_error_when_routing_unavailable(): void
    {
        config(['services.routing.api_key' => null]);
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $customer->update(['latitude' => -7.981, 'longitude' => 112.631]);

        $this->actingAs($user)
            ->postJson(route('api.sales.route.customer', $customer), [
                'latitude' => -7.98, 'longitude' => 112.63,
            ])
            ->assertStatus(503)
            ->assertJsonPath('success', false);
    }
}
