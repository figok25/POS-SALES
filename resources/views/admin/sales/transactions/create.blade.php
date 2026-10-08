<x-admin-layout>
    @php
        $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $ready = $branchId && $warehouseId && $customerId;
    @endphp

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Penjualan Langsung Depo</h1>
                <p class="frm-sub">Jual ke customer/konsumen langsung dari Gudang Depo. Tidak terkait Sales, tanpa Delivery Order.</p>
            </div>
            <div class="frm-head-actions">
                <a href="{{ route('admin.sales.transactions.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Kembali</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="panel" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;padding:12px 16px;margin-bottom:12px">
                <ul style="margin:0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Langkah 1: Depo, Gudang, Kategori Harga, Customer --}}
        <section class="panel" style="margin-bottom:12px">
            <div class="panel-head"><h2 class="panel-title">1. Gudang &amp; Customer</h2></div>
            <form method="GET" action="{{ route('admin.sales.transactions.create') }}" class="frm-toolbar" style="flex-wrap:wrap;gap:8px">
                @if ($isAll)
                    <select name="branch_id" class="frm-input is-select" aria-label="Depo" onchange="this.form.submit()">
                        <option value="">- Pilih Depo -</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected($branchId === $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="warehouse_id" class="frm-input is-select" aria-label="Gudang" onchange="this.form.submit()" @disabled(! $branchId)>
                    <option value="">- Pilih Gudang -</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($warehouseId === $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>

                <select name="price_type" class="frm-input is-select" aria-label="Kategori Harga" onchange="this.form.submit()">
                    @foreach ($priceTypes as $val => $label)
                        <option value="{{ $val }}" @selected($priceType === $val)>Harga {{ $label }}</option>
                    @endforeach
                </select>

                <select name="customer_id" class="frm-input is-select" aria-label="Customer" onchange="this.form.submit()" @disabled(! $branchId)>
                    <option value="">- Pilih Customer -</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected($customerId === $c->id)>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>

                <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan</button></noscript>
            </form>
            @if ($branchId && $warehouses->isEmpty())
                <p class="frm-meta" style="padding:0 16px 12px">Depo ini belum punya Gudang aktif.</p>
            @endif
        </section>

        @if ($ready)
            @if ($stocks->isEmpty())
                <div class="panel" style="padding:16px">Gudang ini tidak punya stok yang bisa dijual.</div>
            @else
                <form method="POST" action="{{ route('admin.sales.transactions.store') }}">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                    <input type="hidden" name="customer_id" value="{{ $customerId }}">
                    <input type="hidden" name="price_type" value="{{ $priceType }}">

                    <section class="panel" style="margin-bottom:12px">
                        <div class="panel-head">
                            <h2 class="panel-title">2. Produk</h2>
                            <span class="frm-count">Harga {{ $priceTypes[$priceType] }}</span>
                        </div>

                        <div class="frm-table-wrap">
                            <table class="frm-table">
                                <thead>
                                    <tr><th>Produk</th><th class="is-num">Qty</th><th class="is-num">Harga</th><th class="is-num">Subtotal</th><th></th></tr>
                                </thead>
                                <tbody id="trxRows"></tbody>
                            </table>
                        </div>

                        <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center">
                            <button type="button" id="addRow" class="adm-btn adm-btn-ghost adm-btn-sm">+ Tambah Baris</button>
                            <strong>Total: Rp <span id="grandTotal">0</span></strong>
                        </div>
                    </section>

                    <section class="panel" style="margin-bottom:12px;padding:16px">
                        <div class="panel-head" style="padding:0 0 8px"><h2 class="panel-title">3. Pembayaran (opsional)</h2></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                            <input type="number" step="0.01" min="0" name="pay_amount" id="payAmount" value="{{ old('pay_amount') }}" class="frm-input" style="width:180px" placeholder="Jumlah dibayar (Rp)">
                            <select name="pay_method" class="frm-input is-select" style="width:160px">
                                <option value="cash" @selected(old('pay_method', 'cash') === 'cash')>Tunai</option>
                                <option value="transfer" @selected(old('pay_method') === 'transfer')>Transfer</option>
                                <option value="other" @selected(old('pay_method') === 'other')>Lainnya</option>
                            </select>
                            <button type="button" id="payFull" class="adm-btn adm-btn-ghost adm-btn-sm">Bayar Lunas</button>
                        </div>
                        <p class="frm-meta" style="margin:0 0 12px">Kosongkan bila belum dibayar; invoice tetap terbit dan bisa dibayar nanti lewat menu Pembayaran. Uang yang dicatat masuk Buku Kas Depo.</p>

                        <label class="frm-label" for="trxNotes">Catatan</label>
                        <textarea id="trxNotes" name="notes" rows="2" class="frm-input">{{ old('notes') }}</textarea>
                    </section>

                    <div class="frm-head-actions">
                        <button type="submit" class="adm-btn adm-btn-primary">Simpan Transaksi</button>
                        <a href="{{ route('admin.sales.transactions.index') }}" class="adm-btn adm-btn-ghost">Batal</a>
                    </div>
                </form>

                <script>
                    (function () {
                        const products = [
                            @foreach ($stocks as $stock)
                                { id: {{ $stock->product_id }}, name: @js($stock->product->name), stock: {{ $qty($stock->quantity) }}, price: {{ (float) ($prices[$stock->product_id] ?? 0) }} },
                            @endforeach
                        ];
                        const body = document.getElementById('trxRows');
                        const fmt = (v) => new Intl.NumberFormat('id-ID').format(Math.round(v || 0));
                        let index = 0;
                        let total = 0;

                        function recalc() {
                            total = 0;
                            body.querySelectorAll('tr').forEach((tr) => {
                                const p = products.find((x) => String(x.id) === tr.querySelector('select').value);
                                const q = parseFloat(tr.querySelector('input').value) || 0;
                                const price = p ? p.price : 0;
                                tr.querySelector('.price').textContent = p ? (price ? 'Rp ' + fmt(price) : 'Belum diatur') : '-';
                                tr.querySelector('.sub').textContent = 'Rp ' + fmt(price * q);
                                total += price * q;
                            });
                            document.getElementById('grandTotal').textContent = fmt(total);
                        }

                        function addRow() {
                            const i = index++;
                            const tr = document.createElement('tr');
                            const options = products.map((p) => `<option value="${p.id}">${p.name} (stok: ${p.stock})</option>`).join('');
                            tr.innerHTML = `
                                <td><select name="items[${i}][product_id]" class="frm-input is-select" required><option value="">- Produk -</option>${options}</select></td>
                                <td class="is-num"><input type="number" step="0.01" min="0.01" name="items[${i}][quantity]" class="frm-input" style="width:90px" required></td>
                                <td class="is-num price">-</td>
                                <td class="is-num sub">Rp 0</td>
                                <td><button type="button" class="adm-btn adm-btn-ghost adm-btn-sm rm" aria-label="Hapus baris">&times;</button></td>`;
                            tr.addEventListener('input', recalc);
                            tr.addEventListener('change', recalc);
                            tr.querySelector('.rm').addEventListener('click', () => {
                                if (body.children.length > 1) { tr.remove(); recalc(); }
                            });
                            body.appendChild(tr);
                        }

                        document.getElementById('addRow').addEventListener('click', addRow);
                        document.getElementById('payFull').addEventListener('click', () => {
                            document.getElementById('payAmount').value = total.toFixed(2);
                        });
                        addRow();
                    })();
                </script>
            @endif
        @endif
    </div>
</x-admin-layout>
