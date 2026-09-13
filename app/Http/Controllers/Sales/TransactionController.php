<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\SalesTransactionRequest;
use App\Models\Customer;
use App\Models\Price;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6 - Sales App: Transaksi Penjualan (Blueprint #12.5).
 */
class TransactionController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected SalesTransactionService $service, protected StockService $stockService)
    {
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

    public function create()
    {
        $sales = $this->currentSales();

        $customers = Customer::where('is_active', true)
            ->where(function ($q) use ($sales) {
                $q->whereNull('sales_id')->orWhere('sales_id', $sales->id);
            })
            ->orderBy('name')
            ->get();

        // Hanya tampilkan produk yang tersedia di Sales Stock milik Sales
        // ini, agar pemilihan produk di form transaksi realistis
        // (Blueprint #12.6 - "Sales dapat melihat stock miliknya sendiri").
        $myStock = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->get()
            ->filter(fn ($stock) => $stock->product !== null);

        $prices = Price::where('is_active', true)->pluck('amount', 'product_id');

        return view('sales.transactions.create', compact('customers', 'myStock', 'prices'));
    }

    public function store(SalesTransactionRequest $request)
    {
        $sales = $this->currentSales();

        try {
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

        $transaction->load('items.product', 'customer', 'invoice');

        return view('sales.transactions.show', compact('transaction'));
    }
}
