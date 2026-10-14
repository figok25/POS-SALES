<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\PromoItem;
use App\Models\Sales;
use App\Models\User;
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

        $this->assertWeeklyQuota($salesId, $customerId);

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

        // Mulai kunjungan = kembali bekerja: istirahat yang masih terbuka otomatis diakhiri.
        \App\Models\SalesBreak::endOpenFor($salesId);

        AuditLogger::log('check_in', 'Sales', Visit::class, $visit->id, null, $visit->toArray());

        return $visit;
    }

    /**
     * Check-in BANTUAN oleh Admin/Superadmin atas nama Sales (mis. Sales lupa
     * check-in atau GPS bermasalah). TIDAK ada batas radius 150 m dan tidak ada
     * lokasi GPS. Kunjungan dicatat selesai pada waktu kunjungan sebenarnya
     * ($visitAt) -- terpisah dari recorded_at (waktu data dimasukkan) -- sehingga
     * ikut Call Made/EC pada tanggal kunjungan itu. Check-in/transaksi asli Sales
     * tidak diubah; kunjungan ganda (Sales + toko + hari yang sama) ditolak.
     */
    public function assistedCheckIn(User $actor, Sales $sales, int $customerId, \Carbon\Carbon $visitAt, string $condition, string $reason): Visit
    {
        if ($visitAt->isFuture()) {
            throw ValidationException::withMessages(['visit_time' => 'Waktu kunjungan tidak boleh di masa depan.']);
        }

        if (! in_array($condition, Visit::CONDITIONS, true)) {
            throw ValidationException::withMessages(['condition' => 'Kondisi outlet tidak valid.']);
        }

        $customer = Customer::findOrFail($customerId);

        if ($sales->branch_id && $customer->branch_id && (int) $customer->branch_id !== (int) $sales->branch_id) {
            throw ValidationException::withMessages(['customer_id' => 'Outlet ini bukan milik Depo Sales yang dipilih.']);
        }

        return DB::transaction(function () use ($actor, $sales, $customer, $visitAt, $condition, $reason) {
            // Kunci baris Sales supaya dua Admin tidak membuat kunjungan ganda bersamaan.
            Sales::whereKey($sales->id)->lockForUpdate()->first();

            $exists = Visit::query()
                ->where('sales_id', $sales->id)
                ->where('customer_id', $customer->id)
                ->whereDate('check_in_at', $visitAt->toDateString())
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Sales ini sudah tercatat mengunjungi outlet tersebut pada '.$visitAt->format('d/m/Y').'. Check-in bantuan tidak diperlukan.',
                ]);
            }

            // Hari lampau: selesai di akhir hari itu; hari ini: selesai sekarang.
            $checkOut = $visitAt->isToday() ? now() : $visitAt->copy()->endOfDay();

            $visit = Visit::create([
                'sales_id' => $sales->id,
                'customer_id' => $customer->id,
                'check_in_at' => $visitAt,
                'check_out_at' => $checkOut,
                'check_in_condition' => $condition,
                'notes' => $reason,
                'check_out_notes' => 'Selesai otomatis (check-in bantuan).',
                'status' => Visit::STATUS_COMPLETED,
                'source' => Visit::SOURCE_ADMIN,
                'entered_by' => $actor->id,
                'recorded_at' => now(),
                'entry_reason' => $reason,
            ]);

            AuditLogger::log(
                'assisted_check_in',
                'Sales',
                Visit::class,
                $visit->id,
                null,
                $visit->toArray() + ['branch_id' => $sales->branch_id],
                $actor->id,
                $sales->branch_id ? (int) $sales->branch_id : null,
            );

            return $visit;
        });
    }

    /**
     * Kuota kunjungan: SATU kali per toko per MINGGU (Senin-Minggu, per Sales).
     * Toko boleh dikunjungi di hari mana pun, tidak terkunci di hari jadwal
     * Rute Kanvas-nya; yang dibatasi hanya frekuensinya. Kunjungan yang hasilnya
     * "Toko Tutup" TIDAK menghabiskan kuota: Sales boleh kunjungan ulang satu kali
     * di minggu yang sama. Kalau kunjungan ulang itu pun tutup (atau kunjungan
     * pertama bukan tutup), kuota habis sampai minggu depan.
     */
    private function assertWeeklyQuota(int $salesId, int $customerId): void
    {
        $visits = Visit::query()
            ->where('sales_id', $salesId)
            ->where('customer_id', $customerId)
            ->whereBetween('check_in_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderBy('id')
            ->get(['id', 'customer_id', 'check_in_at', 'check_in_condition']);

        if (self::quotaAllows($visits)) {
            return;
        }

        $last = $visits->last()->check_in_at;

        throw ValidationException::withMessages([
            'customer_id' => $visits->count() >= 2
                ? 'Kuota kunjungan toko ini minggu ini sudah habis (kunjungan ulang setelah Toko Tutup sudah dipakai). Bisa dikunjungi lagi mulai Senin depan.'
                : 'Toko ini sudah Anda kunjungi minggu ini ('.$last->format('d/m').'). Satu toko hanya bisa dikunjungi sekali per minggu, kecuali kunjungan sebelumnya Toko Tutup.',
        ]);
    }

    /** Boleh check-in lagi? (belum ada kunjungan minggu ini, atau satu-satunya kunjungan = Toko Tutup) */
    private static function quotaAllows(Collection $visitsThisWeek): bool
    {
        return $visitsThisWeek->isEmpty()
            || ($visitsThisWeek->count() === 1 && $visitsThisWeek->first()->check_in_condition === Visit::CONDITION_CLOSED);
    }

    /**
     * ID Customer yang kuota kunjungannya MINGGU INI sudah habis, supaya daftar
     * toko di form Check-in tidak menawarkan toko yang pasti ditolak.
     *
     * @return array<int, int>
     */
    public function quotaExhaustedCustomerIds(int $salesId): array
    {
        return Visit::query()
            ->where('sales_id', $salesId)
            ->whereBetween('check_in_at', [now()->startOfWeek(), now()->endOfWeek()])
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
