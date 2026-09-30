<x-admin-layout>
    <x-slot name="header">Detail Audit Log</x-slot>
    <div class="p-6 max-w-4xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Detail Audit Log #{{ $auditLog->id }}</h1>
            <a href="{{ route('admin.system.audit-log.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali</a>
        </div>

        <div class="bg-white rounded shadow p-4 mb-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Waktu</dt>
                    <dd class="mt-0.5">{{ $auditLog->created_at->format('d F Y, H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">User</dt>
                    <dd class="mt-0.5">{{ $auditLog->user->name ?? '(sistem)' }} @if ($auditLog->user)<span class="text-gray-400">({{ $auditLog->user->email }})</span>@endif</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Modul</dt>
                    <dd class="mt-0.5">{{ $auditLog->module ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</dt>
                    <dd class="mt-0.5"><span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ $auditLog->action }}</span></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dokumen</dt>
                    <dd class="mt-0.5 font-mono text-xs">
                        {{ $auditLog->document_type ? class_basename($auditLog->document_type) : '-' }}
                        @if ($auditLog->document_id) #{{ $auditLog->document_id }} @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">IP Address</dt>
                    <dd class="mt-0.5">{{ $auditLog->ip_address ?? '-' }}</dd>
                </div>
            </dl>
        </div>

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

        @if ($keys->isNotEmpty())
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 border-b">
                    Perubahan Data
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2 text-left w-1/4">Field</th>
                            <th class="px-4 py-2 text-left">Sebelum</th>
                            <th class="px-4 py-2 text-left">Sesudah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($keys as $key)
                            @php
                                $b = $fmt($before[$key] ?? null);
                                $a = $fmt($after[$key] ?? null);
                                $changed = $b !== $a;
                            @endphp
                            <tr class="border-t {{ $changed ? 'bg-yellow-50' : '' }}">
                                <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $key }}</td>
                                <td class="px-4 py-2 {{ $changed ? 'text-red-700' : 'text-gray-500' }} break-all">{{ $b }}</td>
                                <td class="px-4 py-2 {{ $changed ? 'text-green-700 font-medium' : 'text-gray-500' }} break-all">{{ $a }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded shadow p-6 text-center text-sm text-gray-500">
                Tidak ada data before/after untuk log ini (kemungkinan aksi tanpa perubahan field, mis. login atau aksi read-only).
            </div>
        @endif
    </div>
</x-admin-layout>
