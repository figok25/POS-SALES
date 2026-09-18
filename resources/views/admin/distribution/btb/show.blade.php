<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">BTB Distribusi {{ $btb->code }}</h1>
            <a href="{{ route('admin.distribution.btb.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded shadow mb-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Sales Asal</dt><dd>{{ $btb->sales->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Warehouse Tujuan</dt><dd>{{ $btb->warehouse->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="capitalize">
                    @if ($btb->status === 'discrepancy')
                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Discrepancy</span>
                    @else
                        {{ $btb->status }}
                    @endif
                </dd></div>
                <div><dt class="text-gray-500">Asal Dokumen</dt><dd>
                    {{ $btb->source === 'return_stock' ? 'Return Stock (Sales Mobile)' : 'Dibuat Manual oleh Admin' }}
                </dd></div>
                <div><dt class="text-gray-500">Referensi BKB</dt><dd>{{ $btb->bkbDistribusi->code ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Diperiksa (Check)</dt><dd>{{ $btb->checked_at ? $btb->checked_at->format('d/m/Y H:i') : 'Belum diperiksa' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd class="whitespace-pre-line">{{ $btb->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Quantity Dikembalikan</th>
                        @if ($btb->isDraft())
                            <th class="px-3 py-2 text-right">Sales Stock Saat Ini</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($btb->items as $line)
                        @php $stok = $availability[$line->product_id] ?? null; @endphp
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2 text-right">{{ number_format($line->quantity, 2) }}</td>
                            @if ($btb->isDraft())
                                <td class="px-3 py-2 text-right {{ $stok !== null && $stok < $line->quantity ? 'text-red-600 font-semibold' : '' }}">
                                    {{ number_format($stok ?? 0, 2) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($btb->isDraft())
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Tahap Check: pastikan Sales Stock mencukupi sebelum Apply. Apply akan mengurangi Sales Stock dan menambah Warehouse Stock.
            </div>
        @endif

        <div class="flex gap-2">
            @if ($btb->isDraft())
                @can('distribution.apply')
                    <form action="{{ route('admin.distribution.btb.apply', $btb) }}" method="POST" onsubmit="return confirm('Apply (Approve) BTB Distribusi ini? Stok akan berubah.')">
                        @csrf
                        <button class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Apply / Approve</button>
                    </form>
                    <button type="button" onclick="document.getElementById('discrepancy-form').classList.toggle('hidden')" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Tandai Discrepancy</button>
                @endcan
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.btb.cancel', $btb) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button class="border px-3 py-2 rounded text-sm">Batalkan</button>
                    </form>
                @endcan
            @endif
        </div>

        @if ($btb->isDraft())
            @can('distribution.apply')
                <form id="discrepancy-form" action="{{ route('admin.distribution.btb.discrepancy', $btb) }}" method="POST" class="hidden mt-3 bg-white p-4 rounded shadow" onsubmit="return confirm('Tandai dokumen ini Discrepancy? Stock TIDAK akan berpindah.')">
                    @csrf
                    <label class="block text-sm font-medium mb-1">Catatan Selisih (wajib diisi)</label>
                    <textarea name="discrepancy_notes" rows="2" class="w-full border rounded px-3 py-2 text-sm" required placeholder="Jelaskan selisih yang ditemukan saat Check fisik..."></textarea>
                    <button class="bg-red-600 text-white px-3 py-2 rounded text-sm mt-2 hover:bg-red-700">Simpan Discrepancy</button>
                </form>
            @endcan
        @endif
    </div>
</x-admin-layout>
