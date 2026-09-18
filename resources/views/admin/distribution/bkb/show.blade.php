<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">BKB Distribusi {{ $bkb->code }}</h1>
            <a href="{{ route('admin.distribution.bkb.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded shadow mb-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Warehouse Asal</dt><dd>{{ $bkb->warehouse->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Sales Tujuan</dt><dd>{{ $bkb->sales->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="capitalize">{{ $bkb->status }}</dd></div>
                <div><dt class="text-gray-500">Referensi Permintaan Barang</dt><dd>{{ $bkb->stockRequest->code ?? '-' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd>{{ $bkb->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Quantity Diminta</th>
                        @if ($bkb->isDraft())
                            <th class="px-3 py-2 text-right">Stok Warehouse Saat Ini</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bkb->items as $line)
                        @php $stok = $availability[$line->product_id] ?? null; @endphp
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2 text-right">{{ number_format($line->quantity, 2) }}</td>
                            @if ($bkb->isDraft())
                                <td class="px-3 py-2 text-right {{ $stok !== null && $stok < $line->quantity ? 'text-red-600 font-semibold' : '' }}">
                                    {{ number_format($stok ?? 0, 2) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($bkb->isDraft())
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Tahap Check: pastikan stok warehouse mencukupi sebelum Apply. Apply akan mengurangi Warehouse Stock dan menambah Sales Stock.
            </div>
        @endif

        <div class="flex gap-2">
            @if ($bkb->isDraft())
                @can('distribution.apply')
                    <form action="{{ route('admin.distribution.bkb.apply', $bkb) }}" method="POST" onsubmit="return confirm('Apply BKB Distribusi ini? Stok akan berubah.')">
                        @csrf
                        <button class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Apply</button>
                    </form>
                @endcan
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.bkb.cancel', $bkb) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button class="border px-3 py-2 rounded text-sm">Batalkan</button>
                    </form>
                @endcan
            @endif

            @if ($bkb->status === 'applied')
                @can('sales-task.manage')
                    @if ($bkb->isReadyForAssignment())
                        <a href="{{ route('admin.sales-tasks.create', ['bkb_distribusi_id' => $bkb->id]) }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Buat Sales Task (Tugaskan)</a>
                    @else
                        <span class="text-xs text-gray-500 self-center">Sudah ditugaskan lewat Sales Task {{ $bkb->salesTask->code ?? '' }}</span>
                    @endif
                @endcan
                @can('distribution.manage')
                    <a href="{{ route('admin.distribution.btb.create', ['bkb_distribusi_id' => $bkb->id]) }}" class="bg-gray-700 text-white px-3 py-2 rounded text-sm hover:bg-gray-800">Buat BTB (Pengembalian)</a>
                @endcan
            @endif
        </div>
    </div>
</x-admin-layout>
