<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Edit transaksi penjualan (Sales di lapangan maupun penjualan Depo) oleh
 * Admin/Superadmin yang berwenang.
 *
 * Yang dikoreksi: outlet/pembeli, barang, qty, harga, diskon, tanggal, keterangan.
 * Dampak dihitung dari SELISIH data lama vs baru -- tidak mengulang pergerakan stok:
 *   - Stok per produk (gabungan item bayar + gratis): naik = potong lagi, turun = kembalikan.
 *     Lokasi stok: Sales (transaksi Sales) atau Gudang Depo (penjualan Depo).
 *   - Invoice dihitung ulang (subtotal, diskon, total, item, status unpaid/partial/paid).
 *     Total baru tidak boleh lebih kecil dari pembayaran yang sudah masuk, kecuali Admin memilih
 *     "sesuaikan pembayaran" (pembayaran yang belum masuk Settlement dikurangi otomatis).
 *   - Delivery Order Draft disamakan dengan item baru; DO yang sudah dikirim tidak diubah (diberi peringatan).
 *   - Transaksi yang sudah masuk Settlement tetap bisa dikoreksi; Settlement-nya diberi peringatan agar dicek.
 * Barang gratis program: hanya qty-nya yang bisa diubah/dihapus (produk & program tetap).
 */
class SalesTransactionRevisionService
{
    public function __construct(protected StockService $stock) {}

    /**
     * @param  array{customer_id?:int|string|null, consumer_name?:?string, transaction_date?:?string, discount?:mixed, notes?:?string, items:array<int, array{product_id:int|string, quantity:mixed, price:mixed}>, free?:array<int|string, mixed>}  $data
     * @return array{stock_impact: array<int, array>, old_total: float, new_total: float, warnings: array<int, string>}
     */
    public function revise(SalesTransaction $trx, array $data, string $reason): array
    {
        try {
            return DB::transaction(fn () => $this->doRevise($trx, $data, $reason));
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }
    }

    private function doRevise(SalesTransaction $trx, array $data, string $reason): array
    {
        $locked = SalesTransaction::query()->whereKey($trx->id)->lockForUpdate()->firstOrFail();

        if ($locked->status === SalesTransaction::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['items' => 'Transaksi yang sudah dibatalkan tidak dapat diedit.']);
        }

        $locked->load(['items', 'deliveryOrder.items']);
        $invoice = Invoice::query()->where('sales_transaction_id', $locked->id)->lockForUpdate()->first();
        $before = $this->snapshot($locked, $invoice);

        // ---- Baris bayar baru ----
        $oldProductIds = $locked->items->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $newPaid = [];
        foreach ($data['items'] ?? [] as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = round((float) ($row['quantity'] ?? 0), 2);
            $price = round((float) ($row['price'] ?? 0), 2);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $product = Product::find($productId);
            if (! $product || (! $product->is_active && ! in_array($productId, $oldProductIds, true))) {
                throw ValidationException::withMessages(['items' => "Produk #{$productId} tidak ditemukan atau sudah nonaktif."]);
            }

            if ($price < 0) {
                throw ValidationException::withMessages(['items' => "Harga {$product->name} tidak boleh negatif."]);
            }

            $newPaid[] = ['product_id' => $productId, 'quantity' => $qty, 'price' => $price, 'subtotal' => round($price * $qty, 2)];
        }

        // ---- Baris gratis: hanya qty yang bisa diubah / dihapus ----
        $newFree = [];
        $freeInput = $data['free'] ?? [];
        foreach ($locked->items->where('is_free', true) as $old) {
            $qty = array_key_exists($old->id, $freeInput) ? round((float) $freeInput[$old->id], 2) : round((float) $old->quantity, 2);
            if ($qty > 0) {
                $newFree[] = [
                    'product_id' => (int) $old->product_id, 'quantity' => $qty, 'price' => 0, 'subtotal' => 0,
                    'is_free' => true, 'buy_get_promo_id' => $old->buy_get_promo_id, 'shell_qty' => $old->shell_qty,
                ];
            }
        }

