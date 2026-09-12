<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <h1 class="text-xl font-semibold mb-4">Terima Barang - {{ $transfer->code }} (BTB Cabang)</h1>

        <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded text-sm">
            Tahap Check: sesuaikan Quantity Diterima bila ada selisih dengan Quantity Dikirim. Apply akan menambah stok Warehouse Tujuan sesuai Quantity Diterima.
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.distribution.branch-transfer.receive', $transfer) }}" class="bg-white p-4 rounded shadow" onsubmit="return confirm('Apply BTB Cabang? Stok warehouse tujuan akan bertambah.')">
            @csrf

            <table class="w-full text-sm mb-4">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-2 py-2 text-left">Product</th>
                        <th class="px-2 py-2 text-right">Qty Dikirim</th>
                        <th class="px-2 py-2 text-right w-40">Qty Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfer->items as $line)
                        <tr class="border-b">
                            <td class="px-2 py-2">
                                {{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})
                                <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $line->id }}">
                            </td>
                            <td class="px-2 py-2 text-right">{{ number_format($line->quantity_sent, 2) }}</td>
                            <td class="px-2 py-2 text-right">
                                <input type="number" step="0.01" min="0" value="{{ old('items.'.$loop->index.'.quantity_received', $line->quantity_sent) }}" name="items[{{ $loop->index }}][quantity_received]" class="w-full border rounded px-2 py-1.5 text-sm text-right" required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.distribution.branch-transfer.show', $transfer) }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Apply (BTB Cabang)</button>
            </div>
        </form>
    </div>
</x-admin-layout>
