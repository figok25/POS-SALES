<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Permintaan Barang {{ $stockRequest->code }}</h1>
            <a href="{{ route('admin.distribution.stock-requests.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded shadow mb-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Warehouse</dt><dd>{{ $stockRequest->warehouse->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Sales</dt><dd>{{ $stockRequest->sales->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="capitalize">{{ $stockRequest->status }}</dd></div>
                <div><dt class="text-gray-500">Dibuat oleh</dt><dd>{{ $stockRequest->creator->name ?? '-' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd>{{ $stockRequest->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockRequest->items as $line)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2 text-right">{{ number_format($line->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex gap-2">
            @if ($stockRequest->isDraft())
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.stock-requests.submit', $stockRequest) }}" method="POST" onsubmit="return confirm('Submit Permintaan Barang ini?')">
                        @csrf
                        <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Submit</button>
                    </form>
                    <form action="{{ route('admin.distribution.stock-requests.destroy', $stockRequest) }}" method="POST" onsubmit="return confirm('Hapus draft ini?')">
                        @csrf @method('DELETE')
                        <button class="border px-3 py-2 rounded text-sm text-red-600">Hapus</button>
                    </form>
                @endcan
            @endif

            @if ($stockRequest->isSubmitted())
                @can('distribution.manage')
                    <a href="{{ route('admin.distribution.bkb.create', ['stock_request_id' => $stockRequest->id]) }}" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Buat BKB Distribusi</a>
                @endcan
            @endif

            @if (in_array($stockRequest->status, ['draft', 'submitted']))
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.stock-requests.cancel', $stockRequest) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button class="border px-3 py-2 rounded text-sm">Batalkan</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</x-admin-layout>
