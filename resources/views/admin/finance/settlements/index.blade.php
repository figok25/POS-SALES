<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Settlement</h1>
            <a href="{{ route('admin.finance.settlements.create') }}" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm">+ Buat Draft</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-2 text-sm">
            <select name="status" class="border-gray-300 rounded px-3 py-1.5" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="applied" @selected($status === 'applied')>Applied</option>
            </select>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Sales</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-right">Cash Expected</th>
                        <th class="px-3 py-2 text-right">Cash Deposited</th>
                        <th class="px-3 py-2 text-right">Selisih</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $settlement)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $settlement->code }}</td>
                            <td class="px-3 py-2">{{ $settlement->sales->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $settlement->settled_at->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($settlement->cash_expected, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($settlement->cash_deposited, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right {{ $settlement->cash_variance < 0 ? 'text-red-600' : ($settlement->cash_variance > 0 ? 'text-blue-600' : '') }}">
                                Rp {{ number_format($settlement->cash_variance, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2">
                                @if ($settlement->status === 'applied')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Applied</span>
                                @else
                                    <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Draft</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                @if ($settlement->status === 'draft')
                                    <a href="{{ route('admin.finance.settlements.edit', $settlement) }}" class="text-blue-600 hover:underline">Lanjutkan</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada settlement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
