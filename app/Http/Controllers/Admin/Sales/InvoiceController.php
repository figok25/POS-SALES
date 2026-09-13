<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

/**
 * Phase 6 - Admin: Invoice (Blueprint #23).
 * Payment terhadap invoice ini dikerjakan pada Fase 7.
 */
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $items = Invoice::query()
            ->with(['customer', 'sales'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sales.invoices.index', compact('items', 'status'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'sales', 'items.product', 'salesTransaction']);

        return view('admin.sales.invoices.show', compact('invoice'));
    }
}
