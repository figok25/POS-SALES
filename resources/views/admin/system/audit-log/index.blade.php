<x-admin-layout>
    <x-slot name="header">Audit Log</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Audit Log</h1>
                <p class="frm-sub">Jejak aksi create/update/delete/login di seluruh modul. Read-only.</p>
            </div>
        </div>

        <form method="GET" class="dash-filter">
            <div class="dash-field">
                <label for="algModule">Modul</label>
                <select id="algModule" name="module">
                    <option value="">- Semua Modul -</option>
                    @foreach ($modules as $m)
                        <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dash-field">
                <label for="algAction">Aksi</label>
                <select id="algAction" name="action">
                    <option value="">- Semua Aksi -</option>
                    @foreach ($actions as $a)
                        <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dash-field">
                <label for="algUser">User</label>
                <select id="algUser" name="user_id">
                    <option value="">- Semua User -</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected($userId === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dash-field is-date">
                <label for="algFrom">Dari Tanggal</label>
                <input type="date" id="algFrom" name="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="dash-field is-date">
                <label for="algTo">Sampai Tanggal</label>
                <input type="date" id="algTo" name="date_to" value="{{ $dateTo }}">
            </div>
            <div class="dash-actions">
                <button type="submit" class="adm-btn adm-btn-primary">Filter</button>
                @if ($module || $action || $userId || $dateFrom || $dateTo)
                    <a href="{{ route('admin.system.audit-log.index') }}" class="adm-btn adm-btn-ghost">Reset</a>
                @endif
            </div>
        </form>

        <div class="panel">
            @if ($logs->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6M9 11h6M9 19h3"/></svg>
                    </div>
                    <p class="frm-empty-title">Tidak ada data</p>
                    <p class="frm-empty-text">Tidak ada audit log yang cocok dengan filter ini.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Modul</th>
                                <th>Aksi</th>
                                <th>Dokumen</th>
                                <th>IP</th>
                                <th class="is-end">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="frm-dash" style="color: var(--adm-body); white-space: nowrap;">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ $log->user->name ?? '(sistem)' }}</td>
                                    <td>{{ $log->module ?? '—' }}</td>
                                    <td><span class="frm-code">{{ $log->action }}</span></td>
                                    <td class="frm-meta">
                                        {{ $log->document_type ? class_basename($log->document_type) : '—' }}
                                        @if ($log->document_id) #{{ $log->document_id }} @endif
                                    </td>
                                    <td class="frm-meta">{{ $log->ip_address ?? '—' }}</td>
                                    <td class="is-end">
                                        <a href="{{ route('admin.system.audit-log.show', $log) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Lihat</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="frm-pager">{{ $logs->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
