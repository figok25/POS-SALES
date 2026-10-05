<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\LocationUpdateRequest;
use App\Http\Requests\Sales\TrackingStartRequest;
use App\Http\Requests\Sales\TrackingStopRequest;
use App\Models\SalesCurrentLocation;
use App\Models\SalesDevice;
use App\Models\SalesLocationHistory;
use App\Models\SalesTask;
use App\Models\SalesTrackingSession;
use App\Models\Visit;
use App\Services\TrackingSessionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Live Sales Field Operations - Tracking API (Blueprint #21, #22, #23,
 * #25, #38, Fase 2):
 *
 *   POST /api/sales/tracking/start
 *   POST /api/sales/tracking/stop
 *   POST /api/sales/location
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

            DB::transaction(function () use ($request, $sales, $session, $recordedAt) {
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
                ]);

                // Heuristik status sederhana (Blueprint #27): AT_CUSTOMER bila
                // sedang ada kunjungan aktif, selain itu ACTIVE. Threshold
                // IDLE/SIGNAL_LOST yang butuh perbandingan waktu/jarak dapat
                // disempurnakan pada Fase 10 - Optimization.
                $atCustomer = Visit::where('sales_id', $sales->id)
                    ->where('status', Visit::STATUS_ONGOING)
                    ->exists();

                SalesCurrentLocation::updateOrCreate(
                    ['sales_id' => $sales->id],
                    [
                        'branch_id' => $session->branch_id,
                        'tracking_session_id' => $session->id,
                        'latitude' => $request->validated('latitude'),
                        'longitude' => $request->validated('longitude'),
                        'accuracy' => $request->validated('accuracy'),
                        'last_seen_at' => now(),
                        'status' => $atCustomer ? SalesCurrentLocation::STATUS_AT_CUSTOMER : SalesCurrentLocation::STATUS_ACTIVE,
                    ]
                );
            });
        }

        // Response contract persis sesuai Blueprint #38.
        return response()->json([
            'success' => true,
            'server_received_at' => now()->toIso8601String(),
        ]);
    }
}
