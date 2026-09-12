<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Branch Transfer {{ $transfer->code }}</h1>
            <a href="{{ route('admin.distribution.branch-transfer.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded shadow mb-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Warehouse Asal</dt><dd>{{ $transfer->fromWarehouse->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Warehouse Tujuan</dt><dd>{{ $transfer->toWarehouse->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="capitalize">{{ $transfer->status }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd>{{ $transfer->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Qty Dikirim</th>
                        @if ($transfer->isDraft())
                            <th class="px-3 py-2 text-right">Stok Warehouse Asal</th>
                        @endif
                        @if ($transfer->status !== 'draft')
                            <th class="px-3 py-2 text-right">Qty Diterima</th>
                            <th class="px-3 py-2 text-right">Selisih</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfer->items as $line)
                        @php $stok = $availability[$line->product_id] ?? null; @endphp
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2 text-right">{{ number_format($line->quantity_sent, 2) }}</td>
                            @if ($transfer->isDraft())
                                <td class="px-3 py-2 text-right {{ $stok !== null && $stok < $line->quantity_sent ? 'text-red-600 font-semibold' : '' }}">
                                    {{ number_format($stok ?? 0, 2) }}
                                </td>
                            @endif
                            @if ($transfer->status !== 'draft')
                                <td class="px-3 py-2 text-right">{{ $line->quantity_received !== null ? number_format($line->quantity_received, 2) : '-' }}</td>
                                <td class="px-3 py-2 text-right {{ $line->selisih() != 0 ? 'text-red-600 font-semibold' : '' }}">
                                    {{ $line->quantity_received !== null ? number_format($line->selisih(), 2) : '-' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($transfer->isDraft())
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Tahap Check (BKB Cabang): pastikan stok warehouse asal mencukupi sebelum Apply/Kirim.
            </div>
        @elseif ($transfer->isSent())
            <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded text-sm">
                Barang sedang dalam pengiriman. Lanjutkan ke BTB Cabang untuk menerima barang di warehouse tujuan.
            </div>
        @endif

        <div class="flex gap-2">
            @if ($transfer->isDraft())
                @can('distribution.apply')
                    <form action="{{ route('admin.distribution.branch-transfer.send', $transfer) }}" method="POST" onsubmit="return confirm('Apply BKB Cabang? Stok warehouse asal akan berkurang.')">
                        @csrf
                        <button class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Apply (BKB Cabang - Kirim)</button>
                    </form>
                @endcan
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.branch-transfer.cancel', $transfer) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button class="border px-3 py-2 rounded text-sm">Batalkan</button>
                    </form>
                @endcan
            @endif

            @if ($transfer->isSent())
                @can('distribution.apply')
                    <a href="{{ route('admin.distribution.branch-transfer.receive-form', $transfer) }}" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Terima (BTB Cabang - Check & Apply)</a>
                @endcan
            @endif
        </div>
    </div>
</x-admin-layout>
