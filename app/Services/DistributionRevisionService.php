<?php

namespace App\Services;

use App\Models\BkbDistribusi;
use App\Models\BtbDistribusi;
use App\Models\SalesTask;
use App\Models\SalesTaskStock;
use App\Models\Settlement;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Revisi & pembatalan BKB/BTB oleh Admin/Superadmin.
 *
 *  - Draft/Discrepancy : item boleh diganti bebas, stok TIDAK tersentuh.
 *  - Applied           : stok disesuaikan dengan SELISIH item lama vs baru
 *                        (bukan mengulang pergerakan dari awal), semuanya lewat
 *                        StockService dalam satu transaction + row lock.
 *      BKB (Warehouse -> Sales): qty naik = pindah lagi Warehouse->Sales sebesar
 *            selisih; qty turun / barang dihapus = kembalikan Sales->Warehouse.
 *      BTB (Sales -> Warehouse): arah kebalikannya.
 *  - Ganti jenis barang = barang lama dikoreksi penuh, barang baru dipindah penuh.
 *  - Setiap revisi/pembatalan masuk audit log (sebelum, sesudah, alasan, dampak stok).
 */
class DistributionRevisionService
{
    public function __construct(protected StockService $stock) {}

    /**
     * @param  array<int, array{product_id:int|string, quantity:float|int|string}>  $items
     * @return array<int, array{product_id:int, old:float, new:float, delta:float}> dampak per produk
     */
    public function reviseBkb(BkbDistribusi $bkb, array $items, ?string $notes, ?string $documentDate, string $reason): array
    {
        return $this->revise($bkb, 'bkb', $items, $notes, $documentDate, $reason);
    }

    public function reviseBtb(BtbDistribusi $btb, array $items, ?string $notes, ?string $documentDate, string $reason): array
    {
        return $this->revise($btb, 'btb', $items, $notes, $documentDate, $reason);
    }

    /** Batalkan BKB yang sudah Applied: barang dikembalikan dari Sales ke Warehouse. */
    public function voidBkb(BkbDistribusi $bkb, string $reason): void
    {
        $this->void($bkb, 'bkb', $reason);
    }

    /** Batalkan BTB yang sudah Applied: barang dikembalikan dari Warehouse ke Sales. */
    public function voidBtb(BtbDistribusi $btb, string $reason): void
    {
        $this->void($btb, 'btb', $reason);
    }

    private function revise(Model $doc, string $kind, array $items, ?string $notes, ?string $documentDate, string $reason): array
    {
        return DB::transaction(function () use ($doc, $kind, $items, $notes, $documentDate, $reason) {
            $class = $doc::class;
            $locked = $class::query()->whereKey($doc->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                throw new \DomainException('Dokumen yang sudah dibatalkan tidak dapat direvisi.');
            }

            $locked->load(['items', 'warehouse']);
            $before = $this->snapshot($locked);

            $old = $this->qtyMap($locked->items->map(fn ($i) => ['product_id' => $i->product_id, 'quantity' => $i->quantity])->all());
            $new = $this->qtyMap($items);

            if ($new === []) {
                throw new \DomainException('Minimal harus ada 1 item dengan jumlah lebih dari 0.');
            }

            $impact = $this->deltas($old, $new);
            $stockApplied = $locked->status === 'applied';

            if ($stockApplied) {
                $this->moveDeltas($impact, $kind, $locked, $reason);
            }

            $locked->items()->delete();
            foreach ($new as $productId => $qty) {
                $locked->items()->create(['product_id' => $productId, 'quantity' => $qty]);
            }

            // Daftar "Verifikasi Stock" di aplikasi Sales dibaca dari sales_task_stocks
            // (snapshot saat Task dibuat). Tanpa sinkronisasi, revisi BKB tidak
            // tampil di layar Sales.
            $taskSync = $kind === 'bkb' ? $this->syncTaskStocks($locked, $old, $new) : null;

            $locked->update(array_filter([
                'document_date' => $documentDate,
            ], fn ($v) => $v !== null) + [
                'notes' => $notes,
                'revision_count' => (int) $locked->revision_count + 1,
                'last_revised_by' => Auth::id(),
                'last_revised_at' => now(),
                'revision_reason' => $reason,
            ]);

            $after = $this->snapshot($locked->fresh(['items', 'warehouse']));
            $after['reason'] = $reason;
            $after['stock_applied'] = $stockApplied;
            $after['stock_impact'] = $impact;
            if ($taskSync !== null) {
                $after['task_sync'] = $taskSync;
            }

            AuditLogger::log('revise', 'Distribution', $class, $locked->id, $before, $after);

            return $impact;
        });
    }

