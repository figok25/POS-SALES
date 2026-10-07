<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\PromoItem;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Support\Geo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6 - Kunjungan / Visit (Blueprint #12.4).
 * Phase 6 Hardening - check-in/check-out diaudit (Blueprint #45: aktivitas
 * lapangan Sales termasuk yang wajib tercatat di audit_logs).
 */
class VisitService
{
    /**
     * Sales melakukan check-in ke sebuah toko/customer. Sales tidak
     * boleh check-in ganda selagi kunjungan sebelumnya masih berjalan
     * (Blueprint #38 - satu aktivitas jelas per waktu).
     *
     * PERBAIKAN AUDIT (item B - BLOCKER BISNIS PALING PENTING): sebelumnya
     * TIDAK ADA validasi jarak GPS sama sekali di sini, jadi Sales bisa
     * check-in dari mana saja. Sekarang wajib dalam radius
     * config('sales.check_in_radius_meters') dari titik lokasi Customer.
     */
    public function checkIn(int $salesId, int $customerId, array $data): Visit
    {
        $ongoing = Visit::where('sales_id', $salesId)->where('status', Visit::STATUS_ONGOING)->exists();

        if ($ongoing) {
            throw ValidationException::withMessages([
                'customer_id' => 'Anda masih memiliki kunjungan yang berjalan. Selesaikan (Check-out) kunjungan sebelumnya terlebih dahulu.',
            ]);
        }

        $customer = Customer::findOrFail($customerId);

        // PERBAIKAN AUDIT #16: Customer Assignment adalah authorization/business
        // relationship (bukan cuma penanda), jadi Sales HARUS divalidasi benar-benar
        // ditugaskan ke Customer ini sebelum boleh check-in - bukan sekadar bisa
        // check-in ke Customer manapun yang ID-nya diketahui.
        $isAssigned = $customer->sales_id === $salesId
            || CustomerAssignment::where('customer_id', $customerId)
                ->where('sales_id', $salesId)
                ->whereNull('unassigned_at')
                ->exists();

        if (! $isAssigned) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer ini tidak termasuk dalam assignment Anda. Hubungi Admin jika ini seharusnya milik Anda.',
            ]);
        }

        $this->assertDailyQuota($salesId, $customerId);

        if (! $customer->hasLocation()) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer belum punya titik lokasi, hubungi Admin.',
            ]);
        }

        $distance = Geo::distanceMeters(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $customer->latitude,
            (float) $customer->longitude,
        );

        $radius = (int) config('sales.check_in_radius_meters', 150);

        if ($distance > $radius) {
            throw ValidationException::withMessages([
                'latitude' => sprintf(
                    'Lokasi Anda terlalu jauh dari Customer (%d meter, maksimal %d meter). Mendekatlah ke lokasi Customer untuk check-in.',
                    round($distance),
                    $radius,
                ),
            ]);
        }

        // Promosi/POSM: Sales mencentang item yang ADA; item aktif yang tidak
        // dicentang dicatat "Tidak ada". Dicatat untuk SEMUA kondisi outlet
        // (toko tutup pun POSM-nya tetap dicatat, sesuai keputusan bisnis).
        $presentIds = collect($data['promo_items'] ?? [])->map(fn ($id) => (int) $id)->all();

        $visit = DB::transaction(function () use ($salesId, $customerId, $data, $presentIds) {
            $visit = Visit::create([
                'sales_id' => $salesId,
                'customer_id' => $customerId,
                'check_in_at' => now(),
                'check_in_latitude' => $data['latitude'] ?? null,
                'check_in_longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
                'check_in_condition' => $data['condition'] ?? null,
                'facility_notes' => $data['facility_notes'] ?? null,
                'status' => Visit::STATUS_ONGOING,
            ]);

            $answers = [];
            foreach (PromoItem::active()->pluck('id') as $itemId) {
                $answers[$itemId] = ['is_present' => in_array((int) $itemId, $presentIds, true)];
            }
            $visit->promoItems()->sync($answers);

            return $visit;
        });

        AuditLogger::log('check_in', 'Sales', Visit::class, $visit->id, null, $visit->toArray());

        return $visit;
    }

    /**
     * Kuota kunjungan: SATU kali per toko per hari (per Sales). Kunjungan yang
     * hasilnya "Toko Tutup" TIDAK menghabiskan kuota: Sales boleh kunjungan ulang
     * satu kali di hari yang sama. Kalau kunjungan ulang itu pun tutup (atau
     * kunjungan pertama bukan tutup), kuota habis sampai besok.
     */
    private function assertDailyQuota(int $salesId, int $customerId): void
    {
        $visits = Visit::query()
            ->where('sales_id', $salesId)
            ->where('customer_id', $customerId)
            ->whereDate('check_in_at', now()->toDateString())
            ->orderBy('id')
            ->get(['id', 'customer_id', 'check_in_condition']);

        if (self::quotaAllows($visits)) {
            return;
        }

        throw ValidationException::withMessages([
            'customer_id' => $visits->count() >= 2
                ? 'Kuota kunjungan toko ini hari ini sudah habis (kunjungan ulang setelah Toko Tutup sudah dipakai).'
                : 'Toko ini sudah Anda kunjungi hari ini. Kunjungan ulang hanya diizinkan bila kunjungan sebelumnya Toko Tutup.',
        ]);
    }

    /** Boleh check-in lagi? (belum ada kunjungan hari ini, atau satu-satunya kunjungan = Toko Tutup) */
    private static function quotaAllows(Collection $visitsToday): bool
    {
        return $visitsToday->isEmpty()
            || ($visitsToday->count() === 1 && $visitsToday->first()->check_in_condition === Visit::CONDITION_CLOSED);
    }

    /**
     * ID Customer yang kuota kunjungannya hari ini sudah habis, supaya daftar
     * toko di form Check-in tidak menawarkan toko yang pasti ditolak.
     *
     * @return array<int, int>
     */
    public function quotaExhaustedCustomerIds(int $salesId): array
    {
        return Visit::query()
            ->where('sales_id', $salesId)
            ->whereDate('check_in_at', now()->toDateString())
            ->orderBy('id')
            ->get(['id', 'customer_id', 'check_in_condition'])
            ->groupBy('customer_id')
            ->reject(fn (Collection $visits) => self::quotaAllows($visits))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Transaksi hanya boleh dibuat saat Sales SEDANG berkunjung (sudah Check-in)
     * di toko yang sama, dan toko itu tidak tercatat tutup pada kunjungan ini.
     * Dipakai oleh jalur transaksi Sales di lapangan; transaksi Admin (mis. ke
     * Konsumen) memang tidak lewat sini.
     */
    public function assertCanTransact(int $salesId, int $customerId): Visit
    {
        $visit = Visit::query()
            ->where('sales_id', $salesId)
            ->where('status', Visit::STATUS_ONGOING)
            ->with('customer')
            ->first();

        if (! $visit) {
            throw ValidationException::withMessages([
                'customer_id' => 'Lakukan Check-in kunjungan ke toko ini terlebih dahulu sebelum membuat transaksi.',
            ]);
        }

        if ((int) $visit->customer_id !== $customerId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Anda sedang berkunjung ke '.($visit->customer->name ?? 'toko lain').'. Transaksi hanya bisa dibuat untuk toko yang sedang dikunjungi.',
            ]);
        }

        if ($visit->isOutletClosed()) {
            throw ValidationException::withMessages([
                'customer_id' => 'Toko tercatat tutup pada kunjungan ini, jadi transaksi tidak dapat dibuat.',
            ]);
        }

        return $visit;
    }

    public function checkOut(Visit $visit, array $data): Visit
    {
        if (! $visit->isOngoing()) {
            throw ValidationException::withMessages([
                'visit' => 'Kunjungan ini sudah selesai.',
            ]);
        }

        $before = $visit->toArray();

        // RevisiMinor #5: keterangan check-out disimpan terpisah di
        // check_out_notes. Sebelumnya `notes` ditimpa di sini, sehingga
        // alasan/kondisi yang dicatat saat check-in ikut hilang.
        $visit->update([
            'check_out_at' => now(),
            'check_out_latitude' => $data['latitude'] ?? null,
            'check_out_longitude' => $data['longitude'] ?? null,
            'check_out_notes' => $data['notes'] ?? null,
            'status' => Visit::STATUS_COMPLETED,
        ]);

        AuditLogger::log('check_out', 'Sales', Visit::class, $visit->id, $before, $visit->fresh()->toArray());

        return $visit;
    }
}
