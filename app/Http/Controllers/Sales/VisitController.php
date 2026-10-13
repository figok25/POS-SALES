<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\VisitCheckInRequest;
use App\Http\Requests\Sales\VisitCheckOutRequest;
use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\PromoItem;
use App\Models\SalesVisitPlan;
use App\Models\Visit;
use App\Services\SalesRouteMapService;
use App\Services\VisitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6 - Sales App: Kunjungan / Visit (Blueprint #12.4).
 */
class VisitController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected VisitService $service, protected SalesRouteMapService $routeMapService)
    {
    }

    public function index(Request $request)
    {
        $sales = $this->currentSales();

        $ongoing = Visit::where('sales_id', $sales->id)->where('status', Visit::STATUS_ONGOING)->with('customer')->first();

        // Alasan check-out hanya diwajibkan bila outlet normal tapi belum ada transaksi.
        $checkOutReasonRequired = $ongoing?->needsCheckOutReason() ?? false;

        // Riwayat difilter per hari (default hari ini); ?date=all = semua tanggal.
        $filterDate = $this->resolveFilterDate($request->query('date'));

        $items = Visit::where('sales_id', $sales->id)
            ->where('status', Visit::STATUS_COMPLETED)
            ->when($filterDate, fn ($q) => $q->whereDate('check_in_at', $filterDate->toDateString()))
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('sales.visits.index', compact('ongoing', 'items', 'checkOutReasonRequired', 'filterDate'));
    }

    /** null = semua tanggal; tanggal tidak valid/masa depan = hari ini. */
    private function resolveFilterDate(?string $value): ?Carbon
    {
        if ($value === 'all') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }

        return $date->isFuture() ? now()->startOfDay() : $date;
    }

    /**
     * Daftar toko di form Check-in: SEMUA toko aktif milik Sales ini, bukan hanya
     * Rute Kanvas hari ini (toko jadwal hari lain boleh dikunjungi lebih awal/akhir).
     * Yang dibatasi hanya kuotanya: 1 kunjungan per toko per minggu (lihat
     * VisitService::assertWeeklyQuota). Toko jadwal hari ini ditaruh paling atas;
     * toko lain diberi keterangan hari jadwalnya dan tetap bisa dicari lewat kolom cari.
     */
    public function create()
    {
        $sales = $this->currentSales();

        $assignedIds = CustomerAssignment::where('sales_id', $sales->id)
            ->whereNull('unassigned_at')
            ->pluck('customer_id');

        $all = Customer::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('sales_id', $sales->id)->orWhereIn('id', $assignedIds))
            ->orderBy('name')
            ->get();

        // Jadwal Rute Kanvas per toko: [customer_id => [hari ISO, ...]].
        $planDays = SalesVisitPlan::where('sales_id', $sales->id)->get(['customer_id', 'day_of_week'])
            ->groupBy('customer_id')
            ->map(fn ($rows) => $rows->pluck('day_of_week')->map(fn ($d) => (int) $d)->sort()->values()->all());

        $today = now()->dayOfWeekIso;
        [$todayRoute] = $this->routeMapService->customersForDay($sales->id, $today);
        $todayIds = $todayRoute->pluck('id')->all();

        // Toko yang kuota minggu ini sudah habis (sudah dikunjungi, dan bukan Toko Tutup)
        // tidak ditawarkan lagi di daftar.
        $exhausted = $this->service->quotaExhaustedCustomerIds($sales->id);
        $quotaBlockedCount = $all->whereIn('id', $exhausted)->count();
        $all = $all->reject(fn ($c) => in_array((int) $c->id, $exhausted, true));

        // Urutan: jadwal hari ini (sesuai urutan rute) dulu, lalu toko lain menurut nama.
        $customers = $all->filter(fn ($c) => in_array($c->id, $todayIds, true))
            ->sortBy(fn ($c) => array_search($c->id, $todayIds, true))
            ->concat($all->reject(fn ($c) => in_array($c->id, $todayIds, true)))
            ->values();

        $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $scheduleNote = fn ($c) => in_array($c->id, $todayIds, true)
            ? 'Jadwal hari ini'
            : (isset($planDays[$c->id]) && $planDays[$c->id]
                ? 'Jadwal '.collect($planDays[$c->id])->map(fn ($d) => $dayNames[$d])->implode('/')
                : 'Tanpa jadwal');

        $usingFallback = false;

        // Item Promosi/POSM aktif (dikelola Admin) yang dicentang Sales saat check-in.
        $promoItems = PromoItem::active()->ordered()->get();

        return view('sales.visits.create', compact('customers', 'usingFallback', 'promoItems', 'quotaBlockedCount', 'scheduleNote'));
    }

    public function store(VisitCheckInRequest $request)
    {
        $sales = $this->currentSales();

        try {
            $this->service->checkIn($sales->id, (int) $request->validated('customer_id'), $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('sales.visits.index')->with('status', 'Check-in berhasil. Selamat berkunjung!');
    }

    public function checkOut(VisitCheckOutRequest $request, Visit $visit)
    {
        $sales = $this->currentSales();

        if ((int) $visit->sales_id !== (int) $sales->id) {
            abort(403);
        }

        try {
            $this->service->checkOut($visit, $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('sales.visits.index')->with('status', 'Check-out berhasil. Kunjungan selesai dicatat.');
    }
}
