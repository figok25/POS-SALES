<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerAssignment;
use App\Models\SalesRoute;
use App\Services\Routing\RoutingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Section 94: Final End-to-End Routing Flow.
 */
class RouteController extends Controller
{
    public function __construct(private readonly RoutingService $routingService)
    {
    }

    /**
     * GET /api/sales/routes/today
     * Ambil assignment hari ini (urut sequence), lalu minta RoutingService
     * (cache-first, TomTom hanya jika perlu).
     */
    public function today(Request $request)
    {
        $user = $request->user();

        $assignments = CustomerAssignment::with('customer')
            ->where('user_id', $user->id)
            ->whereDate('assigned_date', now()->toDateString())
            ->orderBy('sequence')
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json([
                'success' => true,
                'source' => null,
                'route_id' => null,
                'distance_meters' => null,
                'duration_seconds' => null,
                'geometry' => [],
                'stops' => [],
                'message' => 'Belum ada assignment customer untuk hari ini.',
            ]);
        }

        $orderedStops = $assignments->map(fn ($a) => [
            'customer_id' => $a->customer_id,
            'latitude' => (float) $a->customer->latitude,
            'longitude' => (float) $a->customer->longitude,
        ])->values()->all();

        $route = $this->routingService->calculateDailyRoute($user, now(), $orderedStops);

        return response()->json($this->formatRoute($route));
    }

    /**
     * POST /api/sales/routes/reroute
     * Section 94 "Need reroute?": dipanggil Android saat GPS deviation detector
     * mendeteksi Sales menyimpang jauh dari rute. Selalu mulai dari posisi GPS
     * SAAT INI + stop yang masih pending. Diproteksi cooldown di RoutingService
     * -- endpoint ini AMAN dipanggil berkali-kali, tidak akan membanjiri TomTom.
     */
    public function reroute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();

        $pendingAssignments = CustomerAssignment::with('customer')
            ->where('user_id', $user->id)
            ->whereDate('assigned_date', now()->toDateString())
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->get();

        $remainingStops = $pendingAssignments->map(fn ($a) => [
            'customer_id' => $a->customer_id,
            'latitude' => (float) $a->customer->latitude,
            'longitude' => (float) $a->customer->longitude,
        ])->values()->all();

        $route = $this->routingService->reroute(
            $user,
            now(),
            [
                'latitude' => (float) $request->input('latitude'),
                'longitude' => (float) $request->input('longitude'),
            ],
            $remainingStops
        );

        return response()->json($this->formatRoute($route));
    }

    private function formatRoute(SalesRoute $route): array
    {
        // Section 90-an (MapLibre Android): geometry disimpan sebagai raw TomTom legs
        // (array of leg -> points[]). Diratakan di sini jadi 1 polyline sederhana
        // supaya Android tinggal gambar garis tanpa perlu tahu struktur TomTom.
        $polyline = collect($route->geometry ?? [])
            ->flatMap(fn ($leg) => collect($leg['points'] ?? [])
                ->map(fn ($p) => ['lat' => $p['latitude'], 'lng' => $p['longitude']]))
            ->values()
            ->all();

        return [
            'success' => true,
            'source' => $route->source,
            'route_id' => $route->id,
            'distance_meters' => $route->distance_meters,
            'duration_seconds' => $route->duration_seconds,
            'geometry' => $polyline,
            'stops' => $route->stops->map(fn ($s) => [
                'customer_id' => $s->customer_id,
                'name' => $s->customer->name,
                'latitude' => (float) $s->customer->latitude,
                'longitude' => (float) $s->customer->longitude,
                'sequence' => $s->sequence,
                'status' => $s->status,
            ]),
        ];
    }
}
