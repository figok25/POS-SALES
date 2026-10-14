<?php

namespace App\Services;

use App\Models\BuyGetPromo;
use App\Models\Sales;
use App\Models\Stock;
use Illuminate\Validation\ValidationException;

/**
 * Menghitung barang gratis program untuk satu transaksi Sales:
 *  - "Beli X Gratis Y": dari produk yang DIBELI di transaksi.
 *  - "Tukar Cangkang": dari jumlah cangkang (bungkus kosong) yang diserahkan outlet.
 *
 * Aturan:
 * - Program berbeda tiap Depo: hanya program yang aktif di Depo Sales.
 * - Berlaku kelipatan penuh (nilai tukar diatur Admin, mis. 8 cangkang = 1 gratis).
 * - Barang gratis diambil dari stok SALES. Stok yang dihitung adalah sisa stok
 *   setelah dikurangi barang yang sama yang dibeli di transaksi ini.
 * - Beli Gratis: bila stok barang gratis tidak cukup, hadiah dikurangi sebesar stok
 *   yang ada (stok 0 = tidak dapat gratis). Transaksi tetap berjalan.
 * - Tukar Cangkang: cangkang kurang dari nilai tukar, atau stok gratis habis,
 *   ditolak dengan pesan jelas (cangkang sudah diserahkan outlet, jangan hilang).
 */
class PromoService
{
    public function __construct(protected StockService $stockService) {}

    /**
     * @param  array<int, array{product: \App\Models\Product, quantity: float}>  $lines  baris yang DIBELI
     * @param  array<int, array{promo_id: int|string, quantity: float|string}>  $shells  cangkang yang diserahkan
     * @return array<int, array{promo: BuyGetPromo, product_id: int, quantity: float, wanted: float, shells?: float}>
     */
    public function freeLinesFor(int $salesId, array $lines, array $shells = []): array
    {
        $bought = [];
        foreach ($lines as $line) {
            $id = $line['product']->id;
            $bought[$id] = ($bought[$id] ?? 0) + (float) $line['quantity'];
        }

        $shellQty = [];
        foreach ($shells as $s) {
            $promoId = (int) ($s['promo_id'] ?? 0);
            $qty = (float) ($s['quantity'] ?? 0);
            if ($promoId > 0 && $qty > 0) {
                $shellQty[$promoId] = ($shellQty[$promoId] ?? 0) + $qty;
            }
        }

        if (! $bought && ! $shellQty) {
            return [];
        }

        // Program berbeda tiap Depo: hanya program yang aktif di Depo Sales ini.
        $branchId = Sales::query()->whereKey($salesId)->value('branch_id');
        $branchId = $branchId ? (int) $branchId : null;

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

        if ($bought) {
            $promos = BuyGetPromo::query()
                ->buyGet()
                ->runningOn()
                ->forBranch($branchId)
                ->whereIn('buy_product_id', array_keys($bought))
                ->orderBy('id')
                ->get();

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
        }

        if ($shellQty) {
            $promos = BuyGetPromo::query()
                ->shellExchange()
                ->runningOn()
                ->forBranch($branchId)
                ->whereIn('id', array_keys($shellQty))
                ->with(['buyProduct:id,name', 'freeProduct:id,name'])
                ->get()
                ->keyBy('id');

            foreach ($shellQty as $promoId => $qty) {
                $promo = $promos->get($promoId);

                if (! $promo) {
                    throw ValidationException::withMessages([
                        'shells' => 'Program Tukar Cangkang tidak berlaku di Depo Anda atau sudah tidak aktif.',
                    ]);
                }

                $wanted = $promo->rewardFor($qty);
                $rate = rtrim(rtrim(number_format((float) $promo->buy_qty, 2, '.', ''), '0'), '.');

                if ($wanted <= 0) {
                    throw ValidationException::withMessages([
                        'shells' => "Tukar cangkang {$promo->buyProduct?->name}: minimal {$rate} cangkang untuk mendapat gratis.",
                    ]);
                }

                $give = min($wanted, max(0, floor($available($promo->free_product_id) * 100) / 100));

                if ($give <= 0) {
                    throw ValidationException::withMessages([
                        'shells' => "Stok {$promo->freeProduct?->name} di Sales habis, tukar cangkang tidak bisa diberikan.",
                    ]);
                }

                $remaining[$promo->free_product_id] = $available($promo->free_product_id) - $give;

                $result[] = [
                    'promo' => $promo,
                    'product_id' => $promo->free_product_id,
                    'quantity' => round($give, 2),
                    'wanted' => $wanted,
                    'shells' => round($qty, 2),
                ];
            }
        }

        return $result;
    }
}
