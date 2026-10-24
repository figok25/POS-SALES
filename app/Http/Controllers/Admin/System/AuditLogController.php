<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditRestoreService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * System - Audit Log (Blueprint #45, permission 'audit-log.view').
 *
 * READ-ONLY. Datanya sudah ditulis dari banyak modul lewat
 * App\Services\AuditLogger sejak awal -- halaman ini cuma jendela untuk
 * melihatnya, tidak pernah membuat/mengubah/menghapus baris audit_logs.
 *
 * Multi Branch/Depo: audit_logs.branch_id diisi otomatis oleh AuditLogger.
 * Admin hanya melihat log Depo-nya sendiri; Super Admin melihat semua
 * (termasuk log Global ber-branch_id NULL) atau satu Depo lewat ?branch=.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        [$module, $action, $userId, $dateFrom, $dateTo] = $this->filtersFromRequest($request);

        $branchContext = BranchContext::current();
        $scoped = fn () => $branchContext->applyTo(AuditLog::query());

        $logs = $this->filteredQuery($request)
            ->with(['user', 'branch'])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        // Pilihan filter ikut dibatasi Depo, supaya Admin tidak melihat
        // nama modul/aksi/user dari Depo lain lewat dropdown.
        $modules = $scoped()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        $actions = $scoped()->distinct()->orderBy('action')->pluck('action');
        $users = $branchContext->applyTo(User::query())->orderBy('name')->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.system.audit-log.index', compact(
            'logs', 'modules', 'actions', 'users', 'module', 'action', 'userId', 'dateFrom', 'dateTo',
            'branches', 'branchContext'
        ));
    }

    /**
     * Unduh CSV Audit Log mengikuti filter yang sedang aktif di halaman
     * index (Depo, Modul, Aksi, User, rentang tanggal) -- supaya Admin bisa
     * simpan jejak suatu kejadian (mis. urutan Apply Settlement vs Apply
     * BTB) sebagai bukti offline, tanpa batas pagination 30 baris/halaman.
     */
    public function export(Request $request): StreamedResponse
    {
        $logs = $this->filteredQuery($request)
            ->with(['user', 'branch'])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'audit-log-'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($logs) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Waktu', 'Depo', 'User', 'Modul', 'Aksi', 'Tipe Dokumen', 'ID Dokumen', 'IP', 'Sebelum', 'Sesudah']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->branch->name ?? 'Global',
                    $log->user->name ?? '(sistem)',
                    $log->module ?? '-',
                    $log->action,
                    $log->document_type ? class_basename($log->document_type) : '-',
                    $log->document_id ?? '-',
                    $log->ip_address ?? '-',
                    $log->before ? json_encode($log->before, JSON_UNESCAPED_UNICODE) : '',
                    $log->after ? json_encode($log->after, JSON_UNESCAPED_UNICODE) : '',
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?int, 3: ?string, 4: ?string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            $request->query('module'),
            $request->query('action'),
            $request->filled('user_id') ? (int) $request->query('user_id') : null,
            $request->query('date_from'),
            $request->query('date_to'),
        ];
    }

    private function filteredQuery(Request $request)
    {
        [$module, $action, $userId, $dateFrom, $dateTo] = $this->filtersFromRequest($request);

        return BranchContext::current()->applyTo(AuditLog::query())
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));
    }

    public function show(AuditLog $auditLog, AuditRestoreService $restore)
    {
        // Anti-IDOR (Multi Branch/Depo): log Depo lain & log Global tidak
        // boleh dibuka Admin walau ID-nya diketahui.
        if (! BranchContext::current()->allows($auditLog->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke log ini.');
        }

        $auditLog->load(['user', 'branch']);

        $supportsRestore = $restore->supports($auditLog);
        $restoreBlockedReason = $supportsRestore ? $restore->blockedReason($auditLog) : null;

        return view('admin.system.audit-log.show', compact('auditLog', 'supportsRestore', 'restoreBlockedReason'));
    }

    /**
     * Pulihkan data master yang terhapus tidak sengaja dari snapshot audit log
     * (saat ini: Customer). Butuh izin kelola master data + akses ke Depo datanya.
     */
    public function restore(AuditLog $auditLog, AuditRestoreService $restore)
    {
        if (! BranchContext::current()->allows($auditLog->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke log ini.');
        }

        try {
            $result = $restore->restore($auditLog, auth()->user());
        } catch (\DomainException $e) {
            return redirect()->route('admin.system.audit-log.show', $auditLog)->withErrors(['restore' => $e->getMessage()]);
        }

        $record = $result['record'];

        return redirect()->route('admin.system.audit-log.show', $auditLog)
            ->with('status', 'Data '.class_basename($record).' "'.($record->name ?? '#'.$record->id).'" berhasil dipulihkan. '.implode(' ', $result['notes']));
    }
}
