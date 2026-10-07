<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\SalesTransactionRequest;
use App\Models\Price;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\SalesRouteMapService;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use App\Services\VisitService;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6 - Sales App: Transaksi Penjualan (Blueprint #12.5).
 */
class TransactionController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(
        protected SalesTransactionService $service,
        protected StockService $stockService,
        protected SalesRouteMapService $routeMapService,
        protected VisitService $visitService,
    ) {
    }

    public function index()
    {
        $sales = $this->currentSales();

        $items = SalesTransaction::where('sales_id', $sales->id)
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('sales.transactions.index', compact('items'));
    }

    /**
     * Transaksi hanya bisa dibuat saat Sales SEDANG berkunjung (sudah Check-in)
     * ke toko tersebut -- jadi form ini tidak lagi menawarkan daftar toko,
     * melainkan toko dari kunjungan yang sedang berjalan ($visit). Kalau belum
     * Check-in (atau toko tercatat tutup), form menampilkan petunjuknya.
     */
    public function create()
    {
        $sales = $this->currentSales();

        $visit = Visit::query()
            ->where('sales_id', $sales->id)
            ->where('status', Visit::STATUS_ONGOING)
            ->with('customer')
            ->first();

        // Hanya tampilkan produk yang tersedia di Sales Stock milik Sales
        // ini, agar pemilihan produk di form transaksi realistis
        // (Blueprint #12.6 - "Sales dapat melihat stock miliknya sendiri").
        $myStock = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->get()
            ->filter(fn ($stock) => $stock->product !== null);

        // Harga mengikuti jenis Sales (Retail / WS-Grosir). Kalau ada lebih
        // dari satu harga aktif untuk produk yang sama, yang terbaru dipakai
        // (sama dengan SalesTransactionService).
        $prices = Price::where('is_active', true)
            ->where('price_type', $sales->priceType())
            ->orderBy('id')
            ->pluck('amount', 'product_id');

        return view('sales.transactions.create', compact('visit', 'myStock', 'prices', 'sales'));
    }

    public function store(SalesTransactionRequest $request)
    {
        $sales = $this->currentSales();

        try {
            // Wajib sedang berkunjung (Check-in) di toko yang sama sebelum transaksi.
            $this->visitService->assertCanTransact($sales->id, (int) $request->validated('customer_id'));

            $trx = $this->service->create($sales->id, auth()->id(), $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('sales.transactions.show', $trx)
            ->with('status', "Transaksi {$trx->code} berhasil disimpan. Invoice {$trx->invoice->code} diterbitkan.");
    }

    public function show(SalesTransaction $transaction)
    {
        $sales = $this->currentSales();

        if ((int) $transaction->sales_id !== (int) $sales->id) {
            abort(403);
        }

        $transaction->load('items.product', 'customer', 'invoice', 'sales.branch.company');

        return view('sales.transactions.show', compact('transaction'));
    }
}