    /**
     * Samakan sales_task_stocks milik Sales Task BKB ini dengan item BKB hasil revisi.
     *
     *  - Task belum diverifikasi Sales (applied/document_available/stock_verification/
     *    stock_variance): daftar dibangun ulang, verifikasi dikosongkan supaya Sales
     *    memverifikasi angka yang BARU. Task yang sedang verifikasi/selisih kembali
     *    ke "document_available".
     *  - Task sudah siap/berjalan (ready_to_work/working): angka "dari Admin" diganti,
     *    hasil verifikasi dipertahankan (bila tadinya sama dengan angka lama, ikut
     *    angka baru).
     *  - Task selesai/batal: tidak disentuh.
     *
     * @param  array<int, float>  $old  product_id => qty sebelum revisi
     * @param  array<int, float>  $new  product_id => qty sesudah revisi
     * @return array{task_id:int, mode:string}|null
     */
    private function syncTaskStocks(Model $bkb, array $old, array $new): ?array
    {
        $task = SalesTask::query()->where('bkb_distribusi_id', $bkb->id)->lockForUpdate()->first();

        if (! $task || in_array($task->status, [SalesTask::STATUS_COMPLETED, SalesTask::STATUS_CANCELLED], true)) {
            return null;
        }

        if (in_array($task->status, [SalesTask::STATUS_READY_TO_WORK, SalesTask::STATUS_WORKING], true)) {
            $existing = $task->taskStocks()->get()->keyBy('product_id');

            foreach ($existing as $productId => $line) {
                if (! isset($new[$productId])) {
                    $line->delete();
                    continue;
                }
                $wasSame = $line->quantity_verified !== null
                    && round((float) $line->quantity_verified, 2) === round((float) $line->quantity_assigned, 2);
                $line->update([
                    'quantity_assigned' => $new[$productId],
                    'quantity_verified' => $wasSame ? $new[$productId] : $line->quantity_verified,
                ]);
            }
            foreach ($new as $productId => $qty) {
                if (! $existing->has($productId)) {
                    SalesTaskStock::create([
                        'sales_task_id' => $task->id, 'product_id' => $productId,
                        'quantity_assigned' => $qty, 'quantity_verified' => $qty, 'verified_at' => now(),
                    ]);
                }
            }

            return ['task_id' => $task->id, 'mode' => 'updated_in_place'];
        }

        $task->taskStocks()->delete();
        foreach ($new as $productId => $qty) {
            SalesTaskStock::create(['sales_task_id' => $task->id, 'product_id' => $productId, 'quantity_assigned' => $qty]);
        }
        if (in_array($task->status, [SalesTask::STATUS_STOCK_VERIFICATION, SalesTask::STATUS_STOCK_VARIANCE], true)) {
            $task->update(['status' => SalesTask::STATUS_DOCUMENT_AVAILABLE]);
        }

        return ['task_id' => $task->id, 'mode' => 'rebuilt'];
    }

