<x-admin-layout>
    <div class="p-6 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Assign / Reassign Customer</h1>
            <a href="{{ route('admin.sales.customer-assignments.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali</a>
        </div>

        <div class="bg-white rounded shadow p-4 mb-6">
            <p class="text-sm text-gray-500">Customer</p>
            <p class="font-medium">{{ $customer->name }} <span class="text-gray-400 font-normal">({{ $customer->code }})</span></p>
            <p class="text-sm text-gray-500 mt-3">Sales Saat Ini</p>
            <p class="font-medium">{{ $customer->sales->name ?? 'Belum ada' }}</p>
        </div>

        <form method="POST" action="{{ route('admin.sales.customer-assignments.update', $customer) }}" class="bg-white rounded shadow p-4 mb-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Assign ke Sales</label>
                <select name="sales_id" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="">-- Tidak ada (lepas dari Sales saat ini) --</option>
                    @foreach ($saless as $sales)
                        <option value="{{ $sales->id }}" @selected((string) $customer->sales_id === (string) $sales->id)>{{ $sales->name }}</option>
                    @endforeach
                </select>
                @error('sales_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Alasan (opsional)</label>
                <input type="text" name="reason" maxlength="255" placeholder="mis. Sales lama resign, area dipindah, dst."
                       class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                @error('reason')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Simpan</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <div class="px-4 py-3 border-b font-medium text-sm">Riwayat Assignment</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Ditugaskan</th>
                        <th class="px-3 py-2 text-left">Berakhir</th>
                        <th class="px-3 py-2 text-left">Oleh</th>
                        <th class="px-3 py-2 text-left">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $row)
                        <tr class="border-b {{ $row->isCurrent() ? 'bg-green-50' : '' }}">
                            <td class="px-3 py-2">{{ $row->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->assigned_at->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $row->unassigned_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $row->assignedBy->name ?? 'Sistem' }}</td>
                            <td class="px-3 py-2">{{ $row->reason ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada riwayat assignment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
