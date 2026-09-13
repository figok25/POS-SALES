<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Income &amp; Expense</h1>
            <a href="{{ route('admin.finance.cash-ledgers.create') }}" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm">+ Tambah Catatan</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-2 gap-4 mb-4 max-w-md">
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Income</p>
                <p class="text-lg font-semibold text-green-700">Rp {{ number_format($summaryIncome, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Expense</p>
                <p class="text-lg font-semibold text-red-700">Rp {{ number_format($summaryExpense, 0, ',', '.') }}</p>
            </div>
        </div>

        <form method="GET" class="mb-4 flex gap-2 text-sm">
            <select name="type" class="border-gray-300 rounded px-3 py-1.5" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="income" @selected($type === 'income')>Income</option>
                <option value="expense" @selected($type === 'expense')>Expense</option>
            </select>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-left">Kategori</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                        <th class="px-3 py-2 text-left">Keterangan</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $entry)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $entry->code }}</td>
                            <td class="px-3 py-2">{{ $entry->date->format('d M Y') }}</td>
                            <td class="px-3 py-2">
                                @if ($entry->type === 'income')
                                    <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Income</span>
                                @else
                                    <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Expense</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $entry->category }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($entry->amount, 0, ',', '.') }}</td>
                            <td class="px-3 py-2">{{ $entry->description ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">
                                <form method="POST" action="{{ route('admin.finance.cash-ledgers.destroy', $entry) }}" onsubmit="return confirm('Hapus catatan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada catatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
