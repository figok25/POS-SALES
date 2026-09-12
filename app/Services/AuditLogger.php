<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Audit Foundation (Blueprint #45).
 *
 * Dipakai oleh module-module berikutnya (BKB Apply, BTB Apply, Sales
 * Transaction, Payment, Settlement, dst) untuk mencatat aktivitas penting
 * secara konsisten. Fase 1 hanya menyediakan foundation-nya; pemanggilan
 * di setiap module bisnis dilakukan pada fase modul terkait.
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
     */
    public static function log(
        string $action,
        ?string $module = null,
        ?string $documentType = null,
        ?int $documentId = null,
        ?array $before = null,
        ?array $after = null,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
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
}
