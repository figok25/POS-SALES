<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Sales App: Payment (Blueprint #15).
 * Sales mencatat pembayaran yang mereka terima langsung dari Customer
 * di lapangan (biasanya cash) untuk invoice milik mereka sendiri.
 */
class PaymentController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected PaymentService $service) {}

    public function create(Invoice $invoice)
    {
        $sales = $this->currentSales();
        abort_if($invoice->sales_id !== $sales->id, 403);

        if ($invoice->isFullyPaid()) {
            return redirect()->route('sales.transactions.show', $invoice->salesTransaction)->with('status', 'Invoice ini sudah lunas.');
        }

        return view('sales.payments.create', compact('invoice'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        $sales = $this->currentSales();
        abort_if($invoice->sales_id !== $sales->id, 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,transfer,other'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $data['paid_at'] = now()->toDateString();

        try {
            $this->service->create($invoice, $data, auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('sales.transactions.show', $invoice->salesTransaction)->with('status', 'Payment berhasil dicatat.');
    }
}
