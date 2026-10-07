<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\CustomerTaggingRequest;
use App\Models\CustomerTagging;
use App\Services\CustomerTaggingService;

/**
 * Phase 6 - Sales App: Tagging Toko (Blueprint #12.3).
 */
class CustomerTaggingController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected CustomerTaggingService $service)
    {
    }

    public function index()
    {
        $sales = $this->currentSales();

        $items = CustomerTagging::where('sales_id', $sales->id)
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.tagging.index', compact('items'));
    }

    public function create()
    {
        return view('sales.tagging.create');
    }

    public function store(CustomerTaggingRequest $request)
    {
        $sales = $this->currentSales();
        $data = $request->validated();

        $tagging = $this->service->submit($sales->id, $data);

        // Tidak duplikat -> langsung jadi Customer & masuk Rute Kanvas hari ini.
        // Terindikasi duplikat -> ditahan sampai Admin memutuskan.
        $status = $tagging->status === CustomerTagging::STATUS_APPROVED
            ? "Tagging berhasil. Toko {$tagging->name} langsung masuk daftar customer dan Rute Kanvas Anda."
            : "Tagging terkirim, tetapi terindikasi duplikat ({$tagging->duplicate_reason}). Menunggu keputusan Admin.";

        return redirect()->route('sales.tagging.index')->with('status', $status);
    }
}
