<x-admin-layout>
    <x-slot name="header">Audit Report</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Audit Report</h1>
                <p class="frm-sub">Riwayat aktivitas seluruh modul.</p>
            </div>
            <div class="frm-head-actions">
                <x-report-export />
                <a href="{{ route('admin.reports.index') }}" class="adm-btn adm-btn-ghost">&larr; Reports</a>
            </div>
        </div>

        <form method="GET" class="dash-filter">
            <div class="dash-field">
                <label for="repModule">Modul</label>
                <select id="repModule" name="module">
                    <option value="">Semua Modul</option>
                    @foreach ($modules as $m)
                        <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dash-actions">
                <button type="submit" class="adm-btn adm-btn-primary">Filter</button>
                @if ($module)
                    <a href="{{ route('admin.reports.audit') }}" class="adm-btn adm-btn-ghost">Reset</a>
                @endif
            </div>
        </form>

        <div class="panel">
            @if ($logs->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6M9 11h6"/></svg>
                    </div>
                    <p class="frm-empty-title">Belum ada log</p>
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td data-label="Waktu" class="frm-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                    <td data-label="User">{{ $log->user->name ?? '(sistem)' }}</td>
                                    <td data-label="Modul">{{ $log->module ?? '—' }}</td>
                                    <td data-label="Aksi"><span class="frm-code">{{ $log->action }}</span></td>
                                    <td data-label="Dokumen" class="frm-meta">
                                        {{ $log->document_type ? class_basename($log->document_type) : '—' }}
                                        @if ($log->document_id) #{{ $log->document_id }} @endif
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
