<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hapus (void) transaksi penjualan Sales oleh Super Admin.
 *
 * Transaksi tidak dihapus fisik: statusnya menjadi 'cancelled' (otomatis keluar dari
 * dashboard, KPI, EC, laporan) dan tetap terlihat di riwayat + audit log.
 *  - Stok: SEMUA barang (bayar + gratis) dikembalikan ke stok Sales.
 *  - Invoice: dibatalkan (tidak lagi menjadi piutang).
 *  - Pembayaran pada invoice itu dihapus, kecuali sudah masuk Settlement yang APPLIED
 *    (uang sudah direkonsiliasi) -> ditolak, koreksi Settlement-nya dulu.
 *  - Delivery Order Draft dibatalkan; DO yang sudah dikirim hanya diberi peringatan.
 * Penjualan langsung Depo belum didukung (alur pembatalannya terpisah).
 */
class SalesTransactionVoidService
{
    public function __construct(protected StockService $stock) {}

    /** @return array{stock_impact: array<int, array>, removed_payments: float, warnings: array<int, string>} */
    public function void(SalesTransaction $trx, string $reason): array
    {
        return DB::transaction(fn () => $this->doVoid($trx, $reason));
    }

    private function doVoid(SalesTransaction $trx, string $reason): array
    {
        $locked = SalesTransaction::query()->whereKey($trx->id)->lockForUpdate()->firstOrFail();

        if ($locked->status === SalesTransaction::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['reason' => 'Transaksi ini sudah dihapus/dibatalkan sebelumnya.']);
        }

        if ($locked->isDepoSale() || $locked->sales_id === null) {
            throw ValidationException::withMessages(['reason' => 'Penghapusan hanya untuk transaksi Sales. Penjualan Depo belum didukung.']);
        }

        $locked->load(['items', 'deliveryOrder']);
        $invoice = Invoice::query()->where('sales_transaction_id', $locked->id)->lockForUpdate()->first();
        $payments = $invoice ? Payment::query()->where('invoice_id', $invoice->id)->lockForUpdate()->get() : collect();

        $applied = $payments->filter(function (Payment $p) {
            return $p->settlement_id
                && Settlement::query()->whereKey($p->settlement_id)->where('status', Settlement::STATUS_APPLIED)->exists();
        });

        if ($applied->isNotEmpty()) {
            $codes = Settlement::query()->whereIn('id', $applied->pluck('settlement_id'))->pluck('code')->implode(', ');
            throw ValidationException::withMessages([
                'reason' => "Pembayaran transaksi ini sudah masuk Settlement {$codes} yang sudah di-apply. Koreksi Settlement tersebut terlebih dulu sebelum menghapus transaksi.",
            ]);
        }

        $before = [
            'branch_id' => $locked->branch_id,
            'code' => $locked->code,
            'status' => $locked->status,
            'sales_id' => $locked->sales_id,
            'customer_id' => $locked->customer_id,
            'total' => (float) $locked->total,
            'items' => $locked->items->map(fn ($i) => [
                'product_id' => (int) $i->product_id, 'quantity' => (float) $i->quantity, 'is_free' => (bool) $i->is_free,
            ])->values()->all(),
            'invoice' => $invoice ? ['code' => $invoice->code, 'status' => $invoice->status, 'paid_amount' => (float) $invoice->paid_amount] : null,
        ];

        // ---- Stok kembali ke Sales (bayar + gratis) ----
        $qty = [];
        foreach ($locked->items as $line) {
            $qty[(int) $line->product_id] = round(($qty[(int) $line->product_id] ?? 0) + (float) $line->quantity, 2);
        }

        $impact = [];
        $note = "Hapus transaksi {$locked->code}: {$reason}";
        foreach ($qty as $productId => $q) {
            if ($q > 0) {
                $this->stock->increase($productId, Stock::LOCATION_SALES, (int) $locked->sales_id, $q, 'sales_void', SalesTransaction::class, $locked->id, $note);
            }
            $impact[] = ['product_id' => $productId, 'returned' => $q];
        }

        $warnings = [];

        // ---- Pembayaran & invoice ----
        $removed = round((float) $payments->sum('amount'), 2);
        $draftSettlementCodes = Settlement::query()
            ->whereIn('id', $payments->pluck('settlement_id')->filter()->unique())
            ->pluck('code')->all();

        foreach ($payments as $payment) {
            $payment->delete();
        }

        if ($draftSettlementCodes !== []) {
            $warnings[] = 'Pembayaran transaksi ini sebelumnya tercatat di Settlement draft '.implode(', ', $draftSettlementCodes).'. Periksa kembali Settlement tersebut.';
        }
        if ($removed > 0) {
            $warnings[] = 'Pembayaran Rp '.number_format($removed, 0, ',', '.').' pada invoice transaksi ini ikut dihapus.';
        }

        if ($invoice) {
            $invoice->update(['status' => Invoice::STATUS_CANCELLED, 'paid_amount' => 0]);
        }

        // ---- Delivery Order ----
        $do = $locked->deliveryOrder;
        if ($do && $do->status === DeliveryOrder::STATUS_DRAFT) {
            $do->update(['status' => DeliveryOrder::STATUS_CANCELLED]);
        } elseif ($do && in_array($do->status, [DeliveryOrder::STATUS_DISPATCHED, DeliveryOrder::STATUS_DELIVERED], true)) {
            $warnings[] = "Delivery Order {$do->code} sudah {$do->status}; tidak dibatalkan otomatis. Tindak lanjuti secara manual.";
        }

        // ---- Transaksi ----
        $locked->forceFill([
            'status' => SalesTransaction::STATUS_CANCELLED,
            'voided_at' => now(),
            'voided_by' => Auth::id(),
            'void_reason' => $reason,
        ])->save();

        $after = $before;
        $after['status'] = SalesTransaction::STATUS_CANCELLED;
        $after['reason'] = $reason;
        $after['stock_impact'] = $impact;
        $after['removed_payments'] = $removed;
        $after['warnings'] = $warnings;
        $after['invoice'] = $invoice ? ['code' => $invoice->code, 'status' => Invoice::STATUS_CANCELLED, 'paid_amount' => 0.0] : null;

        AuditLogger::log('void', 'Sales Transaction', SalesTransaction::class, $locked->id, $before, $after);

        return ['stock_impact' => $impact, 'removed_payments' => $removed, 'warnings' => $warnings];
    }
}
