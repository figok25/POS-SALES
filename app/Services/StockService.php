<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3 - Stock Movement / Stock Management (Blueprint #7, #10).
 *
 * Ini adalah SATU-SATUNYA jalan resmi untuk mengubah quantity di tabel
 * `stocks`. Modul bisnis pada fase berikutnya (BKB Apply, BTB Apply,
 * Branch Transfer, Sales Transaction, dst.) WAJIB memanggil service ini,
 * tidak boleh mengubah tabel stocks secara langsung.
 *
 * Setiap perubahan selalu:
 * - Berjalan dalam DB transaction dengan row lock (mencegah race condition
 *   pada transaksi konkuren, lihat Blueprint #43 - Concurrent transaction).
 * - Menghasilkan baris stock_movements sebagai jejak audit.
 * - Melempar InsufficientStockException bila stok tidak cukup, sehingga
 *   proses pemanggil (mis. Sales Transaction) bisa rollback dengan bersih.
 */
class StockService
{
    /**
     * Tambah stok di suatu lokasi. Dipakai misalnya oleh: BKB Apply
     * (menambah Sales Stock), BTB Apply (menambah Warehouse Stock),
     * Stock Adjustment tipe "in".
     */
    public function increase(
        int $productId,
        string $locationType,
        int $locationId,
        float $quantity,
        string $movementType,
        ?string $documentType = null,
        ?int $documentId = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use ($productId, $locationType, $locationId, $quantity, $movementType, $documentType, $documentId, $notes) {
            $stock = $this->lockOrCreateStock($productId, $locationType, $locationId);

            $stock->quantity = round((float) $stock->quantity + $quantity, 2);
            $stock->save();

            return StockMovement::create([
                'product_id' => $productId,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'direction' => 'in',
                'quantity' => $quantity,
                'balance_after' => $stock->quantity,
                'movement_type' => $movementType,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'user_id' => Auth::id(),
                'notes' => $notes,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Kurangi stok di suatu lokasi. Melempar InsufficientStockException
     * bila stok yang tersedia tidak cukup.
     */
    public function decrease(
        int $productId,
        string $locationType,
        int $locationId,
        float $quantity,
        string $movementType,
        ?string $documentType = null,
        ?int $documentId = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositiveQuantity($quantity);

        return DB::transaction(function () use ($productId, $locationType, $locationId, $quantity, $movementType, $documentType, $documentId, $notes) {
            $stock = $this->lockOrCreateStock($productId, $locationType, $locationId);

            if (round((float) $stock->quantity, 2) < round($quantity, 2)) {
                $product = Product::find($productId);
                throw InsufficientStockException::make(
                    $product->name ?? "Product #{$productId}",
                    ucfirst($locationType)." #{$locationId}",
                    (float) $stock->quantity,
                    $quantity,
                );
            }

            $stock->quantity = round((float) $stock->quantity - $quantity, 2);
            $stock->save();

            return StockMovement::create([
                'product_id' => $productId,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'direction' => 'out',
                'quantity' => $quantity,
                'balance_after' => $stock->quantity,
                'movement_type' => $movementType,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'user_id' => Auth::id(),
                'notes' => $notes,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Pindahkan stok antar lokasi dalam satu transaction (mis. BKB
     * Distribusi: Warehouse -> Sales, BTB: Sales -> Warehouse, Branch
     * Transfer: Warehouse Cabang A -> Warehouse Cabang B).
     *
     * @return array{0: StockMovement, 1: StockMovement} [keluar, masuk]
     */
    public function transfer(
        int $productId,
        string $fromLocationType,
        int $fromLocationId,
        string $toLocationType,
        int $toLocationId,
        float $quantity,
        string $movementType,
        ?string $documentType = null,
        ?int $documentId = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($productId, $fromLocationType, $fromLocationId, $toLocationType, $toLocationId, $quantity, $movementType, $documentType, $documentId, $notes) {
            $out = $this->decrease($productId, $fromLocationType, $fromLocationId, $quantity, $movementType, $documentType, $documentId, $notes);
            $in = $this->increase($productId, $toLocationType, $toLocationId, $quantity, $movementType, $documentType, $documentId, $notes);

            return [$out, $in];
        });
    }

    /**
     * Ambil quantity stok saat ini (0 bila belum pernah ada record).
     */
    public function getQuantity(int $productId, string $locationType, int $locationId): float
    {
        return (float) (Stock::where('product_id', $productId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->value('quantity') ?? 0);
    }

    /**
     * Ambil baris stock dengan row lock (harus dipanggil di dalam
     * DB::transaction). Membuat baris baru dengan quantity 0 bila belum ada.
     */
    protected function lockOrCreateStock(int $productId, string $locationType, int $locationId): Stock
    {
        $stock = Stock::firstOrCreate(
            [
                'product_id' => $productId,
                'location_type' => $locationType,
                'location_id' => $locationId,
            ],
            ['quantity' => 0]
        );

        return Stock::where('id', $stock->id)->lockForUpdate()->first();
    }

    protected function assertPositiveQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity pergerakan stok harus lebih besar dari 0.');
        }
    }
}
