<?php

namespace App\Services;

use App\Models\Visit;
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
     */
    public function checkIn(int $salesId, int $customerId, array $data): Visit
    {
        $ongoing = Visit::where('sales_id', $salesId)->where('status', Visit::STATUS_ONGOING)->exists();

        if ($ongoing) {
            throw ValidationException::withMessages([
                'customer_id' => 'Anda masih memiliki kunjungan yang berjalan. Selesaikan (Check-out) kunjungan sebelumnya terlebih dahulu.',
            ]);
        }

        $visit = Visit::create([
            'sales_id' => $salesId,
            'customer_id' => $customerId,
            'check_in_at' => now(),
            'check_in_latitude' => $data['latitude'] ?? null,
            'check_in_longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => Visit::STATUS_ONGOING,
        ]);

        AuditLogger::log('check_in', 'Sales', Visit::class, $visit->id, null, $visit->toArray());

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

        $visit->update([
            'check_out_at' => now(),
            'check_out_latitude' => $data['latitude'] ?? null,
            'check_out_longitude' => $data['longitude'] ?? null,
            'notes' => $data['notes'] ?? $visit->notes,
            'status' => Visit::STATUS_COMPLETED,
        ]);

        AuditLogger::log('check_out', 'Sales', Visit::class, $visit->id, $before, $visit->fresh()->toArray());

        return $visit;
    }
}
