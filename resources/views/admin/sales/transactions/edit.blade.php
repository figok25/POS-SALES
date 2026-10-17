<x-admin-layout>
    @php
        $qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $paidLines = $transaction->items->where('is_free', false)->values();
        $freeLines = $transaction->items->where('is_free', true)->values();
        $paid = (float) ($transaction->invoice->paid_amount ?? 0);
    @endphp

    <div class="frm-page" style="max-width: 860px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Edit Transaksi {{ $transaction->code }}</h1>
                <p class="frm-sub">
                    Stok ({{ $transaction->isDepoSale() ? 'Gudang Depo' : 'Stok Sales '.($transaction->sales->name ?? '') }}), invoice, dan piutang dihitung ulang dari <strong>selisih</strong> data lama dan baru.
                    @if ($paid > 0) Pembayaran yang sudah masuk: <strong>Rp {{ number_format($paid, 0, ',', '.') }}</strong>. Bila total baru lebih kecil, centang "Sesuaikan pembayaran" di bawah.@endif
                </p>
            </div>
            <a href="{{ route('admin.sales.transactions.show', $transaction) }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if (session('error'))
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        <div class="panel">
            <form method="POST" action="{{ route('admin.sales.transactions.update', $transaction) }}">
                @csrf
                @method('PUT')
                <div class="frm-modal-body" style="padding: 22px;">
                    <div class="frm-grid">
                        <div class="frm-field">
                            <label class="frm-label">Sales / Sumber</label>
                            <input type="text" class="frm-input" disabled value="{{ $transaction->isDepoSale() ? 'Penjualan langsung Depo' : ($transaction->sales->name ?? '-') }}">
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="trxDate">Tanggal Transaksi</label>
                            <input id="trxDate" type="date" name="transaction_date" max="{{ now()->toDateString() }}" value="{{ old('transaction_date', $transaction->created_at->toDateString()) }}" class="frm-input">
                        </div>

                        @if ($transaction->isDepoSale())
                            <div class="frm-field">
                                <label class="frm-label" for="trxConsumer">Nama Pembeli</label>
                                <input id="trxConsumer" type="text" name="consumer_name" maxlength="120" value="{{ old('consumer_name', $transaction->consumer_name) }}" class="frm-input">
                            </div>
                        @else
                            <div class="frm-field">
                                <label class="frm-label" for="trxCustomer">Outlet / Customer</label>
                                <select id="trxCustomer" name="customer_id" class="frm-input is-select">
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" @selected((int) old('customer_id', $transaction->customer_id) === (int) $c->id)>{{ $c->name }} ({{ $c->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (! empty($priceTypes))
                            <div class="frm-field">
                                <label class="frm-label" for="trxPriceType">Kategori Harga</label>
                                <select id="trxPriceType" name="price_type" class="frm-input is-select">
                                    @foreach ($priceTypes as $ptValue => $ptLabel)
                                        <option value="{{ $ptValue }}" @selected(old('price_type', $transaction->price_type) === $ptValue)>{{ $ptLabel }}</option>
                                    @endforeach
                                </select>
                                <p class="frm-meta">Mengganti kategori mengisi ulang harga tiap baris dari harga master kategori itu (boleh disesuaikan lagi).</p>
                            </div>
                        @endif
                        <div class="frm-field">
                            <label class="frm-label" for="trxDiscount">Diskon (Rp)</label>
                            <input id="trxDiscount" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', $qty($transaction->discount)) }}" class="frm-input">
                        </div>
                        <div class="frm-field is-full">
                            <label class="frm-label" for="trxNotes">Keterangan</label>
                            <textarea id="trxNotes" name="notes" rows="2" class="frm-input is-area">{{ old('notes', $transaction->notes) }}</textarea>
                        </div>
                        <div class="frm-field is-full">
                            <label class="frm-label" for="trxReason">Alasan Koreksi <span class="frm-req">*</span></label>
                            <input id="trxReason" type="text" name="reason" required maxlength="500" value="{{ old('reason') }}" class="frm-input" placeholder="mis. Salah input qty, seharusnya 15 bukan 20">
                        </div>
                        @if ($paid > 0)
                            <div class="frm-field is-full">
                                <label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;">
                                    <input type="checkbox" name="adjust_payment" value="1" @checked(old('adjust_payment')) style="margin-top:3px;">
                                    <span><strong>Sesuaikan pembayaran ke total baru</strong><br>
                                        <small>Jika total baru lebih kecil dari pembayaran yang sudah masuk (Rp {{ number_format($paid, 0, ',', '.') }}), selisihnya dikurangkan dari pembayaran terbaru secara otomatis dan dicatat di audit log. Pembayaran yang sudah masuk Settlement tidak bisa diubah.</small></span>
                                </label>
                            </div>
                        @endif

                        <div class="frm-field is-full frm-section">
                            <div class="frm-items-head">
                                <label class="frm-label" style="margin:0;">Item Penjualan</label>
                                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" onclick="addItemRow()">Tambah Item</button>
                            </div>
                            <div class="frm-items-wrap">
                                <table class="frm-items-table">
                                    <thead><tr><th>Produk</th><th class="is-end">Qty</th><th class="is-end">Harga (Rp)</th><th></th></tr></thead>
                                    <tbody id="items-body"></tbody>
                                </table>
                                <p class="frm-items-empty" id="items-empty" hidden>Belum ada item bayar. Klik "Tambah Item".</p>
                            </div>
                        </div>

                        @if ($freeLines->isNotEmpty())
                            <div class="frm-field is-full frm-section">
                                <label class="frm-label">Barang Gratis Program</label>
                                <div class="frm-items-wrap">
                                    <table class="frm-items-table">
                                        <thead><tr><th>Produk</th><th class="is-end">Qty Gratis</th></tr></thead>
                                        <tbody>
                                            @foreach ($freeLines as $f)
                                                <tr>
                                                    <td>{{ $f->product->name ?? '-' }}@if ($f->shell_qty) <em>(tukar {{ $qty($f->shell_qty) }} cangkang)</em>@endif</td>
                                                    <td><input type="number" step="0.01" min="0" name="free[{{ $f->id }}]" value="{{ old('free.'.$f->id, $qty($f->quantity)) }}" class="frm-input" style="text-align:right;"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <p class="frm-meta">Isi 0 untuk menghapus barang gratis. Stok ikut disesuaikan.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="frm-modal-foot">
                    <a href="{{ route('admin.sales.transactions.show', $transaction) }}" class="adm-btn adm-btn-ghost">Batal</a>
                    <button type="submit" class="adm-btn adm-btn-primary" onclick="return confirm('Simpan koreksi transaksi ini? Stok dan invoice akan disesuaikan.')">Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->name.' ('.$p->sku.')']));
        const oldItems = @json(old('items'));
        const prefill = oldItems ? Object.values(oldItems) : @json($paidLines->map(fn($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity, 'price' => (float) $i->price]));
        let rowIndex = 0;

        function productOptions(selected = '') {
            let html = '<option value="">- Pilih Produk -</option>';
            products.forEach(p => { html += `<option value="${p.id}" ${String(p.id) === String(selected) ? 'selected' : ''}>${p.label}</option>`; });
            return html;
        }
        function updateEmptyState() {
            var empty = document.getElementById('items-empty');
            if (empty) empty.hidden = document.querySelectorAll('#items-body tr').length > 0;
        }
        function addItemRow(productId = '', quantity = '', price = '') {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select name="items[${rowIndex}][product_id]" class="frm-input is-select" required>${productOptions(productId)}</select></td>
                <td><input type="number" step="0.01" min="0.01" value="${quantity}" name="items[${rowIndex}][quantity]" class="frm-input" style="text-align:right;" required></td>
                <td><input type="number" step="0.01" min="0" value="${price}" name="items[${rowIndex}][price]" class="frm-input" style="text-align:right;" required></td>
                <td class="is-end"><button type="button" class="frm-items-remove" aria-label="Hapus baris" onclick="this.closest('tr').remove(); updateEmptyState();">&times;</button></td>`;
            document.getElementById('items-body').appendChild(tr);
            rowIndex++;
            updateEmptyState();
        }
        const pricesByType = @json($pricesByType ?? []);
        const priceTypeEl = document.getElementById('trxPriceType');
        if (priceTypeEl) {
            priceTypeEl.addEventListener('change', function () {
                const map = pricesByType[priceTypeEl.value] || {};
                document.querySelectorAll('#items-body tr').forEach(function (tr) {
                    const productId = tr.querySelector('select[name$="[product_id]"]').value;
                    const input = tr.querySelector('input[name$="[price]"]');
                    if (productId && map[productId] !== undefined && input) input.value = map[productId];
                });
            });
        }
        prefill.forEach(l => addItemRow(l.product_id, l.quantity, l.price));
        updateEmptyState();
    </script>
</x-admin-layout>
