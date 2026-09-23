<x-sales-layout>
    <x-slot name="header">Transaksi Baru</x-slot>

    @if ($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($myStock->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            Sales Stock Anda kosong. Hubungi Admin untuk pengiriman barang (BKB) terlebih dahulu.
        </div>
    @elseif ($customers->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-sm text-gray-500">
            Tidak ada toko terjadwal untuk hari ini di Rute Kanvas Anda, jadi belum bisa membuat transaksi baru.
        </div>
    @else
        @if ($usingFallback)
            <div class="mb-3 p-3 bg-amber-50 text-amber-800 rounded text-xs">
                Rute Kanvas Anda belum pernah diatur Admin -- daftar Customer di bawah menampilkan semua toko yang di-assign ke Anda untuk sementara.
            </div>
        @endif
        <form method="POST" action="{{ route('sales.transactions.store') }}" x-data="salesTransactionForm()" class="bg-white rounded-lg shadow p-4 space-y-3">
            @csrf
            <div>
                <label class="block text-sm text-gray-600 mb-1">Customer</label>
                <select name="customer_id" required class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">- Pilih Customer -</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-sm text-gray-600">Produk</label>
                    <button type="button" @click="addRow()" class="text-indigo-600 text-sm">+ Tambah Baris</button>
                </div>

                <template x-for="(row, index) in rows" :key="index">
                    <div class="border rounded p-2 mb-2">
                        <div class="flex gap-2 items-start">
                            <select :name="'items[' + index + '][product_id]'" x-model="row.product_id" required class="flex-1 border rounded px-2 py-1.5 text-sm">
                                <option value="">- Produk -</option>
                                @foreach ($myStock as $stock)
                                    <option value="{{ $stock->product_id }}">{{ $stock->product->name ?? '-' }} (stok: {{ rtrim(rtrim(number_format($stock->quantity, 2, '.', ''), '0'), '.') }})</option>
                                @endforeach
                            </select>
                            <button type="button" @click="removeRow(index)" class="text-red-500 text-sm px-2">&times;</button>
                        </div>
                        <div class="flex gap-2 mt-2 items-center">
                            <input type="number" step="0.01" min="0.01" :name="'items[' + index + '][quantity]'" x-model.number="row.quantity" placeholder="Qty" required class="w-24 border rounded px-2 py-1.5 text-sm">
                            <span class="text-xs text-gray-500" x-text="'Harga: Rp ' + formatRupiah(priceOf(row.product_id))"></span>
                            <span class="text-xs text-gray-700 ml-auto font-medium" x-text="'Subtotal: Rp ' + formatRupiah(priceOf(row.product_id) * (row.quantity || 0))"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="text-right text-sm font-semibold border-t pt-2">
                Total: Rp <span x-text="formatRupiah(grandTotal())"></span>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm">Simpan Transaksi</button>
            <a href="{{ route('sales.transactions.index') }}" class="block text-center text-sm text-gray-500">Batal</a>
        </form>
    @endif

    <script>
        function salesTransactionForm() {
            const prices = {
                @foreach ($myStock as $stock)
                    "{{ $stock->product_id }}": {{ $prices[$stock->product_id] ?? 0 }},
                @endforeach
            };

            return {
                rows: [{ product_id: '', quantity: null }],
                addRow() {
                    this.rows.push({ product_id: '', quantity: null });
                },
                removeRow(index) {
                    if (this.rows.length > 1) this.rows.splice(index, 1);
                },
                priceOf(productId) {
                    return prices[productId] || 0;
                },
                grandTotal() {
                    return this.rows.reduce((sum, row) => sum + (this.priceOf(row.product_id) * (row.quantity || 0)), 0);
                },
                formatRupiah(value) {
                    return new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
                },
            };
        }
    </script>
</x-sales-layout>
