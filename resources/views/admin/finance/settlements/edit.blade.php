<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Settlement {{ $settlement->code }}</h1>
            <a href="{{ route('admin.finance.settlements.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded shadow p-4 mb-4 text-sm grid grid-cols-2 gap-2">
            <div><span class="text-gray-500">Sales</span><br>{{ $settlement->sales->name }}</div>
            <div><span class="text-gray-500">Warehouse Tujuan</span><br>{{ $settlement->warehouse->name }}</div>
            <div><span class="text-gray-500">Tanggal</span><br>{{ $settlement->settled_at->format('d M Y') }}</div>
            <div><span class="text-gray-500">Cash Expected (dari {{ $settlement->payments->count() }} payment cash)</span><br>
                <span class="font-medium">Rp {{ number_format($settlement->cash_expected, 0, ',', '.') }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.finance.settlements.apply', $settlement) }}">
            @csrf

            <div class="bg-white rounded shadow overflow-x-auto mb-4">
                <div class="px-4 py-3 border-b font-medium text-sm">Retur Barang (Sales Stock &rarr; Warehouse)</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left">Produk</th>
                            <th class="px-3 py-2 text-right">Qty di Sistem</th>
                            <th class="px-3 py-2 text-right">Qty Diretur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settlement->items as $item)
                            <tr class="border-b">
                                <td class="px-3 py-2">{{ $item->product->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($item->system_qty, 2, '.', ''), '0'), '.') }}</td>
                                <td class="px-3 py-2 text-right">
                                    <input type="number" name="returned_qty[{{ $item->product_id }}]" step="0.01" min="0"
                                           max="{{ $item->system_qty }}" value="{{ old("returned_qty.{$item->product_id}", $item->system_qty) }}"
                                           class="w-28 border-gray-300 rounded px-2 py-1 text-right">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Tidak ada Sales Stock tersisa untuk Sales ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded shadow p-4 mb-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Jumlah Uang Disetor</label>
                    <input type="number" name="cash_deposited" step="0.01" min="0"
                           value="{{ old('cash_deposited', $settlement->cash_expected) }}"
                           class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                    <p class="text-xs text-gray-500 mt-1">Kalau berbeda dari Cash Expected, selisihnya akan tercatat otomatis.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Catatan (opsional)</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="mis. alasan selisih"
                           class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Apply Settlement</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.finance.settlements.destroy', $settlement) }}" class="mt-3"
              onsubmit="return confirm('Batalkan Draft ini? Payment yang sudah direservasi akan dilepas kembali.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:underline">Batalkan Draft</button>
        </form>
    </div>
</x-admin-layout>
