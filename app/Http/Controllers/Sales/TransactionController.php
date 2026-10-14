<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\SalesTransactionRequest;
use App\Models\BuyGetPromo;
use App\Models\Price;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\EffectiveCallService;
use App\Services\SalesRouteMapService;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use App\Services\VisitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $sales = $this->currentSales();

        // Riwayat difilter per hari (default hari ini); ?date=all = semua tanggal.
        $filterDate = $this->resolveFilterDate($request->query('date'));

        $base = SalesTransaction::where('sales_id', $sales->id)
            ->when($filterDate, fn ($q) => $q->whereDate('created_at', $filterDate->toDateString()));

        $summary = [
            'count' => (clone $base)->count(),
            'total' => (float) (clone $base)->where('status', SalesTransaction::STATUS_COMPLETED)->sum('total'),
        ];

        $items = (clone $base)
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('sales.transactions.index', compact('items', 'filterDate', 'summary'));
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

        // Program Beli X Gratis Y yang sedang berlaku (untuk pratinjau di form).
        $promos = BuyGetPromo::query()
            ->buyGet()
            ->runningOn()
            ->forBranch($sales->branch_id ? (int) $sales->branch_id : null)
            ->with(['buyProduct:id,name', 'freeProduct:id,name'])
            ->get()
            ->map(fn (BuyGetPromo $p) => [
                'buy_id' => $p->buy_product_id,
                'buy_name' => $p->buyProduct->name ?? '-',
                'buy_qty' => (float) $p->buy_qty,
                'free_id' => $p->free_product_id,
                'free_name' => $p->freeProduct->name ?? '-',
                'free_qty' => (float) $p->free_qty,
            ])
            ->values();

        // Program Tukar Cangkang yang aktif di Depo Sales ini.
        $shellPromos = BuyGetPromo::query()
            ->shellExchange()
            ->runningOn()
            ->forBranch($sales->branch_id ? (int) $sales->branch_id : null)
            ->with(['buyProduct:id,name', 'freeProduct:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn (BuyGetPromo $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'shell_name' => $p->buyProduct->name ?? '-',
                'rate' => (float) $p->buy_qty,
                'free_id' => $p->free_product_id,
                'free_name' => $p->freeProduct->name ?? '-',
                'free_qty' => (float) $p->free_qty,
            ])
            ->values();

        return view('sales.transactions.create', compact('visit', 'myStock', 'prices', 'sales', 'promos', 'shellPromos'));
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

    /**
     * Laporan penjualan harian Sales (detail per transaksi, rekap produk,
     * pembayaran yang diterima) -- bisa dicetak ke printer thermal.
     * Default hari ini; ?date=YYYY-MM-DD untuk mencetak ulang hari lain.
     */
    public function dailyReport(Request $request)
    {
        $sales = $this->currentSales();

        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $request->query('date', now()->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $date = now()->startOfDay();
        }

        if ($date->isFuture()) {
            $date = now()->startOfDay();
        }

        $day = $date->toDateString();

        $transactions = SalesTransaction::query()
            ->where('sales_id', $sales->id)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereDate('created_at', $day)
            ->with('items.product')
            ->get();

        // Detail penjualan per produk (barang gratis program dipisah, tidak dihitung sebagai penjualan).
        $products = [];
        $totalSales = 0.0;
        $totalFreeQty = 0.0;
        $totalShells = 0.0;
        $transactedCustomers = [];

        foreach ($transactions as $trx) {
            $totalSales += (float) $trx->total;
            $transactedCustomers[$trx->customer_id] = true;

            foreach ($trx->items as $line) {
                $products[$line->product_id] ??= ['name' => $line->product->name ?? '-', 'sold' => 0.0, 'free' => 0.0, 'amount' => 0.0];
                if ($line->is_free) {
                    $products[$line->product_id]['free'] += (float) $line->quantity;
                    $totalFreeQty += (float) $line->quantity;
                    $totalShells += (float) $line->shell_qty;
                } else {
                    $products[$line->product_id]['sold'] += (float) $line->quantity;
                    $products[$line->product_id]['amount'] += (float) $line->subtotal;
                }
            }
        }

        // Kunjungan & EC (definisi sama dengan dashboard): 1 toko dikunjungi = 1 call;
        // EC = toko yang dikunjungi (bukan Toko Tutup) DAN ada transaksi selesai hari itu.
        $visits = Visit::query()
            ->where('sales_id', $sales->id)
            ->whereDate('check_in_at', $day)
            ->get(['customer_id', 'check_in_condition'])
            ->groupBy('customer_id');

        $openCustomers = $visits
            ->filter(fn ($rows) => $rows->contains(fn ($v) => $v->check_in_condition !== Visit::CONDITION_CLOSED))
            ->keys();
        $visitCount = config('kpi.call_made_includes_closed') ? $visits->count() : $openCustomers->count();

        $ecKeys = $openCustomers
            ->filter(fn ($customerId) => isset($transactedCustomers[$customerId]))
            ->map(fn ($customerId) => $customerId.'|'.$day)
            ->values();
        $ecSplit = app(EffectiveCallService::class)->split($ecKeys);

        $report = [
            'company_name' => $sales->branch?->company?->name,
            'sales_name' => $sales->name,
            'date' => $date->format('d/m/Y'),
            'date_iso' => $day,
            'printed_at' => now()->format('d/m/Y H:i'),
            'summary' => [
                'transactions' => $transactions->count(),
                'total_sales' => round($totalSales, 2),
                'free_qty' => round($totalFreeQty, 2),
                'shell_qty' => round($totalShells, 2),
                'visits' => $visitCount,
                'ec' => $ecKeys->count(),
                'ec_new' => $ecSplit['new'],
                'ec_repeat' => $ecSplit['repeat'],
            ],
            'products' => collect($products)->sortByDesc('amount')->values()->all(),
        ];

        return view('sales.transactions.daily', compact('report', 'date'));
    }
}
