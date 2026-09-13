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

        $duplicates = $this->service->findPossibleDuplicates($data['name'], $data['phone'] ?? null);

        $tagging = $this->service->submit($sales->id, $data);

        $status = 'Tagging toko berhasil dikirim. Menunggu verifikasi Admin.';
        if ($duplicates->isNotEmpty()) {
            $status .= ' Catatan: ditemukan '.$duplicates->count().' customer dengan nama/telepon mirip, Admin akan memeriksa kemungkinan duplikasi.';
        }

        return redirect()->route('sales.tagging.index')->with('status', $status);
    }
}
