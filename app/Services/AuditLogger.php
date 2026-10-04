<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Audit Foundation (Blueprint #45).
 *
 * Dipakai oleh module-module berikutnya (BKB Apply, BTB Apply, Sales
 * Transaction, Payment, Settlement, dst) untuk mencatat aktivitas penting
 * secara konsisten. Fase 1 hanya menyediakan foundation-nya; pemanggilan
 * di setiap module bisnis dilakukan pada fase modul terkait.
 *
 * Multi Branch/Depo: setiap log ikut menyimpan `branch_id` supaya Audit Log
 * bisa difilter per Depo. Pemanggil TIDAK perlu mengubah apa pun; Depo
 * ditentukan otomatis (lihat resolveBranchId()).
 */
class AuditLogger
{
    /**
     * Catat satu aktivitas ke audit_logs.
     *
     * @param  string  $action  mis. 'login', 'create', 'update', 'apply', 'cancel', 'permission_change'
     * @param  string|null  $module  mis. 'Inventory', 'BKB', 'Sales Transaction'
     * @param  string|null  $documentType  mis. class model atau nama dokumen bisnis
     * @param  int|null  $documentId
     * @param  array|null  $before
     * @param  array|null  $after
     * @param  int|null  $branchId  Depo eksplisit (opsional). Kalau null, ditentukan otomatis.
     */
    public static function log(
        string $action,
        ?string $module = null,
        ?string $documentType = null,
        ?int $documentId = null,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
        ?int $branchId = null,
    ): AuditLog {
        $actorId = $userId ?? Auth::id();

        return AuditLog::create([
            'user_id' => $actorId,
            'branch_id' => $branchId ?? self::resolveBranchId($actorId, $before, $after),
            'action' => $action,
            'module' => $module,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Urutan penentuan Depo:
     *  1. branch_id pada data dokumen (after, lalu before) -- paling akurat,
     *     mis. Super Admin mengubah Customer milik Depo X -> log masuk Depo X.
     *  2. Depo milik pelaku (users.branch_id) -- Admin/Sales selalu punya.
     *  3. NULL = Global (Super Admin tanpa dokumen ber-Depo, atau sistem).
     */
    private static function resolveBranchId(?int $actorId, ?array $before, ?array $after): ?int
    {
        foreach ([$after, $before] as $payload) {
            if (is_array($payload) && isset($payload['branch_id']) && is_numeric($payload['branch_id'])) {
                return (int) $payload['branch_id'];
            }
        }

        if ($actorId === null) {
            return null;
        }

        $authUser = Auth::user();

        $branchId = ($authUser && (int) $authUser->getAuthIdentifier() === (int) $actorId)
            ? $authUser->branch_id
            : User::query()->whereKey($actorId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }
}
