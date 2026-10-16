<?php

namespace App\Services;

use App\Models\Category;
use App\Models\SalesTransaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Memecah Effective Call (EC) menjadi dua jenis:
 *
 *  - EC Toko Baru   : EC di hari yang SAMA dengan transaksi selesai PERTAMA
 *                     toko itu (toko baru pertama kali order).
 *  - EC Toko Repeat : EC pada toko yang sebelumnya (hari lebih awal) sudah
 *                     pernah punya transaksi selesai (repeat order).
 *
 * Acuan "pertama kali order" = transaksi selesai paling awal toko tersebut
 * (siapa pun Sales-nya), bukan hanya milik Sales yang sedang dilihat.
 * Transaksi dibatalkan tidak dihitung. Toko yang order lebih dari sekali di
 * hari pertamanya tetap dihitung "baru" untuk seluruh EC di hari itu.
 */
class EffectiveCallService
{
    /** @var array<int, int>|null */
    private ?array $ecCategoryIds = null;

    /**
     * Kategori penentu EC (mis. Rokok). Kosong = belum diatur, maka semua
     * transaksi selesai tetap dihitung (perilaku lama) agar EC tidak hilang.
     *
     * @return array<int, int>
     */
    public function categoryIds(): array
    {
        return $this->ecCategoryIds ??= Category::query()
            ->where('counts_as_ec', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Batasi query SalesTransaction ke transaksi yang memenuhi syarat EC:
     * memuat minimal 1 produk (bukan barang gratis) dari kategori penentu EC.
     * Transaksi hanya-kopi tidak lolos. Tanpa kategori penentu = tidak difilter.
     */
    public function onlyEffective(Builder $query): Builder
    {
        $ids = $this->categoryIds();

        if ($ids === []) {
            return $query;
        }

        return $query->whereHas('items', fn ($q) => $q
            ->where('is_free', false)
            ->whereHas('product', fn ($p) => $p->whereIn('category_id', $ids)));
    }

    /**
     * Versi untuk transaksi yang sudah dimuat (wajib with 'items.product').
     */
    public function isEffective(SalesTransaction $trx): bool
    {
        $ids = $this->categoryIds();

        if ($ids === []) {
            return true;
        }

        return $trx->items->contains(
            fn ($item) => ! $item->is_free && in_array((int) ($item->product?->category_id), $ids, true)
        );
    }

    /**
     * Transaksi yang diinput Admin/Superadmin atas nama Sales (stok Sales terpotong)
     * dan memenuhi syarat EC. Setiap toko-hari dari transaksi ini dihitung sebagai
     * 1 Call Made + 1 EC untuk Sales tersebut, walau Sales tidak check-in lagi di hari
     * itu (mis. toko menelepon minta tambahan barang). Data Visit tidak dibuat/diubah,
     * jadi kuota kunjungan mingguan toko tidak terpengaruh. Toko-hari yang sudah punya
     * kunjungan tidak terhitung dobel karena kuncinya digabung (union).
     *
     * @param  \DateTimeInterface  $from
     * @param  \DateTimeInterface  $to
     */
    public function assistedCallsQuery($from, $to): Builder
    {
        return $this->onlyEffective(
            SalesTransaction::query()
                ->where('source', SalesTransaction::SOURCE_ADMIN)
                ->whereNotNull('sales_id')
                ->where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereBetween('created_at', [$from, $to])
        );
    }

    /**
     * Hari (Y-m-d) transaksi selesai pertama tiap toko.
     *
     * @param  array<int, int|string>  $customerIds
     * @return array<int, string> [customer_id => 'Y-m-d']
     */
    public function firstOrderDays(array $customerIds): array
    {
        $customerIds = array_values(array_unique(array_filter($customerIds)));
        if ($customerIds === []) {
            return [];
        }

        $result = [];
        foreach (array_chunk($customerIds, 1000) as $chunk) {
            SalesTransaction::query()
                ->where('status', SalesTransaction::STATUS_COMPLETED)
                ->whereIn('customer_id', $chunk)
                ->groupBy('customer_id')
                ->selectRaw('customer_id, MIN(DATE(created_at)) as first_day')
                ->get()
                ->each(function ($row) use (&$result) {
                    $result[(int) $row->customer_id] = (string) $row->first_day;
                });
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $firstDays  hasil firstOrderDays()
     */
    public function isNewStore(int $customerId, string $day, array $firstDays): bool
    {
        // Tidak ada riwayat transaksi selesai sama sekali berarti hari ini
        // pun bukan EC; dianggap baru agar aman.
        return ! isset($firstDays[$customerId]) || $firstDays[$customerId] >= substr($day, 0, 10);
    }

    /**
     * Pisahkan kumpulan kunci EC ("[sales|]customer|Y-m-d") menjadi
     * jumlah EC baru & repeat.
     *
     * @param  iterable<int, string>  $ecKeys
     * @return array{new:int,repeat:int}
     */
    public function split(iterable $ecKeys, ?array $firstDays = null): array
    {
        $parsed = [];
        foreach ($ecKeys as $key) {
            // Kunci berakhiran "customer|Y-m-d" (boleh diawali "sales|").
            $parts = explode('|', $key);
            $day = array_pop($parts);
            $customerId = array_pop($parts);
            $parsed[] = [(int) $customerId, $day];
        }

        $firstDays ??= $this->firstOrderDays(array_column($parsed, 0));

        $new = 0;
        $repeat = 0;
        foreach ($parsed as [$customerId, $day]) {
            $this->isNewStore($customerId, $day, $firstDays) ? $new++ : $repeat++;
        }

        return ['new' => $new, 'repeat' => $repeat];
    }
}
