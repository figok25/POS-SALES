<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\SalesTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Zona waktu bisnis = Asia/Jakarta (WIB). Waktu lokasi dari perangkat
 * (Android: +07:00, browser: Z/UTC) harus dikonversi ke zona aplikasi saat
 * disimpan supaya recorded_at sebanding dengan received_at/created_at.
 */
class LocationTimezoneTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_application_timezone_is_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('+07:00', now()->format('P'));
    }

    /**
     * Kirim satu lokasi dengan recorded_at tertentu dan kembalikan nilai
     * mentah kolom recorded_at di database (tanpa konversi cast).
     */
    private function storedRecordedAtFor(string $recordedAt): string
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales, SalesTask::STATUS_WORKING);

        $sessionId = $this->actingAs($user)
            ->postJson(route('api.sales.tracking.start'), [
                'latitude' => -7.25, 'longitude' => 112.75, 'platform' => 'web',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->postJson(route('api.sales.location'), [
                'location_event_id' => (string) Str::uuid(),
                'tracking_session_id' => $sessionId,
                'latitude' => -7.25,
                'longitude' => 112.75,
                'recorded_at' => $recordedAt,
            ])
            ->assertOk();

        return (string) DB::table('sales_location_histories')->value('recorded_at');
    }

    public function test_utc_timestamp_from_browser_is_converted_to_wib(): void
    {
        // 06:00 UTC = 13:00 WIB
        $this->assertSame('2026-10-05 13:00:00', $this->storedRecordedAtFor('2026-10-05T06:00:00Z'));
    }

    public function test_wib_timestamp_from_android_is_kept_as_wib(): void
    {
        $this->assertSame('2026-10-05 13:00:00', $this->storedRecordedAtFor('2026-10-05T13:00:00+07:00'));
    }

    public function test_timestamp_from_other_offset_is_converted_to_wib(): void
    {
        // 15:00 di +09:00 (WIT) = 13:00 WIB
        $this->assertSame('2026-10-05 13:00:00', $this->storedRecordedAtFor('2026-10-05T15:00:00+09:00'));
    }

    public function test_date_crossing_midnight_lands_on_the_correct_wib_day(): void
    {
        // 18:30 UTC tanggal 5 = 01:30 WIB tanggal 6
        $this->assertSame('2026-10-06 01:30:00', $this->storedRecordedAtFor('2026-10-05T18:30:00Z'));
    }
}
