<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Support\DocumentCode;

/**
 * Phase 6 - Invoice (Blueprint #23).
 * Invoice -> Payment -> Settlement. Payment/Settlement dikerjakan pada
 * Fase 7; service ini hanya bertanggung jawab menerbitkan invoice dari
 * Sales Transaction yang sudah selesai.
 */
class InvoiceService
{
    public function generateFromSalesTransaction(SalesTransaction $trx): Invoice
    {
        $invoice = Invoice::create([
            'code' => 'TEMP',
            'sales_transaction_id' => $trx->id,
            'customer_id' => $trx->customer_id,
            'sales_id' => $trx->sales_id,
            'date' => now()->toDateString(),
            'subtotal' => $trx->subtotal,
            'discount' => $trx->discount,
            'tax' => $trx->tax,
            'grand_total' => $trx->total,
            'paid_amount' => 0,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $invoice->update(['code' => DocumentCode::make('INV', $invoice->id)]);

        foreach ($trx->items as $line) {
            $invoice->items()->create([
                'product_id' => $line->product_id,
                'quantity' => $line->quantity,
                'price' => $line->price,
                'subtotal' => $line->subtotal,
            ]);
        }

        return $invoice;
    }
}
