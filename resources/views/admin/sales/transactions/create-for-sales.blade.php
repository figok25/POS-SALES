<x-admin-layout>
    <div class="frm-page" style="max-width: 860px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Input Transaksi atas Nama Sales</h1>
                <p class="frm-sub">Untuk transaksi yang sudah dilakukan Sales tetapi belum tercatat. Transaksi tercatat sebagai milik <strong>Sales yang dipilih</strong>; Anda dicatat sebagai pengguna yang menginput. Stok Sales terpotong dan invoice terbit seperti transaksi biasa. Pakai juga untuk pembelian tambahan outlet di minggu yang sudah dikunjungi: transaksi dihitung sebagai Call Made + EC Sales pada tanggalnya, <strong>tanpa menambah atau membuka kuota kunjungan mingguan</strong>. Sales dapat melihat dan mencetak struknya di aplikasi.</p>
            </div>
            <a href="{{ route('admin.sales.transactions.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        {{-- Langkah 1: pilih Sales (halaman memuat ulang customer, stok & harga Sales itu) --}}
        <div class="panel" style="margin-bottom:16px;">
            <form method="GET" action="{{ route('admin.sales.transactions.create-for-sales') }}" style="padding:16px 22px;display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
                <div class="frm-field" style="margin:0;min-width:240px">
                    <label class="frm-label" for="pickSales">Sales <span class="frm-req">*</span></label>
                    <select id="pickSales" name="sales_id" class="frm-input is-select" onchange="this.form.submit()">
                        <option value="">- Pilih Sales -</option>
                        @foreach ($salesList as $s)
                            <option value="{{ $s->id }}" @selected($sales && $sales->id === $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button class="adm-btn adm-btn-ghost">Pilih</button></noscript>
            </form>
        </div>

        @if ($sales)
            @if ($stocks->isEmpty())
                <div class="frm-alert is-warn" role="status"><span class="frm-alert-text">Stok {{ $sales->name }} kosong. Transaksi tidak bisa dicatat sebelum stoknya ada (mis. lewat BKB atau koreksi stok).</span></div>
            @endif

            <div class="panel">
                <form method="POST" action="{{ route('admin.sales.transactions.store-for-sales') }}">
                    @csrf
                    <input type="hidden" name="sales_id" value="{{ $sales->id }}">
                    <div class="frm-modal-body" style="padding: 22px;">
                        <div class="frm-grid">
                            <div class="frm-field">
                                <label class="frm-label" for="efCustomer">Outlet / Customer <span class="frm-req">*</span></label>
                                <select id="efCustomer" name="customer_id" class="frm-input is-select" required>
                                    <option value="">- Pilih Outlet -</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" @selected((int) old('customer_id') === (int) $c->id)>{{ $c->name }} ({{ $c->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="efDate">Tanggal Transaksi (sebenarnya) <span class="frm-req">*</span></label>
                                <input id="efDate" type="date" name="transaction_date" required max="{{ now()->toDateString() }}" value="{{ old('transaction_date', now()->toDateString()) }}" class="frm-input">
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="efDiscount">Diskon (Rp)</label>
                                <input id="efDiscount" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" class="frm-input">
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="efReason">Catatan Input Bantuan <span class="frm-req">*</span></label>
                                <input id="efReason" type="text" name="entry_reason" required maxlength="500" value="{{ old('entry_reason') }}" class="frm-input" placeholder="mis. Sales lupa input transaksi">
                            </div>
                            <div class="frm-field is-full">
                                <label class="frm-label" for="efNotes">Keterangan Transaksi</label>
                                <textarea id="efNotes" name="notes" rows="2" class="frm-input is-area">{{ old('notes') }}</textarea>
                            </div>

                            <div class="frm-field is-full frm-section">
                                <div class="frm-items-head">
                                    <label class="frm-label" style="margin:0;">Item Penjualan <span class="frm-req">*</span></label>
                                    <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" onclick="addItemRow()">Tambah Item</button>
                                </div>
                                <div class="frm-items-wrap">
                                    <table class="frm-items-table">
                                        <thead><tr><th>Produk (stok Sales)</th><th class="is-end">Qty</th><th class="is-end">Harga (Rp)</th><th></th></tr></thead>
                                        <tbody id="items-body"></tbody>
                                    </table>
                                    <p class="frm-items-empty" id="items-empty" hidden>Belum ada item. Klik "Tambah Item".</p>
                                </div>
                                <p class="frm-meta">Harga terisi otomatis dari harga {{ $sales->typeLabel() }} dan boleh disesuaikan. Program Beli-Gratis yang aktif di Depo ini ikut diterapkan otomatis.</p>
                            </div>

                            <div class="frm-field is-full">
                                <label class="inline-flex items-center text-sm">
                                    <input type="checkbox" name="allow_duplicate" value="1" @checked(old('allow_duplicate')) class="mr-2"> Tetap simpan walau terdeteksi transaksi yang sama (hanya bila memang transaksi berbeda)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="frm-modal-foot">
                        <a href="{{ route('admin.sales.transactions.index') }}" class="adm-btn adm-btn-ghost">Batal</a>
                        <button type="submit" class="adm-btn adm-btn-primary" onclick="return confirm('Catat transaksi ini atas nama {{ $sales->name }}? Stok Sales akan dipotong.')">Simpan Transaksi</button>
                    </div>
                </form>
            </div>

            @php
                $fmtQty = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                $productData = [];
                foreach ($stocks as $st) {
                    $productData[] = [
                        'id' => $st->product_id,
                        'label' => $st->product->name . ' (' . $fmtQty($st->quantity) . ')',
                        'price' => (float) ($prices[$st->product_id] ?? 0),
                    ];
                }
            @endphp
            <script>
                const products = @json($productData);
                const oldItems = @json(old('items'));
                let rowIndex = 0;

                function productOptions(selected = '') {
                    let html = '<option value="">- Pilih Produk -</option>';
                    products.forEach(p => { html += `<option value="${p.id}" data-price="${p.price}" ${String(p.id) === String(selected) ? 'selected' : ''}>${p.label}</option>`; });
                    return html;
                }
                function updateEmptyState() {
                    var empty = document.getElementById('items-empty');
                    if (empty) empty.hidden = document.querySelectorAll('#items-body tr').length > 0;
                }
                function fillPrice(select) {
                    var opt = select.options[select.selectedIndex];
                    var input = select.closest('tr').querySelector('input[name$="[price]"]');
                    if (opt && opt.dataset.price !== undefined && input && (input.value === '' || input.dataset.auto === '1')) {
                        input.value = opt.dataset.price; input.dataset.auto = '1';
                    }
                }
                function addItemRow(productId = '', quantity = '', price = '') {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><select name="items[${rowIndex}][product_id]" class="frm-input is-select" required onchange="fillPrice(this)">${productOptions(productId)}</select></td>
                        <td><input type="number" step="0.01" min="0.01" value="${quantity}" name="items[${rowIndex}][quantity]" class="frm-input" style="text-align:right;" required></td>
                        <td><input type="number" step="0.01" min="0" value="${price}" name="items[${rowIndex}][price]" class="frm-input" style="text-align:right;" required oninput="this.dataset.auto='0'"></td>
                        <td class="is-end"><button type="button" class="frm-items-remove" aria-label="Hapus baris" onclick="this.closest('tr').remove(); updateEmptyState();">&times;</button></td>`;
                    document.getElementById('items-body').appendChild(tr);
                    rowIndex++;
                    updateEmptyState();
                }
                if (oldItems) { Object.values(oldItems).forEach(l => addItemRow(l.product_id, l.quantity, l.price)); } else { addItemRow(); }
            </script>
        @endif
    </div>
</x-admin-layout>
