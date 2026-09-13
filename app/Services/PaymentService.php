<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\DocumentCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Payment (Blueprint #15): mendukung pembayaran penuh &
 * sebagian, selalu terhubung ke satu invoice, dan tercatat di audit
 * trail. Setiap payment baru otomatis meng-update
 * invoice.paid_amount & status (unpaid -> partial -> paid).
 */
class PaymentService
{
    /**
     * @param  array{amount: float|string, method?: string, paid_at?: string, reference_no?: string|null, notes?: string|null}  $data
     */
    public function create(Invoice $invoice, array $data, ?int $receivedByUserId): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $receivedByUserId) {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
            }

            if ($amount > $invoice->outstanding() + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Jumlah pembayaran (%s) melebihi outstanding invoice (%s).',
                        number_format($amount, 2),
                        number_format($invoice->outstanding(), 2),
                    ),
                ]);
            }

            $payment = Payment::create([
                'code' => 'TEMP',
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $data['method'] ?? Payment::METHOD_CASH,
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                'received_by' => $receivedByUserId,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $payment->update(['code' => DocumentCode::make('PAY', $payment->id)]);

            $before = ['paid_amount' => $invoice->paid_amount, 'status' => $invoice->status];

            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);
            $newStatus = $newPaidAmount >= (float) $invoice->grand_total
                ? Invoice::STATUS_PAID
                : Invoice::STATUS_PARTIAL;

            $invoice->update(['paid_amount' => $newPaidAmount, 'status' => $newStatus]);

            AuditLogger::log(
                action: 'create',
                module: 'Finance',
                documentType: Payment::class,
                documentId: $payment->id,
                after: $payment->fresh()->toArray(),
                userId: $receivedByUserId,
            );

            AuditLogger::log(
                action: 'update',
                module: 'Finance',
                documentType: Invoice::class,
                documentId: $invoice->id,
                before: $before,
                after: ['paid_amount' => $newPaidAmount, 'status' => $newStatus],
                userId: $receivedByUserId,
            );

            return $payment->fresh('invoice');
        });
    }
}
