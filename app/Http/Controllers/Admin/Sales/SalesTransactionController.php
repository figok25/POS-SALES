<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sales\SalesTransactionRevisionRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\DepoSaleService;
use App\Services\SalesTransactionService;
use App\Services\SalesTransactionRevisionService;
use App\Support\BranchContext;
use Carbon\Carbon;
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
     * Tanggal dari request (Y-m-d) atau null kalau kosong/tidak valid.
     */
    private function parseDate(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Query dasar daftar/laporan: Branch context + filter lengkap (tanggal,
     * Sales, Sumber, Gudang, Status, Pembayaran, pencarian).
     *
     * Depo/Branch dipilih lewat `?branch=` (ditangani BranchContext, hanya
     * berlaku untuk Super Admin).
     */
    private function filteredQuery(Request $request)
    {
        $salesId = $request->query('sales_id');
        $source = $request->query('source');
        $status = $request->query('status');
        $payment = $request->query('payment');
        $warehouseId = $request->query('warehouse_id');
        $search = trim((string) $request->query('q', ''));

        $from = $this->parseDate($request->query('date_from'));
        $to = $this->parseDate($request->query('date_to'));

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return BranchContext::current()->applyTo(
            SalesTransaction::query()->with(['sales', 'customer', 'branch']),
            'branch_id'
        )
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->when($source === 'depo', fn ($q) => $q->whereNull('sales_id'))
            ->when($source === 'sales', fn ($q) => $q->whereNotNull('sales_id'))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when(
                in_array($status, [SalesTransaction::STATUS_COMPLETED, SalesTransaction::STATUS_CANCELLED], true),
                fn ($q) => $q->where('status', $status)
            )
            ->when(
                in_array($payment, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID], true),
                fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('status', $payment))
            )
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to->toDateString()))
            ->tap(fn ($q) => \App\Support\Search::apply($q, $search, ['code', 'consumer_name'], ['customer' => ['name'], 'sales' => ['name'], 'invoice' => ['code']]))
            ->orderBy('id', 'desc');
    }

    /** Transaksi penjualan oleh Sales di lapangan. */
    public function index(Request $request)
    {
        return $this->renderIndex($request, false);
    }

    /** Transaksi Toko Depo (kasir Admin) -- menu sendiri di luar menu Sales. */
    public function depoIndex(Request $request)
    {
        return $this->renderIndex($request, true);
    }

    private function renderIndex(Request $request, bool $depoOnly)
    {
        $request->query->set('source', $depoOnly ? 'depo' : 'sales');

        if ($depoOnly) {
            $request->query->remove('sales_id');
        }

        $context = BranchContext::current();
        $salesId = $depoOnly ? null : $request->query('sales_id');
        $source = $request->query('source');

        $query = $this->filteredQuery($request);

        // Ringkasan mengikuti seluruh filter: jumlah transaksi & omzet (completed).
        $summary = [
            'count' => (clone $query)->toBase()->count(),
            'completed' => (clone $query)->where('status', SalesTransaction::STATUS_COMPLETED)->toBase()->count(),
            'cancelled' => (clone $query)->where('status', SalesTransaction::STATUS_CANCELLED)->toBase()->count(),
            'total' => (float) (clone $query)->where('status', SalesTransaction::STATUS_COMPLETED)->toBase()->sum('total'),
        ];

        $items = $query->paginate(15)->withQueryString();

        $salesList = $context->applyTo(\App\Models\Sales::query())->orderBy('name')->get();

        // Pilihan Depo hanya untuk Super Admin (Admin terkunci ke Depo-nya).
        $branches = $context->isAll() || auth()->user()?->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $warehouses = $depoOnly
            ? $context->applyTo(Warehouse::query()->where('is_active', true))->orderBy('name')->get()
            : collect();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => $this->parseDate($request->query('date_from'))?->toDateString(),
            'date_to' => $this->parseDate($request->query('date_to'))?->toDateString(),
            'status' => $request->query('status'),
            'payment' => $request->query('payment'),
            'warehouse_id' => $request->query('warehouse_id'),
            'branch' => $context->isAll() ? 'all' : (string) $context->branchId(),
        ];

        $isFiltered = filled($salesId) || filled($filters['q']) || filled($filters['date_from'])
            || filled($filters['date_to']) || filled($filters['status']) || filled($filters['payment'])
            || filled($filters['warehouse_id']) || (auth()->user()?->isSuperAdmin() && ! $context->isAll());

        return view('admin.sales.transactions.index', compact(
            'items', 'salesList', 'salesId', 'source', 'depoOnly', 'summary', 'branches', 'warehouses', 'filters', 'isFiltered'
        ));
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

        // Kategori harga dipilih Admin (default Konsumen / kasir).
        $priceTypes = Price::types();
        $priceType = array_key_exists((string) $request->query('price_type'), $priceTypes)
            ? (string) $request->query('price_type')
            : Price::TYPE_CONSUMER;

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
            'isAll', 'branches', 'branchId', 'warehouses', 'warehouseId', 'priceType', 'priceTypes', 'stocks', 'prices'
        ));
    }

    public function store(Request $request, DepoSaleService $service)
    {
        $context = BranchContext::current();

        $data = $request->validate([
            'branch_id' => [$context->isAll() ? 'required' : 'nullable', 'exists:branches,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'consumer_name' => ['nullable', 'string', 'max:120'],
            'price_type' => ['nullable', 'in:'.implode(',', array_keys(Price::types()))],
            'is_credit' => ['nullable', 'boolean'],
            'credit_due_date' => ['nullable', 'date'],
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

        $data['is_credit'] = $request->boolean('is_credit');

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

        $transaction->load(['sales', 'customer', 'items.product', 'invoice', 'warehouse', 'branch', 'creator']);

        $history = AuditLog::query()
            ->with('user:id,name')
            ->where('document_type', SalesTransaction::class)
            ->where('document_id', $transaction->id)
            ->where('action', 'revise')
            ->orderByDesc('id')
            ->get();

        return view('admin.sales.transactions.show', compact('transaction', 'history'));
    }

    /**
     * Admin/Superadmin mencatat transaksi atas nama Sales (Sales lupa input).
     * Pilih Sales -> halaman memuat Customer, stok Sales, dan harga sesuai jenis Sales.
     */
    public function createForSales(Request $request)
    {
        $context = BranchContext::current();

        $salesList = $context->applyTo(\App\Models\Sales::query()->where('is_active', true))->orderBy('name')->get();

        $salesId = (int) $request->query('sales_id');
        $sales = $salesList->firstWhere('id', $salesId);

        $customers = collect();
        $stocks = collect();
        $prices = collect();

        if ($sales) {
            $customers = Customer::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('sales_id', $sales->id)->orWhereNull('sales_id'))
                ->when($sales->branch_id, fn ($q) => $q->where(fn ($x) => $x->where('branch_id', $sales->branch_id)->orWhereNull('branch_id')))
                ->orderBy('name')
                ->get(['id', 'code', 'name']);

            $stocks = Stock::where('location_type', Stock::LOCATION_SALES)
                ->where('location_id', $sales->id)
                ->where('quantity', '>', 0)
                ->with('product')
                ->get()
                ->filter(fn ($stock) => $stock->product !== null && $stock->product->is_active)
                ->sortBy(fn ($stock) => $stock->product->name)
                ->values();

            $prices = Price::where('is_active', true)
                ->where('price_type', $sales->priceType())
                ->orderBy('id')
                ->pluck('amount', 'product_id');
        }

        return view('admin.sales.transactions.create-for-sales', compact('salesList', 'sales', 'customers', 'stocks', 'prices'));
    }

    public function storeForSales(Request $request, SalesTransactionService $service)
    {
        $data = $request->validate([
            'sales_id' => ['required', 'exists:sales,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'entry_reason' => ['required', 'string', 'min:3', 'max:500'],
            'allow_duplicate' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ], [
            'entry_reason.required' => 'Catatan/alasan input bantuan wajib diisi.',
        ]);

        $sales = \App\Models\Sales::findOrFail($data['sales_id']);

        if (! BranchContext::current()->allows($sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Sales ini.');
        }

        $data['admin_entry'] = true;
        $data['allow_duplicate'] = $request->boolean('allow_duplicate');

        try {
            $trx = $service->create($sales->id, auth()->id(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.sales.transactions.show', $trx)
            ->with('status', "Transaksi {$trx->code} atas nama {$sales->name} berhasil dicatat. Stok Sales dipotong dan invoice {$trx->invoice->code} diterbitkan.");
    }

    /** Form edit transaksi (koreksi salah input). */
    public function edit(SalesTransaction $transaction)
    {
        if (! BranchContext::current()->allows($transaction->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Transaksi ini.');
        }

        if ($transaction->status === SalesTransaction::STATUS_CANCELLED) {
            return redirect()->route('admin.sales.transactions.show', $transaction)
                ->with('error', 'Transaksi yang sudah dibatalkan tidak dapat diedit.');
        }

        $transaction->load(['sales', 'customer', 'items.product', 'invoice', 'warehouse', 'branch']);

        $customers = collect();
        if (! $transaction->isDepoSale()) {
            $customers = Customer::query()
                ->where(function ($q) use ($transaction) {
                    $q->where('id', $transaction->customer_id)
                        ->orWhere(function ($qq) use ($transaction) {
                            $qq->where('is_active', true)
                                ->where(fn ($s) => $s->where('sales_id', $transaction->sales_id)->orWhereNull('sales_id'))
                                ->when($transaction->branch_id, fn ($b) => $b->where(fn ($x) => $x->where('branch_id', $transaction->branch_id)->orWhereNull('branch_id')));
                        });
                })
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        }

        $products = Product::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $transaction->items->pluck('product_id')))
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        return view('admin.sales.transactions.edit', compact('transaction', 'customers', 'products'));
    }

    public function update(SalesTransactionRevisionRequest $request, SalesTransaction $transaction, SalesTransactionRevisionService $service)
    {
        if (! BranchContext::current()->allows($transaction->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Transaksi ini.');
        }

        $data = $request->validated();

        try {
            $result = $service->revise($transaction, $data, $data['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $changed = collect($result['stock_impact'])->filter(fn ($r) => $r['delta'] != 0.0)->count();

        return redirect()->route('admin.sales.transactions.show', $transaction)
            ->with('status', "Transaksi {$transaction->code} berhasil dikoreksi. Stok disesuaikan berdasarkan selisih ({$changed} produk berubah), invoice dihitung ulang.")
            ->with('warnings', $result['warnings']);
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
            fputcsv($out, ['Kode Transaksi', 'Tanggal', 'Depo', 'Sumber', 'Sales', 'Customer', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Status']);

            foreach ($items as $t) {
                fputcsv($out, [
                    $t->code,
                    $t->created_at->format('Y-m-d H:i'),
                    $t->branch->name ?? '-',
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
