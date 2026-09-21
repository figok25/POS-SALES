<?php

namespace App\Services;

use App\Models\Payment;
use App\Services\AuditLogger;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\Stock;
use App\Support\DocumentCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 7 - Settlement (Blueprint #16): pertanggungjawaban Sales di
 * akhir hari/rute - uang hasil tagihan (cash) + sisa Sales Stock
 * (retur ke Warehouse), dengan selisih uang & barang tercatat otomatis.
 *
 * Mengikuti alur inti Blueprint #13 (Draft -> Apply -> Stock Movement,
 * rollback bersih bila gagal), pola yang sama dengan BKB/BTB.
 *
 * Payment (cash, belum ter-settlement) langsung "direservasi" ke
 * Settlement begitu Draft dibuat (bukan menunggu Apply) - supaya angka
 * cash_expected tidak berubah-ubah kalau ada Payment baru masuk di
 * antara Draft dibuat dan di-Apply, dan supaya tidak ada dua Settlement
 * yang berebut Payment yang sama. Kalau Draft dibatalkan
 * (discardDraft), reservasi ini dilepas kembali.
 */
class SettlementService
{
    public function __construct(protected StockService $stockService) {}

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

            $stocks = Stock::where('location_type', Stock::LOCATION_SALES)
                ->where('location_id', $salesId)
                ->where('quantity', '>', 0)
                ->get();

            foreach ($stocks as $stock) {
                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'product_id' => $stock->product_id,
                    'system_qty' => $stock->quantity,
                    'returned_qty' => 0,
                    'variance_qty' => $stock->quantity,
                ]);
            }

            AuditLogger::log(
                action: 'create',
                module: 'Finance',
                documentType: Settlement::class,
                documentId: $settlement->id,
                after: $settlement->fresh(['items', 'payments'])->toArray(),
                userId: $createdByUserId,
            );

            return $settlement->fresh(['items.product', 'payments', 'sales']);
        });
    }

    /**
     * @param  array<int, float|string>  $returnedQtyByProductId  [product_id => qty dikembalikan]
     */
    public function apply(
        Settlement $settlement,
        array $returnedQtyByProductId,
        float $cashDeposited,
        ?int $appliedByUserId,
        ?string $notes = null,
    ): Settlement {
        return DB::transaction(function () use ($settlement, $returnedQtyByProductId, $cashDeposited, $appliedByUserId, $notes) {
            /** @var Settlement $settlement */
            $settlement = Settlement::with('items')->lockForUpdate()->findOrFail($settlement->id);

            if (! $settlement->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Settlement ini sudah di-Apply sebelumnya.']);
            }

            if ($cashDeposited < 0) {
                throw ValidationException::withMessages(['cash_deposited' => 'Jumlah uang disetor tidak boleh negatif.']);
            }

            $before = $settlement->toArray();

            foreach ($settlement->items as $item) {
                $returned = round((float) ($returnedQtyByProductId[$item->product_id] ?? 0), 2);

                if ($returned < 0 || $returned > (float) $item->system_qty + 0.01) {
                    throw ValidationException::withMessages([
                        "returned_qty.{$item->product_id}" => "Qty retur untuk produk #{$item->product_id} tidak valid (maks {$item->system_qty}).",
                    ]);
                }

                if ($returned > 0) {
                    $this->stockService->transfer(
                        $item->product_id,
                        Stock::LOCATION_SALES,
                        $settlement->sales_id,
                        Stock::LOCATION_WAREHOUSE,
                        $settlement->warehouse_id,
                        $returned,
                        'settlement_return',
                        Settlement::class,
                        $settlement->id,
                    );
                }

                $item->update([
                    'returned_qty' => $returned,
                    'variance_qty' => round((float) $item->system_qty - $returned, 2),
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
     * reservasi Payment supaya bisa masuk Settlement berikutnya.
     */
    public function discardDraft(Settlement $settlement, ?int $byUserId): void
    {
        DB::transaction(function () use ($settlement, $byUserId) {
            $settlement = Settlement::lockForUpdate()->findOrFail($settlement->id);

            if (! $settlement->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Hanya Settlement berstatus Draft yang bisa dibatalkan.']);
            }

            Payment::where('settlement_id', $settlement->id)->update(['settlement_id' => null]);

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
}
