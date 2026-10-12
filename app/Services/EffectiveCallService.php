<?php

namespace App\Services;

use App\Models\SalesTransaction;

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
