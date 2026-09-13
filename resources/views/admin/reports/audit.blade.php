<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Audit Report</h1>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Reports</a>
        </div>

        <form method="GET" class="mb-4 flex gap-2">
            <select name="module" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Modul</option>
                @foreach ($modules as $m)
                    <option value="{{ $m }}" @selected($module === $m)>{{ $m }}</option>
                @endforeach
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $log->user->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $log->module }}</td>
                            <td class="px-3 py-2 capitalize">{{ $log->action }}</td>
                            <td class="px-3 py-2">{{ class_basename($log->document_type) }} #{{ $log->document_id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Belum ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</x-admin-layout>
