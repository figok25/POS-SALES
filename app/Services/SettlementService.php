<?php

namespace App\Services;

use App\Models\BtbDistribusi;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\Stock;
use App\Services\AuditLogger;
use App\Support\DocumentCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Settlement (Blueprint #16): pertanggungjawaban Sales di
 * akhir hari/rute.
 *
 * ALUR (diluruskan dengan BTB Distribusi):
 *
 *  - BARANG: HANYA BTB Distribusi yang memindahkan stok (Apply BTB =
 *    Sales Stock -> Warehouse Stock). Settlement TIDAK memindahkan stok
 *    apa pun. Bagian barang di Settlement murni turunan dari BTB:
 *      * BTB sudah Applied   -> barang "kembali", tidak ada selisih.
 *      * BTB belum Applied   -> tampil "menunggu BTB"; Settlement tidak
 *                               bisa di-Apply sampai BTB-nya di-Apply.
 *      * stok masih di Sales tanpa BTB -> "belum diretur" = selisih nyata.
 *  - UANG: cash dari payment yang belum ter-settlement; disetor, selisih
 *    uang dihitung otomatis.
 *
 * DRAFT OTOMATIS: Admin tidak membuat draft manual. Draft dibuat otomatis
 * (ensureDraft) ketika Sales submit Return Stock, menyelesaikan Task
 * (stok habis), atau Admin meng-Apply BTB manual. Admin tinggal Cek lalu
 * Apply.
 *
 * Payment (cash, belum ter-settlement) langsung "direservasi" ke
 * Settlement begitu Draft dibuat, supaya angka cash_expected tidak
 * berubah-ubah dan tidak ada dua Settlement berebut Payment yang sama.
 * BTB diklaim dengan cara serupa (btb_distribusi.settlement_id).
 */
class SettlementService
{
    public function createDraft(int $salesId, int $warehouseId, ?int $createdByUserId): Settlement
    {
        return DB::transaction(function () use ($salesId, $warehouseId, $createdByUserId) {
            $hasOpenDraft = Settlement::where('sales_id', $salesId)
                ->where('status', Settlement::STATUS_DRAFT)
                ->exists();

            if ($hasOpenDraft) {
                throw ValidationException::withMessages([
                    'sales_id' => 'Sales ini masih punya Settlement Draft yang belum di-Apply atau dibatalkan.',
                ]);
            }

            $payments = Payment::whereNull('settlement_id')
                ->where('method', Payment::METHOD_CASH)
                ->whereHas('invoice', fn ($q) => $q->where('sales_id', $salesId))
                ->lockForUpdate()
                ->get();

            $cashExpected = round((float) $payments->sum('amount'), 2);

            $settlement = Settlement::create([
                'code' => 'TEMP',
                'sales_id' => $salesId,
                'warehouse_id' => $warehouseId,
                'settled_at' => now()->toDateString(),
                'cash_expected' => $cashExpected,
                'cash_deposited' => 0,
                'cash_variance' => -$cashExpected,
                'status' => Settlement::STATUS_DRAFT,
                'created_by' => $createdByUserId,
            ]);
            $settlement->update(['code' => DocumentCode::make('STL', $settlement->id)]);

            foreach ($payments as $payment) {
                $payment->update(['settlement_id' => $settlement->id]);
            }

            // Barang TIDAK di-snapshot di sini: stok Sales saat draft dibuat
            // masih mencakup barang yang menunggu BTB. Barang selalu
            // dihitung langsung dari BTB + stok Sales (goodsSummary), dan
            // baris settlement_items baru ditulis saat Apply sebagai catatan
            // final.

            AuditLogger::log(
                action: 'create',
                module: 'Finance',
                documentType: Settlement::class,
                documentId: $settlement->id,
                after: $settlement->fresh(['payments'])->toArray(),
                userId: $createdByUserId,
            );

            return $settlement->fresh(['items.product', 'payments', 'sales']);
        });
    }

    /**
     * Pastikan Sales punya Settlement Draft yang memuat BTB-nya -- dipanggil
     * otomatis dari alur Return Stock / selesai Task / Apply BTB. Aman
     * dipanggil berulang (idempotent).
     *
     *  - Sudah ada draft terbuka  -> BTB yang belum diklaim ikut dimasukkan.
     *  - Belum ada draft          -> dibuat HANYA jika ada sesuatu untuk
     *    di-settle (payment cash belum disetor atau BTB yang bisa diklaim).
     *    Kalau tidak ada apa-apa, kembalikan null (tidak membuat draft kosong).
     *
     * BTB yang diklaim: yang masih menunggu (draft) milik Sales ini, plus
     * $trigger (BTB yang baru saja di-Apply). BTB Applied lama yang tidak
     * dipicu sengaja TIDAK diklaim, supaya riwayat lama tidak ikut terseret
     * ke Settlement baru.
     */
    public function ensureDraft(int $salesId, int $warehouseId, ?int $byUserId, ?BtbDistribusi $trigger = null): ?Settlement
    {
        return DB::transaction(function () use ($salesId, $warehouseId, $byUserId, $trigger) {
            $claimable = BtbDistribusi::where('sales_id', $salesId)
                ->whereNull('settlement_id')
                ->where('status', '!=', BtbDistribusi::STATUS_CANCELLED)
                ->where(function ($q) use ($trigger) {
                    $q->where('status', BtbDistribusi::STATUS_DRAFT);
                    if ($trigger) {
                        $q->orWhere('id', $trigger->id);
                    }
                })
                ->lockForUpdate()
                ->get();

            $open = Settlement::where('sales_id', $salesId)
                ->where('status', Settlement::STATUS_DRAFT)
                ->lockForUpdate()
                ->first();

            if ($open) {
                $this->claimBtbs($claimable, $open);

                return $open;
            }

            $hasUnsettledCash = Payment::whereNull('settlement_id')
                ->where('method', Payment::METHOD_CASH)
                ->whereHas('invoice', fn ($q) => $q->where('sales_id', $salesId))
                ->exists();

            if (! $hasUnsettledCash && $claimable->isEmpty()) {
                return null;
            }

            $settlement = $this->createDraft($salesId, $warehouseId, $byUserId);
            $this->claimBtbs($claimable, $settlement);

            return $settlement;
        });
    }

    /**
     * Status barang untuk satu Settlement, diturunkan dari BTB + stok Sales
     * SAAT INI (tidak ada snapshot yang bisa basi).
     *
     * Per produk:
     *  - returned   : qty pada BTB yang sudah Applied (barang sudah kembali).
     *  - pending    : qty pada BTB yang masih menunggu Check/Apply.
     *  - unreturned : sisa stok Sales yang TIDAK tercakup BTB menunggu =
     *                 selisih nyata. (BTB Discrepancy tidak memindahkan stok,
     *                 jadi barangnya masih di Sales dan ikut terhitung di sini.)
     *
     * @return array{rows: Collection<int, array{product_id:int, product:mixed, returned:float, pending:float, unreturned:float}>, btbs: Collection, pending_btbs: Collection, has_pending: bool, total_unreturned: float}
     */
    public function goodsSummary(Settlement $settlement): array
    {
        $btbs = BtbDistribusi::with('items.product')
            ->where('settlement_id', $settlement->id)
            ->where('status', '!=', BtbDistribusi::STATUS_CANCELLED)
            ->orderBy('id')
            ->get();

        $returned = [];
        $pending = [];
        $products = [];

        foreach ($btbs as $btb) {
            foreach ($btb->items as $line) {
                $products[$line->product_id] = $line->product;
                $qty = (float) $line->quantity;

                if ($btb->status === BtbDistribusi::STATUS_APPLIED) {
                    $returned[$line->product_id] = ($returned[$line->product_id] ?? 0) + $qty;
                } elseif ($btb->status === BtbDistribusi::STATUS_DRAFT) {
                    $pending[$line->product_id] = ($pending[$line->product_id] ?? 0) + $qty;
                }
                // Discrepancy: stok tidak berpindah -> tidak dihitung sebagai
                // kembali/menunggu; barangnya masih di Sales (lihat unreturned).
            }
        }

        $live = Stock::with('product')
            ->where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $settlement->sales_id)
            ->where('quantity', '>', 0)
            ->get()
            ->keyBy('product_id');

        foreach ($live as $productId => $stock) {
            $products[$productId] ??= $stock->product;
        }

        $rows = collect($products)->map(function ($product, $productId) use ($returned, $pending, $live) {
            $liveQty = (float) ($live[$productId]->quantity ?? 0);
            $pendingQty = round($pending[$productId] ?? 0, 2);

            return [
                'product_id' => (int) $productId,
                'product' => $product,
                'returned' => round($returned[$productId] ?? 0, 2),
                'pending' => $pendingQty,
                'unreturned' => round(max(0, $liveQty - $pendingQty), 2),
            ];
        })
            ->filter(fn ($r) => $r['returned'] > 0 || $r['pending'] > 0 || $r['unreturned'] > 0)
            ->sortBy(fn ($r) => $r['product']->name ?? '')
            ->values();

        $pendingBtbs = $btbs->where('status', BtbDistribusi::STATUS_DRAFT)->values();

        return [
            'rows' => $rows,
            'btbs' => $btbs,
            'pending_btbs' => $pendingBtbs,
            'has_pending' => $pendingBtbs->isNotEmpty(),
            'total_unreturned' => round($rows->sum('unreturned'), 2),
        ];
    }

    /**
     * Apply Settlement: HANYA menyelesaikan uang dan mencatat status barang
     * sebagai catatan final. Tidak ada perpindahan stok di sini -- itu
     * sepenuhnya tugas BTB. Ditolak selama masih ada BTB yang menunggu.
     */
    public function apply(
        Settlement $settlement,
        float $cashDeposited,
        ?int $appliedByUserId,
        ?string $notes = null,
    ): Settlement {
        return DB::transaction(function () use ($settlement, $cashDeposited, $appliedByUserId, $notes) {
            /** @var Settlement $settlement */
            $settlement = Settlement::lockForUpdate()->findOrFail($settlement->id);

            if (! $settlement->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Settlement ini sudah di-Apply sebelumnya.']);
            }

            if ($cashDeposited < 0) {
                throw ValidationException::withMessages(['cash_deposited' => 'Jumlah uang disetor tidak boleh negatif.']);
            }

            $pendingCodes = BtbDistribusi::where('settlement_id', $settlement->id)
                ->where('status', BtbDistribusi::STATUS_DRAFT)
                ->orderBy('id')
                ->pluck('code');

            if ($pendingCodes->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Masih ada BTB yang belum di-Apply: '.$pendingCodes->implode(', ').'. Apply BTB-nya dulu agar barang kembali ke Warehouse, lalu Apply Settlement.',
                ]);
            }

            $before = $settlement->toArray();

            // Catatan final status barang (turunan BTB + sisa stok Sales).
            SettlementItem::where('settlement_id', $settlement->id)->delete();

            foreach ($this->goodsSummary($settlement)['rows'] as $row) {
                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'product_id' => $row['product_id'],
                    'system_qty' => round($row['returned'] + $row['unreturned'], 2),
                    'returned_qty' => $row['returned'],
                    'variance_qty' => $row['unreturned'],
                ]);
            }

            $cashVariance = round($cashDeposited - (float) $settlement->cash_expected, 2);

            $settlement->update([
                'cash_deposited' => $cashDeposited,
                'cash_variance' => $cashVariance,
                'status' => Settlement::STATUS_APPLIED,
                'applied_by' => $appliedByUserId,
                'applied_at' => now(),
                'notes' => $notes,
            ]);

            AuditLogger::log(
                action: 'apply',
                module: 'Finance',
                documentType: Settlement::class,
                documentId: $settlement->id,
                before: $before,
                after: $settlement->fresh(['items'])->toArray(),
                userId: $appliedByUserId,
            );

            return $settlement->fresh(['items.product', 'payments', 'sales', 'warehouse']);
        });
    }

    /**
     * Batalkan Settlement Draft (belum di-Apply) - lepas kembali
     * reservasi Payment dan klaim BTB supaya bisa masuk Settlement berikutnya.
     */
    public function discardDraft(Settlement $settlement, ?int $byUserId): void
    {
        DB::transaction(function () use ($settlement, $byUserId) {
            $settlement = Settlement::lockForUpdate()->findOrFail($settlement->id);

            if (! $settlement->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Hanya Settlement berstatus Draft yang bisa dibatalkan.']);
            }

            Payment::where('settlement_id', $settlement->id)->update(['settlement_id' => null]);
            BtbDistribusi::where('settlement_id', $settlement->id)->update(['settlement_id' => null]);

            AuditLogger::log(
                action: 'discard',
                module: 'Finance',
                documentType: Settlement::class,
                documentId: $settlement->id,
                before: $settlement->toArray(),
                userId: $byUserId,
            );

            $settlement->delete();
        });
    }

    private function claimBtbs(Collection $btbs, Settlement $settlement): void
    {
        foreach ($btbs as $btb) {
            $btb->update(['settlement_id' => $settlement->id]);
        }
    }
}
