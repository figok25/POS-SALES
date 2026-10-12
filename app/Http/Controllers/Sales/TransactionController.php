<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\SalesTransactionRequest;
use App\Models\BuyGetPromo;
use App\Models\Payment;
use App\Models\Price;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
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

        // Program Beli X Gratis Y yang sedang berlaku (untuk pratinjau di form).
        $promos = BuyGetPromo::query()
            ->runningOn()
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

        return view('sales.transactions.create', compact('visit', 'myStock', 'prices', 'sales', 'promos'));
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

        $transactions = SalesTransaction::query()
            ->where('sales_id', $sales->id)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereDate('created_at', $date->toDateString())
            ->with(['items.product', 'customer', 'invoice'])
            ->orderBy('created_at')
            ->get();

        $methodLabels = [
            Payment::METHOD_CASH => 'Tunai',
            Payment::METHOD_TRANSFER => 'Transfer',
            Payment::METHOD_OTHER => 'Lainnya',
        ];

        $trxRows = [];
        $products = [];
        $customerIds = [];
        $totalSales = 0.0;
        $totalFreeQty = 0.0;
        $outstanding = 0.0;

        foreach ($transactions as $trx) {
            $customerIds[$trx->customer_id] = true;
            $totalSales += (float) $trx->total;

            $invoice = $trx->invoice;
            $statusLabel = $invoice ? match ($invoice->status) {
                'paid' => 'Lunas',
                'partial' => 'Sebagian',
                default => 'Belum Bayar',
            } : '-';
            $left = $invoice && ! $invoice->isFullyPaid() ? $invoice->outstanding() : 0.0;
            $outstanding += $left;

            $items = [];
            foreach ($trx->items as $line) {
                $name = ($line->product->name ?? '-').($line->is_free ? ' (GRATIS)' : '');
                $items[] = [
                    'name' => $name,
                    'quantity' => (float) $line->quantity,
                    'price' => (float) $line->price,
                    'subtotal' => (float) $line->subtotal,
                    'is_free' => (bool) $line->is_free,
                ];

                $key = $line->product_id;
                $products[$key] ??= ['name' => $line->product->name ?? '-', 'sold' => 0.0, 'free' => 0.0, 'amount' => 0.0];
                if ($line->is_free) {
                    $products[$key]['free'] += (float) $line->quantity;
                    $totalFreeQty += (float) $line->quantity;
                } else {
                    $products[$key]['sold'] += (float) $line->quantity;
                    $products[$key]['amount'] += (float) $line->subtotal;
                }
            }

            $trxRows[] = [
                'code' => $trx->code,
                'time' => $trx->created_at->format('H:i'),
                'customer' => $trx->customer->name ?? '-',
                'items' => $items,
                'total' => (float) $trx->total,
                'status' => $statusLabel,
                'outstanding' => $left,
            ];
        }

        // Pembayaran yang DITERIMA Sales ini pada tanggal tsb (termasuk penagihan invoice hari lain).
        $payments = Payment::query()
            ->where('received_by', auth()->id())
            ->whereDate('paid_at', $date->toDateString())
            ->with('invoice.customer')
            ->orderBy('id')
            ->get();

        $byMethod = [];
        $paymentRows = [];
        foreach ($payments as $pay) {
            $label = $methodLabels[$pay->method] ?? ucfirst((string) $pay->method);
            $byMethod[$label] = ($byMethod[$label] ?? 0) + (float) $pay->amount;
            $paymentRows[] = [
                'invoice' => $pay->invoice->code ?? '-',
                'customer' => $pay->invoice?->customer?->name ?? '-',
                'method' => $label,
                'amount' => (float) $pay->amount,
            ];
        }

        $report = [
            'company_name' => $sales->branch?->company?->name,
            'sales_name' => $sales->name,
            'date' => $date->format('d/m/Y'),
            'date_iso' => $date->toDateString(),
            'printed_at' => now()->format('d/m/Y H:i'),
            'summary' => [
                'transactions' => count($trxRows),
                'customers' => count($customerIds),
                'total_sales' => round($totalSales, 2),
                'free_qty' => round($totalFreeQty, 2),
                'received' => round(array_sum($byMethod), 2),
                'outstanding' => round($outstanding, 2),
            ],
            'transactions' => $trxRows,
            'products' => array_values($products),
            'payments' => $paymentRows,
            'payments_by_method' => collect($byMethod)->map(fn ($amount, $label) => ['method' => $label, 'amount' => $amount])->values()->all(),
        ];

        return view('sales.transactions.daily', compact('report', 'date'));
    }
}
