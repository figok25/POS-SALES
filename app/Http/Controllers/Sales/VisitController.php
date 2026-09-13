<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\VisitCheckInRequest;
use App\Http\Requests\Sales\VisitCheckOutRequest;
use App\Models\Customer;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6 - Sales App: Kunjungan / Visit (Blueprint #12.4).
 */
class VisitController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected VisitService $service)
    {
    }

    public function index()
    {
        $sales = $this->currentSales();

        $ongoing = Visit::where('sales_id', $sales->id)->where('status', Visit::STATUS_ONGOING)->with('customer')->first();

        $items = Visit::where('sales_id', $sales->id)
            ->where('status', Visit::STATUS_COMPLETED)
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.visits.index', compact('ongoing', 'items'));
    }

    public function create()
    {
        $sales = $this->currentSales();

        $customers = Customer::where('is_active', true)
            ->where(function ($q) use ($sales) {
                $q->whereNull('sales_id')->orWhere('sales_id', $sales->id);
            })
            ->orderBy('name')
            ->get();

        return view('sales.visits.create', compact('customers'));
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
