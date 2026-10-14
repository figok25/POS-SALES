<x-admin-layout>
    <x-slot name="header">{{ $title }} {{ $doc->code }}</x-slot>

    <div class="frm-page" style="max-width: 760px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">{{ $title }} — {{ $doc->code }}</h1>
                <p class="frm-sub">
                    @if ($isApplied)
                        Dokumen sudah <strong>Applied</strong>. Stok disesuaikan otomatis berdasarkan <strong>selisih</strong> data lama dan baru. {{ $stockHint }}
                    @else
                        Dokumen belum di-Apply, jadi stok tidak berubah.
                    @endif
                </p>
            </div>
            <a href="{{ $backUrl }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if (session('error'))
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        <div class="panel">
            <form method="POST" action="{{ $action }}">
                @csrf
                @method('PUT')
                <div class="frm-modal-body" style="padding: 22px;">
                    <div class="frm-grid">
                        <div class="frm-field">
                            <label class="frm-label">Sales</label>
                            <input type="text" class="frm-input" value="{{ $doc->sales->name ?? '-' }}" disabled>
                        </div>
                        <div class="frm-field">
                            <label class="frm-label">Warehouse</label>
                            <input type="text" class="frm-input" value="{{ $doc->warehouse->name ?? '-' }}" disabled>
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="docDate">Tanggal Transaksi</label>
                            <input id="docDate" type="date" name="document_date" max="{{ now()->toDateString() }}" value="{{ old('document_date', $doc->document_date?->toDateString() ?? $doc->created_at?->toDateString()) }}" class="frm-input">
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="docReason">Alasan Revisi <span class="frm-req">*</span></label>
                            <input id="docReason" type="text" name="reason" value="{{ old('reason') }}" maxlength="500" required class="frm-input" placeholder="mis. Salah input jumlah, seharusnya 15">
                        </div>
                        <div class="frm-field is-full">
                            <label class="frm-label" for="docNotes">Keterangan</label>
                            <textarea id="docNotes" name="notes" rows="2" class="frm-input is-area">{{ old('notes', $doc->notes) }}</textarea>
                        </div>

                        <div class="frm-field is-full frm-section">
                            <div class="frm-items-head">
                                <label class="frm-label" style="margin:0;">Item Produk <span class="frm-req">*</span></label>
                                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" onclick="addItemRow()">Tambah Item</button>
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
                    <a href="{{ $backUrl }}" class="adm-btn adm-btn-ghost">Batal</a>
                    <button type="submit" class="adm-btn adm-btn-primary" onclick="return confirm('Simpan revisi {{ $kind }} ini?{{ $isApplied ? ' Stok akan disesuaikan.' : '' }}')">Simpan Revisi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->name.' ('.$p->sku.')']));
        const oldItems = @json(old('items'));
        const prefill = oldItems ? Object.values(oldItems) : @json($doc->items->map(fn($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity]));
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
            if (empty) empty.hidden = document.querySelectorAll('#items-body tr').length > 0;
        }
        function addItemRow(productId = '', quantity = '') {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select name="items[${rowIndex}][product_id]" class="frm-input is-select" required>${productOptions(productId)}</select></td>
                <td><input type="number" step="0.01" min="0.01" value="${quantity}" name="items[${rowIndex}][quantity]" class="frm-input" style="text-align:right;" required></td>
                <td class="is-end"><button type="button" class="frm-items-remove" aria-label="Hapus baris" onclick="this.closest('tr').remove(); updateEmptyState();">&times;</button></td>`;
            document.getElementById('items-body').appendChild(tr);
            rowIndex++;
            updateEmptyState();
        }
        if (prefill.length > 0) { prefill.forEach(l => addItemRow(l.product_id, l.quantity)); } else { addItemRow(); }
    </script>
</x-admin-layout>
