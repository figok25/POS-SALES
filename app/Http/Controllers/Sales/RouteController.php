<?php

namespace App\Http\Controllers\Sales;

use App\Contracts\RoutingEngine;
use App\Exceptions\RoutingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * Live Sales Field Operations - Basic Route (Blueprint #16, #67).
 *
 * Perhitungan rute dilakukan di Laravel (server-side), BUKAN di
 * JavaScript client, supaya:
 * 1. ROUTING_API_KEY tidak pernah terekspos ke browser;
 * 2. Provider routing bisa diganti (OpenRouteService -> OSRM) tanpa
 *    menyentuh kode frontend sama sekali (Blueprint #83).
 *
 * Arsitektur mengikuti Blueprint #64: Android/Browser GPS -> Laravel /
 * Routing Service -> Route Geometry -> MapLibre.
 */
class RouteController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected RoutingEngine $routingEngine) {}

    public function calculate(Request $request, Customer $customer)
    {
        $sales = $this->currentSales();

        abort_if((int) $customer->sales_id !== (int) $sales->id, 403);

        if (! $customer->hasLocation()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ini belum memiliki titik lokasi.',
            ], 422);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $route = $this->routingEngine->calculateRoute(
                $data['latitude'],
                $data['longitude'],
                (float) $customer->latitude,
                (float) $customer->longitude,
            );
        } catch (RoutingUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 503);
        }

        return response()->json(['success' => true, 'data' => $route->toArray()]);
    }
}
