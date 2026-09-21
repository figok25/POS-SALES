<x-sales-layout>
    <x-slot name="header">Return Stock</x-slot>

    @if (session('status'))
        <div class="mb-3 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
    @endif

    @if (! $activeTask)
        <div class="mb-3 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
            Anda belum memiliki pekerjaan (Working/Completed) yang bisa dijadikan rujukan retur.
        </div>
    @elseif ($currentStock->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm">
            <p class="text-gray-600 mb-3">Sales Stock Anda kosong, tidak ada yang bisa diretur. Ini normal kalau semua barang sudah terjual/dikunjungkan habis hari ini.</p>
            <form method="POST" action="{{ route('sales.return-stock.complete-empty') }}" onsubmit="return confirm('Tandai Task ini selesai? Pastikan Sales Stock Anda memang sudah habis.');">
                @csrf
                <button class="w-full bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                    Selesaikan Task (Stock Habis)
                </button>
            </form>
        </div>
    @else
        <p class="text-sm text-gray-600 mb-3">Kirim stock sisa yang belum terjual. Admin akan memeriksa fisiknya sebelum disetujui.</p>

        <form method="POST" action="{{ route('sales.return-stock.store') }}" class="bg-white rounded-lg shadow p-3 mb-4">
            @csrf
            @if ($errors->any())
                <div class="mb-3 p-2 bg-red-100 text-red-800 rounded text-xs">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
                </div>
            @endif

            <div class="divide-y">
                @foreach ($currentStock as $i => $item)
                    <div class="flex items-center justify-between py-2 gap-2">
                        <div class="text-sm flex-1">
                            <div>{{ $item->product->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">Stock saat ini: {{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</div>
                        </div>
                        <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                        <input type="number" step="0.01" min="0" max="{{ $item->quantity }}"
                               name="items[{{ $i }}][quantity]" placeholder="0"
                               class="w-24 border rounded px-2 py-1.5 text-sm text-right">
                    </div>
                @endforeach
            </div>

            <textarea name="notes" rows="2" placeholder="Catatan (opsional)" class="w-full border rounded px-2 py-1.5 text-sm mt-3">{{ old('notes') }}</textarea>

            <button class="w-full bg-blue-600 text-white px-3 py-2 rounded text-sm mt-3 hover:bg-blue-700">Submit Return Stock</button>
            <p class="text-xs text-gray-500 mt-2">Kosongkan/isi 0 pada produk yang tidak diretur. Baris dengan quantity 0 akan diabaikan.</p>
        </form>
    @endif

    <div class="font-medium text-sm mb-2">Riwayat Return Stock</div>
    <div class="bg-white rounded-lg shadow divide-y text-sm">
        @forelse ($returns as $r)
            <div class="px-3 py-2.5">
                <div class="flex justify-between">
                    <span class="font-mono">{{ $r->code }}</span>
                    @if ($r->status === 'applied')
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Approved</span>
                    @elseif ($r->status === 'discrepancy')
                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Discrepancy</span>
                    @elseif ($r->status === 'cancelled')
                        <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-xs">Cancelled</span>
                    @else
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Menunggu Check</span>
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ $r->created_at->format('d/m/Y H:i') }} &middot; {{ $r->items->count() }} item</div>
            </div>
        @empty
            <p class="text-center text-gray-500 text-sm py-6">Belum ada Return Stock.</p>
        @endforelse
    </div>
</x-sales-layout>
