<x-admin-layout>
    <x-slot name="header">Buat Draft BKB Distribusi</x-slot>

    <div class="frm-page" style="max-width: 760px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Buat Draft BKB Distribusi</h1>
                <p class="frm-sub">Dokumen tersimpan sebagai Draft. Stok baru berubah setelah di-Check dan di-Apply pada halaman detail.</p>
            </div>
            <a href="{{ route('admin.distribution.bkb.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                <span class="frm-alert-text">{{ $errors->first() }}</span>
            </div>
        @endif

        @if ($stockRequest)
            <div class="frm-alert is-info" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
                <span class="frm-alert-text">Dibuat dari Permintaan Barang <strong>{{ $stockRequest->code }}</strong>. Item di bawah sudah terisi otomatis.</span>
            </div>
        @endif

        <div class="panel">
            <form method="POST" action="{{ route('admin.distribution.bkb.store') }}">
                @csrf
                <input type="hidden" name="stock_request_id" value="{{ $stockRequest->id ?? old('stock_request_id') }}">

                <div class="frm-modal-body" style="padding: 22px;">
                    <div class="frm-grid">
                        <div class="frm-field">
                            <label class="frm-label" for="bkbWarehouse">Warehouse Asal <span class="frm-req">*</span></label>
                            <select id="bkbWarehouse" name="warehouse_id" class="frm-input is-select" required>
                                <option value="">- Pilih Warehouse -</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id', $stockRequest->warehouse_id ?? null) == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="bkbSales">Sales Tujuan <span class="frm-req">*</span></label>
                            <select id="bkbSales" name="sales_id" class="frm-input is-select" required>
                                <option value="">- Pilih Sales -</option>
                                @foreach ($salesList as $s)
                                    <option value="{{ $s->id }}" @selected(old('sales_id', $stockRequest->sales_id ?? null) == $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="frm-field is-full">
                            <label class="frm-label" for="bkbNotes">Catatan</label>
                            <textarea id="bkbNotes" name="notes" rows="2" class="frm-input is-area">{{ old('notes') }}</textarea>
                        </div>

                        <div class="frm-field is-full frm-section">
                            <div class="frm-items-head">
                                <label class="frm-label" style="margin:0;">Item Produk <span class="frm-req">*</span></label>
                                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" onclick="addItemRow()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    Tambah Item
                                </button>
                            </div>
                            <div class="frm-items-wrap">
                                <table class="frm-items-table">
                                    <colgroup><col><col class="is-qty"><col class="is-remove"></colgroup>
                                    <thead><tr><th>Produk</th><th class="is-end">Quantity</th><th></th></tr></thead>
                                    <tbody id="items-body"></tbody>
                                </table>
                                <p class="frm-items-empty" id="items-empty" hidden>Belum ada item. Klik "Tambah Item".</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="frm-modal-foot">
                    <a href="{{ route('admin.distribution.bkb.index') }}" class="adm-btn adm-btn-ghost">Batal</a>
                    <button type="submit" class="adm-btn adm-btn-primary">Simpan Draft</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->name.' ('.$p->sku.')']));
        const prefill = @json($stockRequest ? $stockRequest->items->map(fn($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity]) : []);
        let rowIndex = 0;

        function productOptions(selected = '') {
            let html = '<option value="">- Pilih Produk -</option>';
            products.forEach(p => {
                html += `<option value="${p.id}" ${String(p.id) === String(selected) ? 'selected' : ''}>${p.label}</option>`;
            });
            return html;
        }

        function updateEmptyState() {
            var empty = document.getElementById('items-empty');
            var count = document.querySelectorAll('#items-body tr').length;
            if (empty) empty.hidden = count > 0;
        }

        function addItemRow(productId = '', quantity = '') {
            const tbody = document.getElementById('items-body');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select name="items[${rowIndex}][product_id]" class="frm-input is-select" required>
                        ${productOptions(productId)}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" value="${quantity}" name="items[${rowIndex}][quantity]" class="frm-input" style="text-align:right;" required>
                </td>
                <td class="is-end">
                    <button type="button" class="frm-items-remove" aria-label="Hapus baris" onclick="this.closest('tr').remove(); updateEmptyState();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
            updateEmptyState();
        }

        if (prefill.length > 0) {
            prefill.forEach(line => addItemRow(line.product_id, line.quantity));
        } else {
            addItemRow();
        }
    </script>
</x-admin-layout>