        if ($newPaid === [] && $newFree === []) {
            throw ValidationException::withMessages(['items' => 'Transaksi harus memiliki minimal 1 item.']);
        }

        // ---- Pembeli ----
        $customerId = $locked->customer_id;
        $consumerName = $locked->consumer_name;

        if ($locked->isDepoSale()) {
            if (array_key_exists('consumer_name', $data)) {
                $name = trim((string) $data['consumer_name']);
                $consumerName = $name === '' ? null : mb_substr($name, 0, 120);
            }
        } elseif (! empty($data['customer_id']) && (int) $data['customer_id'] !== (int) $locked->customer_id) {
            $customer = Customer::find($data['customer_id']);

            if (! $customer || ! $customer->is_active) {
                throw ValidationException::withMessages(['customer_id' => 'Customer tidak ditemukan atau sudah nonaktif.']);
            }
            if ($customer->sales_id && (int) $customer->sales_id !== (int) $locked->sales_id) {
                throw ValidationException::withMessages(['customer_id' => 'Customer ini terdaftar pada Sales lain.']);
            }
            if ($customer->branch_id !== null && $locked->branch_id !== null && (int) $customer->branch_id !== (int) $locked->branch_id) {
                throw ValidationException::withMessages(['customer_id' => 'Customer ini terdaftar di Depo lain.']);
            }
            $customerId = $customer->id;
        }

        // ---- Total ----
        $subtotal = round(array_sum(array_column($newPaid, 'subtotal')), 2);
        $discount = round((float) ($data['discount'] ?? $locked->discount), 2);

        if ($discount < 0 || $discount > $subtotal + 0.001) {
            throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh negatif atau melebihi subtotal.']);
        }

        $tax = round((float) $locked->tax, 2);
        $newTotal = round($subtotal - $discount + $tax, 2);
        $oldTotal = round((float) $locked->total, 2);

