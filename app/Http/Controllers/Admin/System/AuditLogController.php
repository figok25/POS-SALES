<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\Request;

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
        $module = $request->query('module');
        $action = $request->query('action');
        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $branchContext = BranchContext::current();
        $scoped = fn () => $branchContext->applyTo(AuditLog::query());

        $logs = $scoped()
            ->with(['user', 'branch'])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
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

    public function show(AuditLog $auditLog)
    {
        // Anti-IDOR (Multi Branch/Depo): log Depo lain & log Global tidak
        // boleh dibuka Admin walau ID-nya diketahui.
        if (! BranchContext::current()->allows($auditLog->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke log ini.');
        }

        $auditLog->load(['user', 'branch']);

        return view('admin.system.audit-log.show', compact('auditLog'));
    }
}
