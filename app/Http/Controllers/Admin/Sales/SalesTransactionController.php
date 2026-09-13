<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;

/**
 * Phase 6 - Admin: Monitoring Sales Transaction (Blueprint #22, #28 Sales Report).
 * Read-only di sisi Admin - transaksi dibuat oleh Sales lewat Sales App.
 */
class SalesTransactionController extends Controller
{
    public function index(Request $request)
    {
        $salesId = $request->query('sales_id');

        $items = SalesTransaction::query()
            ->with(['sales', 'customer'])
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $salesList = \App\Models\Sales::orderBy('name')->get();

        return view('admin.sales.transactions.index', compact('items', 'salesList', 'salesId'));
    }

    public function show(SalesTransaction $transaction)
    {
        $transaction->load(['sales', 'customer', 'items.product', 'invoice']);

        return view('admin.sales.transactions.show', compact('transaction'));
    }
}
