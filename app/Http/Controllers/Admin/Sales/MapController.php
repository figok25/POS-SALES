<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Services\SalesRouteMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Route Toko Sesuai Hari/Minggu - versi Admin: Admin pilih Sales +
 * hari, lalu lihat toko yang masuk Rute Kanvas Sales tsb pada hari
 * itu (peta + daftar). Read-only monitoring, bukan editor -- untuk
 * mengubah isi Rute Kanvas tetap lewat Visit Plan
 * (VisitPlanController, customer-assignment.manage).
 *
 * Filter Peta Rute Berdasarkan Tanggal Task: menerima ?date=YYYY-MM-DD
 * (dipakai link "Lihat di Peta Rute" dari Sales Task -- lihat
 * SalesTaskController::show) selain ?day=1..7 (browsing manual minggu
 * berjalan). Kalau ?date= dipakai, tab hari yang tampil mengikuti
 * MINGGU ASLI tanggal itu (bisa beda dari minggu berjalan), bukan
 * selalu "minggu ini".
 *
 * Penyaringan Daftar Sales: dropdown Sales hanya menampilkan Sales
 * yang punya Rute Kanvas terjadwal pada hari terpilih -- supaya daftar
 * yang muncul relevan dengan jadwal harian, bukan seluruh Sales aktif
 * tanpa pandang jadwal.
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
        $anchorDate = $this->parseAnchorDate($request->query('date'));

        $selectedDay = $anchorDate
            ? $anchorDate->dayOfWeekIso
            : $this->routeMapService->resolveSelectedDay(
                $request->has('day') ? (int) $request->query('day') : null
            );

        $activeSales = Sales::where('is_active', true)->orderBy('name')->get();

        // Penyaringan Daftar Sales: hanya Sales yang benar-benar punya
        // Rute Kanvas hari ini yang tampil di dropdown.
        $salesIdsWithRoute = $this->routeMapService->salesIdsWithRouteOnDay($selectedDay);
        $salesList = $activeSales->whereIn('id', $salesIdsWithRoute)->values();

        $selectedSalesId = $request->query('sales_id')
            ? (int) $request->query('sales_id')
            : optional($salesList->first())->id;

        // Kalau sales_id datang dari tautan luar (mis. Sales Task) dan
        // kebetulan tidak/tidak-lagi punya Rute Kanvas hari itu, tetap
        // resolve dari daftar Sales aktif penuh -- supaya link "Lihat di
        // Peta Rute" tidak pernah berujung halaman kosong tanpa penjelasan,
        // dan tetap disisipkan ke dropdown supaya terlihat & bisa dipilih.
        $selectedSales = $salesList->firstWhere('id', $selectedSalesId)
            ?? $activeSales->firstWhere('id', $selectedSalesId);

        if ($selectedSales && ! $salesList->contains('id', $selectedSales->id)) {
            $salesList->push($selectedSales);
        }

        $customers = collect();
        $usingFallback = false;
        $visitedCustomerIds = collect();

        if ($selectedSales) {
            [$customers, $usingFallback] = $this->routeMapService->customersForDay($selectedSales->id, $selectedDay);
        }

        $weekDays = $this->routeMapService->weekDayOptions($selectedDay, $anchorDate);

        if ($selectedSales) {
            $selectedDate = collect($weekDays)->firstWhere('isSelected', true)['date'];
            $visitedCustomerIds = $this->routeMapService->visitedCustomerIds($selectedSales->id, $selectedDate);
        }

        return view('admin.sales.route-map.index', [
            'salesList' => $salesList,
            'selectedSales' => $selectedSales,
            'customers' => $customers,
            'customersWithLocation' => $customers->filter->hasLocation()->values(),
            'mapStyleUrl' => config('services.maps.style_url'),
            'weekDays' => $weekDays,
            'selectedDay' => $selectedDay,
            'anchorDate' => $anchorDate,
            'usingFallback' => $usingFallback,
            'visitedCustomerIds' => $visitedCustomerIds,
        ]);
    }

    private function parseAnchorDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }
}
