<?php

namespace App\Services;

use App\Models\BuyGetPromo;
use App\Models\Sales;
use App\Models\Stock;

/**
 * Menghitung barang gratis program "Beli X Gratis Y" untuk satu transaksi Sales.
 *
 * Aturan:
 * - Berlaku kelipatan (beli 3 dengan aturan beli 1 gratis 1 = gratis 3).
 * - Barang gratis diambil dari stok SALES. Stok yang dihitung adalah sisa stok
 *   setelah dikurangi barang yang sama yang dibeli di transaksi ini.
 * - Bila stok barang gratis tidak cukup, hadiah dikurangi sebesar stok yang ada
 *   (stok 0 = tidak dapat gratis). Transaksi tetap berjalan.
 */
class PromoService
{
    public function __construct(protected StockService $stockService) {}

    /**
     * @param  array<int, array{product: \App\Models\Product, quantity: float}>  $lines  baris yang DIBELI
     * @return array<int, array{promo: BuyGetPromo, product_id: int, quantity: float, wanted: float}>
     */
    public function freeLinesFor(int $salesId, array $lines): array
    {
        $bought = [];
        foreach ($lines as $line) {
            $id = $line['product']->id;
            $bought[$id] = ($bought[$id] ?? 0) + (float) $line['quantity'];
        }

        if (! $bought) {
            return [];
        }

        // Program berbeda tiap Depo: hanya program yang aktif di Depo Sales ini.
        $branchId = Sales::query()->whereKey($salesId)->value('branch_id');

        $promos = BuyGetPromo::query()
            ->runningOn()
            ->forBranch($branchId ? (int) $branchId : null)
            ->whereIn('buy_product_id', array_keys($bought))
            ->orderBy('id')
            ->get();

        // Sisa stok Sales per produk (dikurangi pembelian & hadiah yang sudah dialokasikan).
        $remaining = [];
        $available = function (int $productId) use (&$remaining, $salesId, $bought) {
            if (! array_key_exists($productId, $remaining)) {
                $remaining[$productId] = $this->stockService->getQuantity($productId, Stock::LOCATION_SALES, $salesId)
                    - ($bought[$productId] ?? 0);
            }

            return $remaining[$productId];
        };

        $result = [];

        foreach ($promos as $promo) {
            $wanted = $promo->rewardFor($bought[$promo->buy_product_id]);

            if ($wanted <= 0) {
                continue;
            }

            $give = min($wanted, max(0, floor($available($promo->free_product_id) * 100) / 100));

            if ($give <= 0) {
                continue;
            }

            $remaining[$promo->free_product_id] = $available($promo->free_product_id) - $give;

            $result[] = [
                'promo' => $promo,
                'product_id' => $promo->free_product_id,
                'quantity' => round($give, 2),
                'wanted' => $wanted,
            ];
        }

        return $result;
    }
}
