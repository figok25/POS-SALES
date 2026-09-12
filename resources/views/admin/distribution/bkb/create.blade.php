<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <h1 class="text-xl font-semibold mb-4">Buat Draft BKB Distribusi</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($stockRequest)
            <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded text-sm">
                Dibuat dari Permintaan Barang <strong>{{ $stockRequest->code }}</strong>. Item di bawah sudah terisi otomatis.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.distribution.bkb.store') }}" class="bg-white p-4 rounded shadow">
            @csrf
            <input type="hidden" name="stock_request_id" value="{{ $stockRequest->id ?? old('stock_request_id') }}">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Warehouse Asal *</label>
                    <select name="warehouse_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Pilih Warehouse --</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id', $stockRequest->warehouse_id ?? null) == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Sales Tujuan *</label>
                    <select name="sales_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Pilih Sales --</option>
                        @foreach ($salesList as $s)
                            <option value="{{ $s->id }}" @selected(old('sales_id', $stockRequest->sales_id ?? null) == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium">Item Produk *</label>
                <button type="button" onclick="addItemRow()" class="text-sm bg-gray-200 px-2 py-1 rounded">+ Tambah Item</button>
            </div>

            <table class="w-full text-sm mb-4" id="items-table">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-2 py-2 text-left">Product</th>
                        <th class="px-2 py-2 text-left w-32">Quantity</th>
                        <th class="px-2 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="items-body"></tbody>
            </table>

            <p class="text-xs text-gray-500 mb-4">Dokumen tersimpan sebagai Draft. Stok baru berubah setelah di-Check dan di-Apply pada halaman detail (Blueprint #8).</p>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.distribution.bkb.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan Draft</button>
            </div>
        </form>
    </div>

    <script>
        const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->name.' ('.$p->sku.')']));
        const prefill = @json($stockRequest ? $stockRequest->items->map(fn($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity]) : []);
        let rowIndex = 0;

        function productOptions(selected = '') {
            let html = '<option value="">-- Pilih Product --</option>';
            products.forEach(p => {
                html += `<option value="${p.id}" ${String(p.id) === String(selected) ? 'selected' : ''}>${p.label}</option>`;
            });
            return html;
        }

        function addItemRow(productId = '', quantity = '') {
            const tbody = document.getElementById('items-body');
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-2">
                    <select name="items[${rowIndex}][product_id]" class="w-full border rounded px-2 py-1.5 text-sm" required>
                        ${productOptions(productId)}
                    </select>
                </td>
                <td class="px-2 py-2">
                    <input type="number" step="0.01" min="0.01" value="${quantity}" name="items[${rowIndex}][quantity]" class="w-full border rounded px-2 py-1.5 text-sm" required>
                </td>
                <td class="px-2 py-2 text-center">
                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-600">&times;</button>
                </td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
        }

        if (prefill.length > 0) {
            prefill.forEach(line => addItemRow(line.product_id, line.quantity));
        } else {
            addItemRow();
        }
    </script>
</x-admin-layout>
