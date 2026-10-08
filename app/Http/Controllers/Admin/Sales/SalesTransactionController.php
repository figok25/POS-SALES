<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\DepoSaleService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 6 - Admin: Transaksi Penjualan (Blueprint #22, #28 Sales Report).
 *
 * Dua sumber transaksi: Sales di lapangan (Sales App) dan penjualan langsung
 * Depo yang dibuat Admin (create/store) -- yang kedua berdiri sendiri, tanpa
 * Sales, stok dari Gudang Depo.
 */
class SalesTransactionController extends Controller
{
    /**
     * Query dasar daftar/laporan: Branch context + filter Sales & Sumber.
     */
    private function filteredQuery(Request $request)
    {
        $salesId = $request->query('sales_id');
        $source = $request->query('source');

        return BranchContext::current()->applyTo(
            SalesTransaction::query()->with(['sales', 'customer']),
            'branch_id'
        )
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->when($source === 'depo', fn ($q) => $q->whereNull('sales_id'))
            ->when($source === 'sales', fn ($q) => $q->whereNotNull('sales_id'))
            ->orderBy('id', 'desc');
    }

    public function index(Request $request)
    {
        $salesId = $request->query('sales_id');
        $source = in_array($request->query('source'), ['depo', 'sales'], true) ? $request->query('source') : null;

        $items = $this->filteredQuery($request)->paginate(15)->withQueryString();

        $salesList = BranchContext::current()->applyTo(\App\Models\Sales::query())->orderBy('name')->get();

        return view('admin.sales.transactions.index', compact('items', 'salesList', 'salesId', 'source'));
    }

    /**
     * Penjualan langsung Depo (kasir): pilih Depo (Super Admin) -> Gudang ->
     * isi produk dari stok Gudang itu. Pembeli = konsumen umum, harga Konsumen.
     */
    public function create(Request $request)
    {
        $context = BranchContext::current();
        $isAll = $context->isAll();

        $branches = $isAll ? Branch::where('is_active', true)->orderBy('name')->get() : collect();

        $branchId = $isAll
            ? ($branches->contains('id', (int) $request->query('branch_id')) ? (int) $request->query('branch_id') : null)
            : $context->branchId();

        $warehouses = $branchId
            ? Warehouse::where('branch_id', $branchId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        $warehouseId = (int) $request->query('warehouse_id');
        if (! $warehouses->contains('id', $warehouseId)) {
            $warehouseId = $warehouses->count() === 1 ? $warehouses->first()->id : null;
        }

        // Penjualan Depo = konsumen umum (kasir): harga otomatis kategori Konsumen.
        $priceType = Price::TYPE_CONSUMER;

        $stocks = collect();
        $prices = collect();

        if ($warehouseId) {
            $stocks = Stock::where('location_type', Stock::LOCATION_WAREHOUSE)
                ->where('location_id', $warehouseId)
                ->where('quantity', '>', 0)
                ->with('product')
                ->get()
                ->filter(fn ($stock) => $stock->product !== null && $stock->product->is_active)
                ->sortBy(fn ($stock) => $stock->product->name)
                ->values();

            $prices = Price::where('is_active', true)
                ->where('price_type', $priceType)
                ->orderBy('id')
                ->pluck('amount', 'product_id');
        }

        return view('admin.sales.transactions.create', compact(
            'isAll', 'branches', 'branchId', 'warehouses', 'warehouseId', 'priceType', 'stocks', 'prices'
        ));
    }

    public function store(Request $request, DepoSaleService $service)
    {
        $context = BranchContext::current();

        $data = $request->validate([
            'branch_id' => [$context->isAll() ? 'required' : 'nullable', 'exists:branches,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'consumer_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'pay_amount' => ['nullable', 'numeric', 'min:0'],
            'pay_method' => ['nullable', 'in:'.Payment::METHOD_CASH.','.Payment::METHOD_TRANSFER.','.Payment::METHOD_OTHER],
        ]);

        $branchId = $context->isAll() ? (int) $data['branch_id'] : (int) $context->branchId();

        if (! $context->allows($branchId)) {
            abort(403, 'Anda tidak memiliki akses ke Depo ini.');
        }

        try {
            $trx = $service->create($branchId, (int) $data['warehouse_id'], auth()->id(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.sales.transactions.show', $trx)
            ->with('status', "Transaksi {$trx->code} berhasil dibuat. Invoice {$trx->invoice->code} diterbitkan.");
    }

    public function show(SalesTransaction $transaction)
    {
        if (! BranchContext::current()->allows($transaction->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Transaksi ini.');
        }

        $transaction->load(['sales', 'customer', 'items.product', 'invoice', 'warehouse', 'branch']);

        return view('admin.sales.transactions.show', compact('transaction'));
    }

    /**
     * View print-friendly untuk Transaksi (Cetak Nota): format struk
     * (thermal ~80mm) dan A4, dipilih lewat query `?format=`.
     */
    public function print(Request $request, SalesTransaction $transaction)
    {
        if (! BranchContext::current()->allows($transaction->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Transaksi ini.');
        }

        $transaction->load(['sales', 'branch.company', 'customer', 'items.product', 'invoice']);

        $format = $request->query('format') === 'a4' ? 'a4' : 'struk';
        $company = $transaction->branch?->company;

        return view('admin.sales.transactions.print', compact('transaction', 'format', 'company'));
    }

    /**
     * Laporan Transaksi Penjualan Admin: unduh CSV mengikuti filter Sales
     * dan Sumber yang sedang aktif pada halaman index (kalau ada).
     */
    public function export(Request $request): StreamedResponse
    {
        $items = $this->filteredQuery($request)->get();

        $filename = 'laporan-transaksi-penjualan-'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($items) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Kode Transaksi', 'Tanggal', 'Sumber', 'Sales', 'Customer', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Status']);

            foreach ($items as $t) {
                fputcsv($out, [
                    $t->code,
                    $t->created_at->format('Y-m-d H:i'),
                    $t->isDepoSale() ? 'Toko Depo' : 'Sales',
                    $t->sales->name ?? 'Toko Depo',
                    $t->customer->name ?? '-',
                    $t->subtotal,
                    $t->discount,
                    $t->tax,
                    $t->total,
                    $t->status,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
