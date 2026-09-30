<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * System - Audit Log (Blueprint #45, permission 'audit-log.view').
 *
 * READ-ONLY. Datanya sudah ditulis dari banyak modul lewat
 * App\Services\AuditLogger sejak awal -- halaman ini cuma jendela untuk
 * melihatnya, tidak pernah membuat/mengubah/menghapus baris audit_logs.
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

        $logs = AuditLog::with('user')
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $modules = AuditLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $users = User::orderBy('name')->get();

        return view('admin.system.audit-log.index', compact(
            'logs', 'modules', 'actions', 'users', 'module', 'action', 'userId', 'dateFrom', 'dateTo'
        ));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('admin.system.audit-log.show', compact('auditLog'));
    }
}
