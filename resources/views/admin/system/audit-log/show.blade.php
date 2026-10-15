<x-admin-layout>
    <x-slot name="header">Detail Audit Log</x-slot>

    <div class="frm-page" style="max-width: 860px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Detail Audit Log #{{ $auditLog->id }}</h1>
                <p class="frm-sub">Read-only.</p>
            </div>
            <a href="{{ route('admin.system.audit-log.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        <div class="panel" style="margin-bottom: 16px;">
            <div class="panel-head">
                <h2 class="panel-title">Ringkasan</h2>
            </div>
            <dl class="frm-detail">
                <div>
                    <dt>Waktu</dt>
                    <dd>{{ $auditLog->created_at->format('d F Y, H:i:s') }}</dd>
                </div>
                <div>
                    <dt>User</dt>
                    <dd>{{ $auditLog->user->name ?? '(sistem)' }} @if ($auditLog->user)<span class="frm-meta">{{ $auditLog->user->email }}</span>@endif</dd>
                </div>
                <div>
                    <dt>Depo</dt>
                    <dd>{{ $auditLog->branch->name ?? 'Global' }}</dd>
                </div>
                <div>
                    <dt>Modul</dt>
                    <dd>{{ $auditLog->module ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Aksi</dt>
                    <dd><span class="frm-code">{{ $auditLog->action }}</span></dd>
                </div>
                <div>
                    <dt>Dokumen</dt>
                    <dd>
                        {{ $auditLog->document_type ? class_basename($auditLog->document_type) : '—' }}
                        @if ($auditLog->document_id) #{{ $auditLog->document_id }} @endif
                    </dd>
                </div>
                <div>
                    <dt>IP Address</dt>
                    <dd>{{ $auditLog->ip_address ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status" style="margin-bottom:16px"><span class="frm-alert-text">{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="frm-alert is-error" role="alert" style="margin-bottom:16px"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        @if ($supportsRestore)
            <div class="panel" style="margin-bottom: 16px;">
                <div class="panel-head">
                    <h2 class="panel-title">Pulihkan Data</h2>
                </div>
                <div style="padding: 16px 22px;">
                    @if ($restoreBlockedReason === null)
                        <p class="frm-meta" style="margin:0 0 12px">
                            Data {{ class_basename($auditLog->document_type) }} ini terhapus. Anda bisa mengembalikannya persis seperti sebelum dihapus
                            (ID dan kode sama, serta penugasan ke Sales). Keanggotaan Rute distribusi dan Rencana Kunjungan harian tidak ikut kembali.
                        </p>
                        @can('master-data.manage')
                            <form method="POST" action="{{ route('admin.system.audit-log.restore', $auditLog) }}">
                                @csrf
                                <button type="submit" class="adm-btn adm-btn-primary" onclick="return confirm('Pulihkan data ini?')">Pulihkan Data</button>
                            </form>
                        @else
                            <p class="frm-meta" style="margin:0">Anda tidak memiliki izin untuk memulihkan data.</p>
                        @endcan
                    @else
                        <p class="frm-meta" style="margin:0">{{ $restoreBlockedReason }}</p>
                    @endif
                </div>
            </div>
        @endif

        @php
            $before = $auditLog->before ?? [];
            $after = $auditLog->after ?? [];
            $keys = collect(array_keys($before))->merge(array_keys($after))->unique()->sort()->values();

            $fmt = function ($v) {
                if (is_null($v)) return '-';
                if (is_bool($v)) return $v ? 'true' : 'false';
                if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return (string) $v;
            };
        @endphp

        <div class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Perubahan Data</h2>
            </div>

            @if ($keys->isEmpty())
                <div class="frm-empty">
                    <p class="frm-empty-text" style="margin: 0;">Tidak ada data before/after untuk log ini (mis. aksi login atau aksi read-only).</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Field</th>
                                <th>Sebelum</th>
                                <th>Sesudah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($keys as $key)
                                @php
                                    $b = $fmt($before[$key] ?? null);
                                    $a = $fmt($after[$key] ?? null);
                                    $changed = $b !== $a;
                                @endphp
                                <tr style="{{ $changed ? 'background: var(--adm-danger-bg);' : '' }}">
                                    <td class="frm-meta" style="font-weight: 700;">{{ $key }}</td>
                                    <td style="{{ $changed ? 'color: var(--adm-danger-solid);' : '' }} overflow-wrap: anywhere;">{{ $b }}</td>
                                    <td style="{{ $changed ? 'color: var(--adm-success); font-weight: 700;' : '' }} overflow-wrap: anywhere;">{{ $a }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
