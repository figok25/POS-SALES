<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAssignment;
use Illuminate\Support\Facades\DB;

/**
 * Phase 6 Hardening — Customer Assignment (Blueprint baris #729, #12,
 * #1008 Developer C: "Customer Assignment").
 *
 * Ini SATU-SATUNYA jalan resmi untuk mengubah `customers.sales_id`.
 * Modul lain (Master Data Customer, Tagging Toko approval, dst) WAJIB
 * lewat service ini, tidak boleh langsung meng-update kolom sales_id,
 * supaya riwayat assignment selalu konsisten dan teraudit — sama
 * seperti prinsip StockService untuk tabel stocks.
 */
class CustomerAssignmentService
{
    /**
     * Assign (atau reassign) Customer ke Sales tertentu.
     *
     * Menutup assignment aktif sebelumnya (jika ada dan berbeda),
     * membuat baris riwayat baru, lalu memperbarui pointer
     * customers.sales_id. No-op (tidak membuat baris baru) bila
     * Customer sudah ditangani Sales yang sama.
     */
    public function assign(Customer $customer, int $newSalesId, ?int $assignedByUserId, ?string $reason = null): CustomerAssignment
    {
        return DB::transaction(function () use ($customer, $newSalesId, $assignedByUserId, $reason) {
            $current = $customer->assignments()->current()->lockForUpdate()->first();

            if ($current && (int) $current->sales_id === $newSalesId) {
                return $current;
            }

            if ($current) {
                $current->update(['unassigned_at' => now()]);
            }

            $assignment = $customer->assignments()->create([
                'sales_id' => $newSalesId,
                'assigned_by' => $assignedByUserId,
                'assigned_at' => now(),
                'reason' => $reason,
            ]);

            $before = ['sales_id' => $customer->sales_id];
            $customer->update(['sales_id' => $newSalesId]);

            AuditLogger::log(
                action: $current ? 'reassign' : 'assign',
                module: 'Sales',
                documentType: Customer::class,
                documentId: $customer->id,
                before: $before,
                after: ['sales_id' => $newSalesId, 'reason' => $reason],
                userId: $assignedByUserId,
            );

            return $assignment;
        });
    }

    /**
     * Lepas Customer dari Sales yang menanganinya saat ini (tidak
     * dipindah ke Sales lain — jadi tidak ada Sales yang menangani
     * untuk sementara, mis. saat Sales resign dan penggantinya belum
     * ditentukan).
     */
    public function unassign(Customer $customer, ?int $byUserId, ?string $reason = null): void
    {
        DB::transaction(function () use ($customer, $byUserId, $reason) {
            $current = $customer->assignments()->current()->lockForUpdate()->first();

            if (! $current) {
                return;
            }

            $current->update(['unassigned_at' => now()]);

            $before = ['sales_id' => $customer->sales_id];
            $customer->update(['sales_id' => null]);

            AuditLogger::log(
                action: 'unassign',
                module: 'Sales',
                documentType: Customer::class,
                documentId: $customer->id,
                before: $before,
                after: ['sales_id' => null, 'reason' => $reason],
                userId: $byUserId,
            );
        });
    }
}
