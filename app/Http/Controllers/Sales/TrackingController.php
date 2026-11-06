<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\LocationUpdateRequest;
use App\Http\Requests\Sales\TrackingStartRequest;
use App\Http\Requests\Sales\TrackingStopRequest;
use App\Models\SalesBreak;
use App\Models\SalesCurrentLocation;
use App\Models\SalesDevice;
use App\Models\SalesDeviceEvent;
use App\Models\SalesLocationHistory;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;
use App\Models\Visit;
use App\Services\TrackingSessionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Live Sales Field Operations - Tracking API (Blueprint #21, #22, #23,
 * #25, #38, Fase 2):
 *
 *   POST /api/sales/tracking/start
 *   POST /api/sales/tracking/stop
 *   POST /api/sales/location
 *   POST /api/sales/tracking/heartbeat   (status HP tanpa koordinat)
 *   GET  /api/sales/tracking/status
 */
class TrackingController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected TrackingSessionService $trackingService)
    {
    }

    public function status()
    {
        $sales = $this->currentSales();

        $session = SalesTrackingSession::where('sales_id', $sales->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        // Tracking otomatis (tanpa tombol Start): normalnya sesi sudah dibuat
        // saat Admin Apply/Release. Kalau Sales punya Task HARI INI yang
        // sudah di-Apply tapi belum selesai dan sesinya belum ada (mis. Task
        // di-Apply sebelum fitur ini ada), sesi dibuat di sini. Task
        // COMPLETED (setelah Return Stock) sengaja tidak termasuk, supaya
        // tracking tidak menyala lagi sesudah selesai.
        if (! $session) {
            $task = SalesTask::where('sales_id', $sales->id)
                ->whereDate('task_date', today())
                ->whereIn('status', [
                    SalesTask::STATUS_DOCUMENT_AVAILABLE,
                    SalesTask::STATUS_STOCK_VERIFICATION,
                    SalesTask::STATUS_STOCK_VARIANCE,
                    SalesTask::STATUS_READY_TO_WORK,
                    SalesTask::STATUS_WORKING,
                ])
                ->latest('id')
                ->first();

            if ($task) {
                $session = $this->trackingService->startForTask($task);
            }
        }

        $current = SalesCurrentLocation::where('sales_id', $sales->id)->first();

        // PERBAIKAN AUDIT #8 (P0/P1): Android TrackingStatusResponse mengharapkan
        // field TOP-LEVEL `status` (string) dan `tracking_session_id`, bukan
        // hanya nested di dalam `data`. Field `data` dipertahankan untuk WebView JS.
        return response()->json([
            'success' => true,
            'status' => $session ? 'ACTIVE' : 'STOPPED',
            'tracking_session_id' => $session?->id,
            'data' => [
                'tracking_active' => (bool) $session,
                'session' => $session,
                'current_location' => $current,
            ],
        ]);
    }

    public function start(TrackingStartRequest $request)
    {
        $sales = $this->currentSales();

        // Prasyarat Start Work/Start Tracking (Blueprint #13.7): harus ada
        // task aktif yang sudah Ready to Work atau sedang Working.
        $task = SalesTask::where('sales_id', $sales->id)
            ->whereIn('status', [SalesTask::STATUS_READY_TO_WORK, SalesTask::STATUS_WORKING])
            ->latest('id')
            ->first();

        if (! $task) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki tugas aktif yang siap dikerjakan.',
            ], 403);
        }

        // Idempotent: kalau sudah ada session aktif, kembalikan yang sama.
        $existing = SalesTrackingSession::where('sales_id', $sales->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'tracking_session_id' => $existing->id,
                'message' => 'Sesi tracking sudah aktif.',
                'data' => $existing,
            ]);
        }

        $session = DB::transaction(function () use ($request, $sales, $task) {
            $device = null;
            if ($request->filled('device_identifier')) {
                $device = SalesDevice::updateOrCreate(
                    ['device_identifier' => $request->validated('device_identifier')],
                    [
                        'sales_id' => $sales->id,
                        'device_name' => $request->validated('device_name'),
                        'platform' => $request->validated('platform'),
                        'app_version' => $request->validated('app_version'),
                        'last_seen_at' => now(),
                        'status' => SalesDevice::STATUS_ACTIVE,
                        'registered_at' => now(),
                    ]
                );
            }

            if ($task->status === SalesTask::STATUS_READY_TO_WORK) {
                $task->update(['status' => SalesTask::STATUS_WORKING, 'started_at' => now()]);
            }

            $session = SalesTrackingSession::create([
                'sales_id' => $sales->id,
                'branch_id' => $task->branch_id,
                'sales_task_id' => $task->id,
                'device_id' => $device?->id,
                'started_at' => now(),
                'start_latitude' => $request->validated('latitude'),
                'start_longitude' => $request->validated('longitude'),
                'status' => SalesTrackingSession::STATUS_ACTIVE,
            ]);

            if ($request->filled('latitude') && $request->filled('longitude')) {
                SalesCurrentLocation::updateOrCreate(
                    ['sales_id' => $sales->id],
                    [
                        'branch_id' => $task->branch_id,
                        'tracking_session_id' => $session->id,
                        'latitude' => $request->validated('latitude'),
                        'longitude' => $request->validated('longitude'),
                        'last_seen_at' => now(),
                        'status' => SalesCurrentLocation::STATUS_ACTIVE,
                    ]
                );
            }

            return $session;
        });

        // PERBAIKAN AUDIT #8: tambahkan field top-level tracking_session_id +
        // message supaya cocok dengan Android TrackingStartResponse(success,
        // trackingSessionId, message).
        return response()->json([
            'success' => true,
            'tracking_session_id' => $session->id,
            'message' => 'Tracking dimulai.',
            'data' => $session,
        ], 201);
    }

    public function stop(TrackingStopRequest $request)
    {
        $sales = $this->currentSales();

        $session = SalesTrackingSession::where('sales_id', $sales->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        if (! $session) {
            // PERBAIKAN AUDIT #4/#35: Stop harus idempotent juga - kalau native
            // service memanggil stop tapi server sudah tidak ada sesi aktif
            // (mis. race condition/retry), jangan balikan error yang bikin
            // Android bingung; anggap sudah stopped.
            return response()->json([
                'success' => true,
                'tracking_session_id' => null,
                'message' => 'Tidak ada sesi tracking aktif (sudah berhenti).',
            ]);
        }

        $session->update([
            'ended_at' => now(),
            'end_latitude' => $request->validated('latitude'),
            'end_longitude' => $request->validated('longitude'),
            'status' => SalesTrackingSession::STATUS_COMPLETED,
        ]);

        SalesBreak::endOpenFor($sales->id);

        SalesCurrentLocation::where('sales_id', $sales->id)->update([
            'status' => SalesCurrentLocation::STATUS_OFF_DUTY,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'tracking_session_id' => $session->id,
            'message' => 'Tracking dihentikan.',
            'data' => $session,
        ]);
    }

    public function location(LocationUpdateRequest $request)
    {
        $sales = $this->currentSales();

        $session = SalesTrackingSession::where('id', $request->validated('tracking_session_id'))
            ->where('sales_id', $sales->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tracking tidak valid atau sudah tidak aktif.',
            ], 422);
        }

        // Idempotency (Blueprint #25): retry dari Offline Queue tidak boleh
        // membuat duplikasi baris history.
        $existing = SalesLocationHistory::where('location_event_id', $request->validated('location_event_id'))->first();

        if (! $existing) {
            // Waktu dari perangkat membawa offset sendiri (Android: +07:00,
            // browser: Z/UTC). Dikonversi ke zona waktu aplikasi sebelum disimpan
            // supaya recorded_at selalu sebanding dengan received_at/created_at.
            $recordedAt = Carbon::parse($request->validated('recorded_at'))->setTimezone(config('app.timezone'));

            $device = $this->deviceState($request);

            DB::transaction(function () use ($request, $sales, $session, $recordedAt, $device) {
                SalesLocationHistory::create([
                    'sales_id' => $sales->id,
                    'location_event_id' => $request->validated('location_event_id'),
                    'branch_id' => $session->branch_id,
                    'tracking_session_id' => $session->id,
                    'latitude' => $request->validated('latitude'),
                    'longitude' => $request->validated('longitude'),
                    'accuracy' => $request->validated('accuracy'),
                    'recorded_at' => $recordedAt,
                    'received_at' => now(),
                ] + array_intersect_key($device, array_flip(['battery_level', 'gps_enabled', 'network_type'])));

                // Heuristik status sederhana (Blueprint #27): AT_CUSTOMER bila
                // sedang ada kunjungan aktif, selain itu ACTIVE. Threshold
                // IDLE/SIGNAL_LOST yang butuh perbandingan waktu/jarak dapat
                // disempurnakan pada Fase 10 - Optimization.
                $atCustomer = Visit::where('sales_id', $sales->id)
                    ->where('status', Visit::STATUS_ONGOING)
                    ->exists();

                // Istirahat menang atas status lain: Sales istirahat tidak dianggap diam.
                $onBreak = SalesBreak::open()->where('sales_id', $sales->id)->exists();

                SalesCurrentLocation::updateOrCreate(
                    ['sales_id' => $sales->id],
                    [
                        'branch_id' => $session->branch_id,
                        'tracking_session_id' => $session->id,
                        'latitude' => $request->validated('latitude'),
                        'longitude' => $request->validated('longitude'),
                        'accuracy' => $request->validated('accuracy'),
                        'last_seen_at' => now(),
                        'status' => $onBreak
                            ? SalesCurrentLocation::STATUS_ON_BREAK
                            : ($atCustomer ? SalesCurrentLocation::STATUS_AT_CUSTOMER : SalesCurrentLocation::STATUS_ACTIVE),
                    ] + ($device !== [] ? $device + ['device_seen_at' => now()] : [])
                );
            });
        }

        // Response contract persis sesuai Blueprint #38.
        return response()->json([
            'success' => true,
            'server_received_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Heartbeat status HP: baterai, GPS, izin lokasi, jaringan, dan (opsional)
     * satu kejadian (mis. gps_off). TIDAK membawa koordinat dan TIDAK mengubah
     * last_seen_at / status lokasi -- supaya "Sinyal Hilang" tetap dihitung dari
     * titik GPS terakhir, sementara Admin bisa melihat alasan di balik hilangnya
     * (GPS dimatikan, izin dicabut, baterai habis, dsb).
     */
    public function heartbeat(Request $request)
    {
        $sales = $this->currentSales();

        $device = $this->deviceState($request);
        $eventName = $request->input('event');
        $eventName = is_string($eventName) && array_key_exists($eventName, SalesDeviceEvent::LABELS) ? $eventName : null;

        $occurredAt = now();
        if ($request->filled('occurred_at')) {
            try {
                $parsed = Carbon::parse($request->input('occurred_at'))->setTimezone(config('app.timezone'));
                // Antrean offline boleh membawa waktu lampau; waktu masa depan diabaikan.
                $occurredAt = $parsed->greaterThan(now()) ? now() : $parsed;
            } catch (\Throwable) {
                // waktu tidak terbaca -> pakai waktu server
            }
        }

        $session = SalesTrackingSession::where('sales_id', $sales->id)
            ->where('status', SalesTrackingSession::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        if ($device !== []) {
            // Status terbaru menang: heartbeat lama dari antrean tidak menimpa yang lebih baru.
            SalesCurrentLocation::where('sales_id', $sales->id)
                ->where(fn ($q) => $q->whereNull('device_seen_at')->orWhere('device_seen_at', '<=', $occurredAt))
                ->update($device + ['device_seen_at' => $occurredAt]);
        }

        if ($eventName) {
            SalesDeviceEvent::create([
                'sales_id' => $sales->id,
                'branch_id' => $session?->branch_id ?? $sales->branch_id,
                'tracking_session_id' => $session?->id,
                'event' => $eventName,
                'battery_level' => $device['battery_level'] ?? null,
                'detail' => mb_substr((string) $request->input('detail', ''), 0, 255) ?: null,
                'occurred_at' => $occurredAt,
                'received_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'server_received_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Ambil status HP dari request. Semua field opsional dan TIDAK PERNAH
     * menggagalkan request: nilai yang tidak valid dibuang diam-diam, supaya
     * titik lokasi dari aplikasi lama/aneh tetap tersimpan.
     *
     * @return array<string, mixed>
     */
    private function deviceState(Request $request): array
    {
        $rules = [
            'battery_level' => ['integer', 'between:0,100'],
            'is_charging' => ['boolean'],
            'gps_enabled' => ['boolean'],
            'location_permission' => ['in:always,foreground,denied'],
            'network_type' => ['in:wifi,mobile,none'],
        ];

        $out = [];
        foreach ($rules as $key => $rule) {
            if (! $request->has($key) || $request->input($key) === null) {
                continue;
            }
            if (Validator::make([$key => $request->input($key)], [$key => $rule])->passes()) {
                $value = $request->input($key);
                $out[$key] = in_array($key, ['is_charging', 'gps_enabled'], true) ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : (is_numeric($value) ? (int) $value : $value);
            }
        }

        return $out;
    }
}
