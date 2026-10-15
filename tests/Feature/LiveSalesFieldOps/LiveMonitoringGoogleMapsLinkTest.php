<?php

namespace Tests\Feature\LiveSalesFieldOps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Popup titik Sales di Live Monitoring memuat tautan "Buka di Google Maps".
 */
class LiveMonitoringGoogleMapsLinkTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_live_monitoring_page_builds_google_maps_link_for_marker_popup(): void
    {
        $this->actingAs($this->makeSuperAdminUser())
            ->get(route('admin.operations.live-monitoring.index'))
            ->assertOk()
            ->assertSee('https://www.google.com/maps/search/?api=1&query=', false)
            ->assertSee('Buka di Google Maps');
    }
}
