<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\VisitCheckInRequest;
use App\Http\Requests\Sales\VisitCheckOutRequest;
use App\Models\PromoItem;
use App\Models\Visit;
use App\Services\SalesRouteMapService;
use App\Services\VisitService;
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

    public function index()
    {
        $sales = $this->currentSales();

        $ongoing = Visit::where('sales_id', $sales->id)->where('status', Visit::STATUS_ONGOING)->with('customer')->first();

        // Alasan check-out hanya diwajibkan bila outlet normal tapi belum ada transaksi.
        $checkOutReasonRequired = $ongoing?->needsCheckOutReason() ?? false;

        $items = Visit::where('sales_id', $sales->id)
            ->where('status', Visit::STATUS_COMPLETED)
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.visits.index', compact('ongoing', 'items', 'checkOutReasonRequired'));
    }

    /**
     * PERBAIKAN: sebelumnya menampilkan SEMUA Customer yang di-assign ke
     * Sales (+ yang belum di-assign ke siapapun), tanpa peduli hari.
     * Akibatnya Sales bisa check-in ke toko jadwal hari lain di hari yang
     * salah. Sekarang dropdown Check-in dibatasi ke Rute Kanvas HARI INI
     * saja, memakai service yang sama dengan Peta Customer (SalesRouteMapService)
     * supaya kedua fitur selalu konsisten -- termasuk toko hasil Tagging
     * yang baru di-approve untuk hari ini (lihat CustomerTaggingService::
     * autoAddToVisitPlan()).
     */
    public function create()
    {
        $sales = $this->currentSales();

        $today = now()->dayOfWeekIso;
        [$customers, $usingFallback] = $this->routeMapService->customersForDay($sales->id, $today);

        // Toko yang kuota kunjungannya hari ini sudah habis (sudah dikunjungi, dan
        // bukan Toko Tutup) tidak ditawarkan lagi di daftar.
        $exhausted = $this->service->quotaExhaustedCustomerIds($sales->id);
        $quotaBlockedCount = $customers->whereIn('id', $exhausted)->count();
        $customers = $customers->reject(fn ($c) => in_array((int) $c->id, $exhausted, true))->values();

        // Item Promosi/POSM aktif (dikelola Admin) yang dicentang Sales saat check-in.
        $promoItems = PromoItem::active()->ordered()->get();

        return view('sales.visits.create', compact('customers', 'usingFallback', 'promoItems', 'quotaBlockedCount'));
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
