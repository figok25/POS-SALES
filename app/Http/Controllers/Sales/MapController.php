<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\Customer;
use App\Services\SalesRouteMapService;
use Illuminate\Http\Request;

/**
 * Live Sales Field Operations - Customer Map & Customer Detail
 * (Blueprint #11 Customer Location, #12 Customer Assignment, #64/#65
 * MapLibre + OpenStreetMap, #16 Basic Route, Fase 3 WebView Sales).
 *
 * Hanya menampilkan Customer yang di-assign ke Sales yang sedang login
 * (Blueprint #12: "Jangan menampilkan ribuan customer tanpa filter").
 *
 * Route Toko Sesuai Hari/Minggu: peta di-filter mengikuti "Rute Kanvas"
 * (SalesVisitPlan) milik hari yang dipilih -- default hari ini. Sales
 * bisa lihat hari lain dalam minggu berjalan lewat query ?day=1..7.
 * Logic filter-nya ada di SalesRouteMapService, dipakai bersama dengan
 * versi Admin (Admin\Sales\MapController) supaya kedua sisi konsisten.
 *
 * Indikator Visual Status Kunjungan pada Peta: setiap Customer ditandai
 * "sudah" atau "belum" dikunjungi (berdasarkan Visit::check_in_at pada
 * tanggal tab yang dipilih) -- read-only, dari SalesRouteMapService,
 * TIDAK menyentuh RouteController/TrackingController (mobile native).
 */
class MapController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected SalesRouteMapService $routeMapService) {}

    public function index(Request $request)
    {
        $sales = $this->currentSales();

        $selectedDay = $this->routeMapService->resolveSelectedDay(
            $request->has('day') ? (int) $request->query('day') : null
        );

        [$customers, $usingFallback] = $this->routeMapService->customersForDay($sales->id, $selectedDay);

        $weekDays = $this->routeMapService->weekDayOptions($selectedDay);

        $selectedDate = collect($weekDays)->firstWhere('isSelected', true)['date'];
        $visitedCustomerIds = $this->routeMapService->visitedCustomerIds($sales->id, $selectedDate);

        return view('sales.map.index', [
            'customers' => $customers,
            'customersWithLocation' => $customers->filter->hasLocation()->values(),
            'mapStyleUrl' => config('services.maps.style_url'),
            'weekDays' => $weekDays,
            'selectedDay' => $selectedDay,
            'usingFallback' => $usingFallback,
            'visitedCustomerIds' => $visitedCustomerIds,
        ]);
    }

    public function show(Customer $customer)
    {
        $sales = $this->currentSales();

        abort_if((int) $customer->sales_id !== (int) $sales->id, 403);

        return view('sales.map.show', [
            'customer' => $customer,
            'mapStyleUrl' => config('services.maps.style_url'),
        ]);
    }
}
