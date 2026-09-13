<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Admin: Payment (Blueprint #15).
 */
class PaymentController extends Controller
{
    public function __construct(protected PaymentService $service) {}

    public function index(Request $request)
    {
        $method = $request->query('method');

        $items = Payment::query()
            ->with(['invoice.customer', 'invoice.sales', 'receivedBy'])
            ->when($method, fn ($q) => $q->where('method', $method))
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.finance.payments.index', compact('items', 'method'));
    }

    public function create(Invoice $invoice)
    {
        if ($invoice->isFullyPaid()) {
            return redirect()->route('admin.sales.invoices.show', $invoice)->with('status', 'Invoice ini sudah lunas.');
        }

        return view('admin.finance.payments.create', compact('invoice'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,transfer,other'],
            'paid_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->create($invoice, $data, auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.sales.invoices.show', $invoice)->with('status', 'Payment berhasil dicatat.');
    }
}
