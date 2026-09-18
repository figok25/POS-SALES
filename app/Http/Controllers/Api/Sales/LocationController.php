<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\LocationPing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Section 9: penerima batch dari LocationSyncWorker (Android local queue).
 * Section 93: endpoint ini TIDAK PERNAH memicu TomTom -- GPS hanya jadi input
 * routing ketika route baru memang diperlukan (lihat RouteController).
 */
class LocationController extends Controller
{
    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|array|min:1',
            'points.*.latitude' => 'required|numeric|between:-90,90',
            'points.*.longitude' => 'required|numeric|between:-180,180',
            'points.*.accuracy_meters' => 'nullable|numeric',
            'points.*.speed_mps' => 'nullable|numeric',
            'points.*.bearing' => 'nullable|numeric',
            'points.*.captured_at' => 'required|integer', // epoch millis
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();
        $points = $request->input('points');

        DB::transaction(function () use ($user, $points) {
            $rows = [];
            $now = now();
            foreach ($points as $p) {
                $rows[] = [
                    'user_id' => $user->id,
                    'latitude' => $p['latitude'],
                    'longitude' => $p['longitude'],
                    'accuracy_meters' => $p['accuracy_meters'] ?? null,
                    'speed_mps' => $p['speed_mps'] ?? null,
                    'bearing' => $p['bearing'] ?? null,
                    'captured_at' => date('Y-m-d H:i:s', intdiv($p['captured_at'], 1000)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            LocationPing::insert($rows);

            // Update snapshot posisi terkini (dipakai Admin Live Monitoring Map)
            $latest = collect($points)->sortByDesc('captured_at')->first();
            $user->forceFill([
                'last_latitude' => $latest['latitude'],
                'last_longitude' => $latest['longitude'],
                'last_location_at' => date('Y-m-d H:i:s', intdiv($latest['captured_at'], 1000)),
                'tracking_status' => 'ACTIVE',
            ])->save();
        });

        return response()->json(['success' => true, 'message' => 'Location batch saved']);
    }

    /**
     * Section 8: dipanggil Android saat status tracking berubah
     * (ACTIVE/PAUSED/STOPPED/PERMISSION_LOST) supaya Admin melihat status yang sesuai.
     */
    public function updateTrackingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ACTIVE,PAUSED,STOPPED,PERMISSION_LOST,STARTING,STOPPING',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $request->user()->forceFill([
            'tracking_status' => $request->input('status'),
            'is_tracking_active' => in_array($request->input('status'), ['ACTIVE', 'STARTING']),
        ])->save();

        return response()->json(['success' => true]);
    }
}
