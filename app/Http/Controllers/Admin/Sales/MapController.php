<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Services\SalesRouteMapService;
use Illuminate\Http\Request;

/**
 * Route Toko Sesuai Hari/Minggu - versi Admin: Admin pilih Sales +
 * hari, lalu lihat toko yang masuk Rute Kanvas Sales tsb pada hari
 * itu (peta + daftar). Read-only monitoring, bukan editor -- untuk
 * mengubah isi Rute Kanvas tetap lewat Visit Plan
 * (VisitPlanController, customer-assignment.manage).
 *
 * Memakai SalesRouteMapService yang sama dengan Sales\MapController
 * (WebView Sales) supaya definisi "Rute Kanvas hari ini" konsisten di
 * kedua sisi. TIDAK menyentuh RouteController/TrackingController
 * (mobile native) maupun LiveSalesController (live GPS monitoring) --
 * murni baca dari SalesVisitPlan & Customer.
 */
class MapController extends Controller
{
    public function __construct(protected SalesRouteMapService $routeMapService) {}

    public function index(Request $request)
    {
        $salesList = Sales::where('is_active', true)->orderBy('name')->get();

        $selectedSalesId = $request->query('sales_id')
            ? (int) $request->query('sales_id')
            : optional($salesList->first())->id;

        $selectedSales = $salesList->firstWhere('id', $selectedSalesId);

        $selectedDay = $this->routeMapService->resolveSelectedDay(
            $request->has('day') ? (int) $request->query('day') : null
        );

        $customers = collect();
        $usingFallback = false;

        if ($selectedSales) {
            [$customers, $usingFallback] = $this->routeMapService->customersForDay($selectedSales->id, $selectedDay);
        }

        $weekDays = $this->routeMapService->weekDayOptions($selectedDay);

        return view('admin.sales.route-map.index', [
            'salesList' => $salesList,
            'selectedSales' => $selectedSales,
            'customers' => $customers,
            'customersWithLocation' => $customers->filter->hasLocation()->values(),
            'mapStyleUrl' => config('services.maps.style_url'),
            'weekDays' => $weekDays,
            'selectedDay' => $selectedDay,
            'usingFallback' => $usingFallback,
        ]);
    }
}
