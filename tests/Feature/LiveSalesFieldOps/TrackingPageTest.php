<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\SalesTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Str;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Sales Field Operations - Test halaman & alur Tracking (Blueprint
 * #21 Tracking Session, #24 Location Update, Fase 3 WebView Sales).
 */
class TrackingPageTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_tracking_page_renders(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)
            ->get(route('sales.tracking.show'))
            ->assertOk()
            ->assertSee('Start Tracking', false);
    }

    public function test_full_tracking_flow_start_location_stop(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);

        $start = $this->actingAs($user)
            ->postJson(route('api.sales.tracking.start'), [
                'latitude' => -7.98, 'longitude' => 112.63, 'platform' => 'web',
            ])
            ->assertCreated();

        $sessionId = $start->json('data.id');
        $this->assertNotNull($sessionId);

        $this->actingAs($user)
            ->getJson(route('api.sales.tracking.status'))
            ->assertOk()
            ->assertJsonPath('data.tracking_active', true);

        $this->actingAs($user)
            ->postJson(route('api.sales.location'), [
                'location_event_id' => (string) Str::uuid(),
                'tracking_session_id' => $sessionId,
                'latitude' => -7.981,
                'longitude' => 112.631,
                'accuracy' => 15,
                'recorded_at' => now()->toIso8601String(),
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('api.sales.tracking.stop'), [
                'latitude' => -7.982, 'longitude' => 112.632,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('api.sales.tracking.status'))
            ->assertOk()
            ->assertJsonPath('data.tracking_active', false);
    }

    public function test_duplicate_location_event_id_is_idempotent(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);

        $start = $this->actingAs($user)->postJson(route('api.sales.tracking.start'), [
            'latitude' => -7.98, 'longitude' => 112.63,
        ]);
        $sessionId = $start->json('data.id');
        $eventId = (string) Str::uuid();

        $payload = [
            'location_event_id' => $eventId,
            'tracking_session_id' => $sessionId,
            'latitude' => -7.981,
            'longitude' => 112.631,
            'recorded_at' => now()->toIso8601String(),
        ];

        $this->actingAs($user)->postJson(route('api.sales.location'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('api.sales.location'), $payload)->assertOk();

        // Kirim ulang location_event_id yang sama TIDAK boleh membuat baris ganda.
        $this->assertDatabaseCount('sales_location_histories', 1);
    }

    public function test_tracking_start_rejected_without_active_task(): void
    {
        [$user] = $this->makeSalesUser();
        // Tidak ada task working/ready_to_work.

        $this->actingAs($user)
            ->postJson(route('api.sales.tracking.start'), ['latitude' => -7.98, 'longitude' => 112.63])
            ->assertForbidden();
    }
}