    private function void(Model $doc, string $kind, string $reason): void
    {
        DB::transaction(function () use ($doc, $kind, $reason) {
            $class = $doc::class;
            $locked = $class::query()->whereKey($doc->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'applied') {
                throw new \DomainException('Hanya dokumen berstatus Applied yang dapat dibatalkan lewat koreksi stok.');
            }

            if ($kind === 'bkb' && $locked->salesTask()->exists()) {
                throw new \DomainException('BKB ini sudah dipakai Sales Task. Batalkan Sales Task-nya dulu sebelum membatalkan BKB.');
            }

            if ($kind === 'btb' && $locked->settlement_id
                && Settlement::query()->whereKey($locked->settlement_id)->where('status', Settlement::STATUS_APPLIED)->exists()) {
                throw new \DomainException('BTB ini sudah masuk Settlement yang Applied, tidak bisa dibatalkan. Gunakan Revisi atau koreksi Settlement.');
            }

            $locked->load(['items', 'warehouse']);
            $before = $this->snapshot($locked);

            $impact = [];
            foreach ($locked->items as $line) {
                $qty = round((float) $line->quantity, 2);
                $impact[] = ['product_id' => (int) $line->product_id, 'old' => $qty, 'new' => 0.0, 'delta' => -$qty];
            }
            $this->moveDeltas($impact, $kind, $locked, $reason, "{$kind}_void");

            $locked->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $after = $this->snapshot($locked->fresh(['items', 'warehouse']));
            $after['reason'] = $reason;
            $after['stock_applied'] = true;
            $after['stock_impact'] = $impact;

            AuditLogger::log('void', 'Distribution', $class, $locked->id, $before, $after);
        });
    }

    /**
     * Pindahkan stok sebesar selisih. delta > 0 = searah dokumen asli,
     * delta < 0 = kebalikannya (mengembalikan).
     */
    private function moveDeltas(array $impact, string $kind, Model $doc, string $reason, ?string $movementType = null): void
    {
        $movementType ??= "{$kind}_revision";

        [$fromType, $fromId, $toType, $toId] = $kind === 'bkb'
            ? [Stock::LOCATION_WAREHOUSE, $doc->warehouse_id, Stock::LOCATION_SALES, $doc->sales_id]
            : [Stock::LOCATION_SALES, $doc->sales_id, Stock::LOCATION_WAREHOUSE, $doc->warehouse_id];

        $label = strtoupper($kind);
        $note = "Revisi {$label} {$doc->code}: {$reason}";

        // Kembalikan dulu (membebaskan stok), baru tambah kirim.
        usort($impact, fn ($a, $b) => $a['delta'] <=> $b['delta']);

        foreach ($impact as $row) {
            if ($row['delta'] == 0.0) {
                continue;
            }

            if ($row['delta'] > 0) {
                $this->stock->transfer($row['product_id'], $fromType, $fromId, $toType, $toId, $row['delta'], $movementType, $doc::class, $doc->id, $note);
            } else {
                $this->stock->transfer($row['product_id'], $toType, $toId, $fromType, $fromId, abs($row['delta']), $movementType, $doc::class, $doc->id, $note);
            }
        }
    }

    /** @return array<int, float> product_id => total qty (>0) */
    private function qtyMap(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = round((float) ($item['quantity'] ?? 0), 2);
            if ($productId > 0 && $qty > 0) {
                $map[$productId] = round(($map[$productId] ?? 0) + $qty, 2);
            }
        }

        return $map;
    }

    private function deltas(array $old, array $new): array
    {
        $impact = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $productId) {
            $o = $old[$productId] ?? 0.0;
            $n = $new[$productId] ?? 0.0;
            $impact[] = ['product_id' => (int) $productId, 'old' => (float) $o, 'new' => (float) $n, 'delta' => round($n - $o, 2)];
        }

        return $impact;
    }

    private function snapshot(Model $doc): array
    {
        return [
            'branch_id' => $doc->warehouse?->branch_id,
            'code' => $doc->code,
            'status' => $doc->status,
            'sales_id' => $doc->sales_id,
            'warehouse_id' => $doc->warehouse_id,
            'notes' => $doc->notes,
            'document_date' => $doc->document_date?->toDateString(),
            'items' => $doc->items->map(fn ($i) => ['product_id' => (int) $i->product_id, 'quantity' => (float) $i->quantity])->values()->all(),
        ];
    }
}