        $paid = $invoice ? round((float) $invoice->paid_amount, 2) : 0.0;
        $paymentAdjust = null;
        if ($invoice && $paid > $newTotal + 0.01) {
            if (empty($data['adjust_payment'])) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Total baru (Rp %s) lebih kecil dari pembayaran yang sudah masuk (Rp %s). Centang "Sesuaikan pembayaran ke total baru" untuk mengurangi pembayaran otomatis.',
                        number_format($newTotal, 0, ',', '.'),
                        number_format($paid, 0, ',', '.'),
                    ),
                ]);
            }

            $paymentAdjust = $this->shrinkPayments($invoice, round($paid - $newTotal, 2), $newTotal, $paid);
            $invoice->refresh();
            $paid = round((float) $invoice->paid_amount, 2);
        }

        // ---- Stok: selisih per produk (bayar + gratis) ----
        $oldQty = [];
        foreach ($locked->items as $line) {
            $oldQty[(int) $line->product_id] = round(($oldQty[(int) $line->product_id] ?? 0) + (float) $line->quantity, 2);
        }
        $newQty = [];
        foreach (array_merge($newPaid, $newFree) as $line) {
            $newQty[$line['product_id']] = round(($newQty[$line['product_id']] ?? 0) + $line['quantity'], 2);
        }

        [$locType, $locId] = $locked->isDepoSale()
            ? [Stock::LOCATION_WAREHOUSE, (int) $locked->warehouse_id]
            : [Stock::LOCATION_SALES, (int) $locked->sales_id];

        $impact = [];
        foreach (array_unique(array_merge(array_keys($oldQty), array_keys($newQty))) as $productId) {
            $o = $oldQty[$productId] ?? 0.0;
            $n = $newQty[$productId] ?? 0.0;
            $impact[] = ['product_id' => (int) $productId, 'old' => (float) $o, 'new' => (float) $n, 'delta' => round($n - $o, 2)];
        }

        usort($impact, fn ($a, $b) => $a['delta'] <=> $b['delta']); // kembalikan dulu, baru potong

        $note = "Revisi transaksi {$locked->code}: {$reason}";
        foreach ($impact as $row) {
            if ($row['delta'] > 0) {
                $this->stock->decrease($row['product_id'], $locType, $locId, $row['delta'], 'sales_revision', SalesTransaction::class, $locked->id, $note);
            } elseif ($row['delta'] < 0) {
                $this->stock->increase($row['product_id'], $locType, $locId, abs($row['delta']), 'sales_revision', SalesTransaction::class, $locked->id, $note);
            }
        }

        // ---- Simpan transaksi ----
        $locked->items()->delete();
        foreach (array_merge($newPaid, $newFree) as $line) {
            $locked->items()->create($line);
        }

        $dateChanged = false;
        if (! empty($data['transaction_date'])) {
            $newDay = Carbon::parse($data['transaction_date'])->toDateString();
            if ($newDay !== $locked->created_at->toDateString()) {
                $locked->created_at = Carbon::parse($newDay.' '.$locked->created_at->format('H:i:s'));
                $dateChanged = true;
            }
        }

        // Kategori harga (Retail / WS-Grosir; Depo juga Konsumen). Transaksi Tester tidak bisa diubah.
        if (! empty($data['price_type']) && $data['price_type'] !== $locked->price_type) {
            $allowed = $locked->isDepoSale() ? Price::types() : Price::salesTypes();

            if ($locked->price_type === Price::TYPE_TESTER || ! array_key_exists($data['price_type'], $allowed)) {
                throw ValidationException::withMessages(['price_type' => 'Kategori harga tidak valid untuk transaksi ini.']);
            }

            $locked->price_type = $data['price_type'];
        }

        $locked->customer_id = $customerId;
        $locked->consumer_name = $consumerName;
        $locked->subtotal = $subtotal;
        $locked->discount = $discount;
        $locked->total = $newTotal;
        $locked->notes = array_key_exists('notes', $data) ? $data['notes'] : $locked->notes;
        $locked->revision_count = (int) $locked->revision_count + 1;
        $locked->last_revised_by = Auth::id();
        $locked->last_revised_at = now();
        $locked->revision_reason = $reason;
        $locked->save();

        $warnings = [];

        // ---- Invoice ----
        if ($invoice) {
            $status = ($newTotal <= 0 || $paid >= $newTotal - 0.005)
                ? Invoice::STATUS_PAID
                : ($paid > 0 ? Invoice::STATUS_PARTIAL : Invoice::STATUS_UNPAID);

            $invoice->update([
                'customer_id' => $customerId,
                'consumer_name' => $consumerName,
                'date' => $dateChanged ? $locked->created_at->toDateString() : $invoice->date,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $newTotal,
                'status' => $status,
            ]);

            $invoice->items()->delete();
            foreach ($locked->items()->get() as $line) {
                $invoice->items()->create([
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'subtotal' => $line->subtotal,
                    'is_free' => (bool) $line->is_free,
                ]);
            }

            $settlementCodes = Payment::query()
                ->where('payments.invoice_id', $invoice->id)
                ->whereNotNull('payments.settlement_id')
                ->join('settlements', 'settlements.id', '=', 'payments.settlement_id')
                ->distinct()->pluck('settlements.code')->all();

            if ($settlementCodes !== [] && abs($newTotal - $oldTotal) > 0.009) {
                $warnings[] = 'Pembayaran transaksi ini sudah masuk Settlement '.implode(', ', $settlementCodes).'. Periksa/koreksi Settlement tersebut karena total transaksi berubah.';
            }
        }

        // ---- Delivery Order ----
        $do = $locked->deliveryOrder;
        if ($do && $do->status === DeliveryOrder::STATUS_DRAFT) {
            $do->items()->delete();
            foreach ($locked->items()->get() as $line) {
                $do->items()->create(['product_id' => $line->product_id, 'quantity' => $line->quantity]);
            }
        } elseif ($do && in_array($do->status, [DeliveryOrder::STATUS_DISPATCHED, DeliveryOrder::STATUS_DELIVERED], true)) {
            $warnings[] = "Delivery Order {$do->code} sudah {$do->status}; item DO tidak diubah otomatis. Sesuaikan secara manual bila perlu.";
        }

        // ---- Audit ----
        $after = $this->snapshot($locked->fresh('items'), $invoice?->fresh());
        $after['reason'] = $reason;
        $after['stock_location'] = $locType.'#'.$locId;
        $after['stock_impact'] = $impact;
        $after['old_total'] = $oldTotal;
        $after['new_total'] = $newTotal;
        $after['warnings'] = $warnings;
        if ($paymentAdjust) {
            $after['payment_adjustment'] = $paymentAdjust;
        }

        AuditLogger::log('revise', 'Sales Transaction', SalesTransaction::class, $locked->id, $before, $after);

        return ['stock_impact' => $impact, 'old_total' => $oldTotal, 'new_total' => $newTotal, 'warnings' => $warnings];
    }

    /**
     * Kurangi pembayaran yang BELUM masuk Settlement (terbaru dulu) sebesar $excess.
     * Pembayaran yang sudah masuk Settlement tidak disentuh; bila tidak cukup -> ditolak.
     *
     * @return array<int, array{code:string, from:float, to:float}>
     */
    private function shrinkPayments(Invoice $invoice, float $excess, float $newTotal, float $paid): array
    {
        $open = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->whereNull('settlement_id')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        $available = round((float) $open->sum('amount'), 2);
        if ($available + 0.01 < $excess) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'Pembayaran tidak bisa disesuaikan otomatis: selisih Rp %s, tetapi pembayaran yang belum masuk Settlement hanya Rp %s. Pembayaran yang sudah diproses Settlement tidak boleh diubah.',
                    number_format($excess, 0, ',', '.'),
                    number_format($available, 0, ',', '.'),
                ),
            ]);
        }

        $remaining = $excess;
        $changes = [];
        foreach ($open as $payment) {
            if ($remaining <= 0.005) {
                break;
            }
            $from = round((float) $payment->amount, 2);
            $cut = min($from, $remaining);
            $to = round($from - $cut, 2);
            $remaining = round($remaining - $cut, 2);

            $changes[] = ['code' => $payment->code, 'from' => $from, 'to' => $to];

            if ($to <= 0.005) {
                $payment->delete();
            } else {
                $payment->update(['amount' => $to]);
            }
        }

        $invoice->update(['paid_amount' => round($paid - $excess, 2)]);

        return $changes;
    }

    private function snapshot(SalesTransaction $trx, ?Invoice $invoice): array
    {
        return [
            'branch_id' => $trx->branch_id,
            'code' => $trx->code,
            'sales_id' => $trx->sales_id,
            'customer_id' => $trx->customer_id,
            'consumer_name' => $trx->consumer_name,
            'price_type' => $trx->price_type,
            'date' => $trx->created_at?->toDateString(),
            'subtotal' => (float) $trx->subtotal,
            'discount' => (float) $trx->discount,
            'total' => (float) $trx->total,
            'notes' => $trx->notes,
            'items' => $trx->items->map(fn ($i) => [
                'product_id' => (int) $i->product_id,
                'quantity' => (float) $i->quantity,
                'price' => (float) $i->price,
                'is_free' => (bool) $i->is_free,
            ])->values()->all(),
            'invoice' => $invoice ? [
                'code' => $invoice->code,
                'grand_total' => (float) $invoice->grand_total,
                'paid_amount' => (float) $invoice->paid_amount,
                'status' => $invoice->status,
            ] : null,
        ];
    }
}
