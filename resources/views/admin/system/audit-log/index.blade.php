<x-admin-layout>
    <x-slot name="header">Audit Log</x-slot>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Audit Log</h1>

        <form method="GET" class="mb-4 bg-white p-3 rounded shadow flex flex-wrap items-end gap-3 text-sm">
            <div>
                <label class="block text-xs font-medium mb-1">Modul</label>
                <select name="module" class="border rounded px-3 py-2 text-sm min-w-[160px]">
                    <option value="">- Semua Modul -</option>
                    @foreach ($modules as $m)
                        <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Aksi</label>
                <select name="action" class="border rounded px-3 py-2 text-sm min-w-[140px]">
                    <option value="">- Semua Aksi -</option>
                    @foreach ($actions as $a)
                        <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">User</label>
                <select name="user_id" class="border rounded px-3 py-2 text-sm min-w-[160px]">
                    <option value="">- Semua User -</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected($userId === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Filter</button>
            @if ($module || $action || $userId || $dateFrom || $dateTo)
                <a href="{{ route('admin.system.audit-log.index') }}" class="px-3 py-2 text-sm rounded border">Reset</a>
            @endif
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Waktu</th>
                        <th class="px-3 py-2 text-left">User</th>
                        <th class="px-3 py-2 text-left">Modul</th>
                        <th class="px-3 py-2 text-left">Aksi</th>
                        <th class="px-3 py-2 text-left">Dokumen</th>
                        <th class="px-3 py-2 text-left">IP</th>
                        <th class="px-3 py-2 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b">
                            <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-3 py-2">{{ $log->user->name ?? '(sistem)' }}</td>
                            <td class="px-3 py-2">{{ $log->module ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ $log->action }}</span>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">
                                {{ $log->document_type ? class_basename($log->document_type) : '-' }}
                                @if ($log->document_id) #{{ $log->document_id }} @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500">{{ $log->ip_address ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.system.audit-log.show', $log) }}" class="text-blue-600 hover:underline">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Tidak ada data audit log untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
