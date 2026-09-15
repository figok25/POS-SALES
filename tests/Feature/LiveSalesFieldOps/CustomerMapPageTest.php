<?php

namespace Tests\Feature\LiveSalesFieldOps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Sales Field Operations - Test Customer Map & Customer Detail
 * (Blueprint #11, #12, #15, #16, #64/#65 MapLibre + OSM, Fase 3 WebView
 * Sales).
 */
class CustomerMapPageTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_map_index_only_shows_own_assigned_customers(): void
    {
        [$user, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $ownCustomer = $this->makeCustomer($salesA->id);
        $otherCustomer = $this->makeCustomer($salesB->id);

        $response = $this->actingAs($user)->get(route('sales.map.index'));

        $response->assertOk()
            ->assertSee($ownCustomer->name)
            ->assertDontSee($otherCustomer->name);
    }

    public function test_map_index_shows_no_map_notice_without_located_customers(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id); // tanpa lat/lng

        $this->actingAs($user)
            ->get(route('sales.map.index'))
            ->assertOk()
            ->assertSee('Belum ada Customer dengan titik lokasi');
    }

    public function test_map_shows_maplibre_script_and_configured_style_when_customer_located(): void
    {
        config(['services.maps.style_url' => 'https://tiles.openfreemap.org/styles/liberty']);
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $customer->update(['latitude' => -7.98, 'longitude' => 112.63]);

        $this->actingAs($user)
            ->get(route('sales.map.index'))
            ->assertOk()
            ->assertSee('maplibre-gl', false)
            ->assertSee('tiles.openfreemap.org', false);
    }

    public function test_customer_detail_page_renders_for_own_customer(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);

        $this->actingAs($user)
            ->get(route('sales.map.show', $customer))
            ->assertOk()
            ->assertSee($customer->name);
    }

    public function test_customer_detail_shows_no_location_notice_when_missing(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id); // tanpa lat/lng

        $this->actingAs($user)
            ->get(route('sales.map.show', $customer))
            ->assertOk()
            ->assertSee('belum memiliki titik lokasi');
    }

    public function test_customer_detail_shows_route_button_when_located(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $customer->update(['latitude' => -7.98, 'longitude' => 112.63]);

        // Tombol Route selalu muncul kalau Customer punya lokasi - map
        // (MapLibre) tidak butuh API key, hanya perhitungan rute
        // aktualnya (saat tombol diklik) yang bergantung ROUTING_API_KEY.
        $this->actingAs($user)
            ->get(route('sales.map.show', $customer))
            ->assertOk()
            ->assertSee('Hitung Route', false)
            ->assertSee('maplibre-gl', false);
    }

    public function test_sales_cannot_view_another_sales_customer_detail(): void
    {
        [$userA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $customerOfB = $this->makeCustomer($salesB->id);

        $this->actingAs($userA)
            ->get(route('sales.map.show', $customerOfB))
            ->assertForbidden();
    }
}
