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
    @elseif (! $visit)
        <div class="bg-white rounded-lg shadow p-4 text-sm text-gray-600 space-y-3">
            <p>Untuk membuat transaksi, <strong>Check-in</strong> dulu ke toko yang Anda kunjungi.</p>
            <a href="{{ route('sales.visits.create') }}" class="block text-center bg-indigo-600 text-white px-3 py-2 rounded text-sm">Check-in Kunjungan</a>
        </div>
    @elseif ($visit->isOutletClosed())
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
            Toko <strong>{{ $visit->customer->name ?? '-' }}</strong> tercatat tutup pada kunjungan ini, jadi transaksi tidak bisa dibuat.
            Check-out dulu; bila toko sudah buka, lakukan satu kali kunjungan ulang.
            <a href="{{ route('sales.visits.index') }}" class="block text-center mt-3 bg-amber-600 text-white px-3 py-2 rounded text-sm">Ke Kunjungan</a>
        </div>
    @else
        <form method="POST" action="{{ route('sales.transactions.store') }}" x-data="salesTransactionForm()" class="bg-white rounded-lg shadow p-4 space-y-3">
            @csrf
            <div class="flex items-center justify-between text-xs bg-indigo-50 text-indigo-700 rounded px-3 py-2">
                <span>Kategori harga</span>
                <span class="font-semibold">{{ $sales->typeLabel() }}</span>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Customer (kunjungan berjalan)</label>
                <input type="hidden" name="customer_id" value="{{ $visit->customer_id }}">
                <div class="border rounded px-3 py-2 text-sm bg-gray-50">
                    {{ $visit->customer->name ?? '-' }}@if ($visit->customer?->code) <span class="text-gray-500">({{ $visit->customer->code }})</span>@endif
                </div>
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
                            <span class="text-xs" :class="row.product_id && !priceOf(row.product_id) ? 'text-red-600' : 'text-gray-500'"
                                  x-text="row.product_id && !priceOf(row.product_id) ? 'Harga {{ $sales->typeLabel() }} belum diatur' : 'Harga: Rp ' + formatRupiah(priceOf(row.product_id))"></span>
                            <span class="text-xs text-gray-700 ml-auto font-medium" x-text="'Subtotal: Rp ' + formatRupiah(priceOf(row.product_id) * (row.quantity || 0))"></span>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="freeLines().length || promoHints().length">
                <div class="rounded border border-green-200 bg-green-50 p-2 text-xs text-green-800 space-y-1">
                    <template x-for="f in freeLines()" :key="f.key">
                        <div>🎁 <strong>GRATIS</strong> <span x-text="f.qty"></span> &times; <span x-text="f.name"></span>
                            <span x-show="f.short" class="text-amber-700" x-text="'(stok gratis hanya ' + f.qty + ', seharusnya ' + f.wanted + ')'"></span>
                        </div>
                    </template>
                    <template x-for="h in promoHints()" :key="h.key">
                        <div class="text-gray-600" x-text="h.text"></div>
                    </template>
                </div>
            </template>

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

            const stockQty = {
                @foreach ($myStock as $stock)
                    "{{ $stock->product_id }}": {{ (float) $stock->quantity }},
                @endforeach
            };
            const promos = @json($promos);
            const fmt = (n) => (Math.round(n * 100) / 100).toString();

            return {
                rows: [{ product_id: '', quantity: null }],
                boughtQty(productId) {
                    return this.rows.reduce((sum, r) => sum + (String(r.product_id) === String(productId) ? (Number(r.quantity) || 0) : 0), 0);
                },
                // Pratinjau barang gratis (hitungan final dilakukan server, dibatasi stok Sales).
                freeLines() {
                    const left = {};
                    const out = [];
                    promos.forEach((p, i) => {
                        const bought = this.boughtQty(p.buy_id);
                        const wanted = Math.floor(Math.round(bought / p.buy_qty * 1e6) / 1e6) * p.free_qty;
                        if (wanted <= 0) return;
                        if (left[p.free_id] === undefined) left[p.free_id] = (stockQty[p.free_id] || 0) - this.boughtQty(p.free_id);
                        const give = Math.min(wanted, Math.max(0, left[p.free_id]));
                        if (give <= 0) return;
                        left[p.free_id] -= give;
                        out.push({ key: 'f' + i, name: p.free_name, qty: fmt(give), wanted: fmt(wanted), short: give < wanted });
                    });
                    return out;
                },
                // Petunjuk program untuk produk yang sudah dipilih tapi belum cukup kelipatan.
                promoHints() {
                    return promos
                        .map((p, i) => ({ p, i, bought: this.boughtQty(p.buy_id) }))
                        .filter((x) => x.bought > 0 && x.bought < x.p.buy_qty)
                        .map((x) => ({ key: 'h' + x.i, text: 'Program: beli ' + fmt(x.p.buy_qty) + ' ' + x.p.buy_name + ' gratis ' + fmt(x.p.free_qty) + ' ' + x.p.free_name }));
                },
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
