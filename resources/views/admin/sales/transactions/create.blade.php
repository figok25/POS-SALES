<x-admin-layout>
    @php
        $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $ready = $branchId && $warehouseId;
    @endphp

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Penjualan Langsung Depo</h1>
                <p class="frm-sub">Kasir Depo: jual langsung ke konsumen dari Gudang Depo dengan harga Konsumen. Bukan transaksi Customer/Outlet, tidak terkait Sales, tanpa Delivery Order.</p>
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
            <div class="panel-head"><h2 class="panel-title">1. Depo &amp; Gudang</h2></div>
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

                    <section class="panel" style="margin-bottom:12px">
                        <div class="panel-head">
                            <h2 class="panel-title">2. Produk</h2>
                            <span class="frm-count">Harga Konsumen</span>
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
                        <div class="panel-head" style="padding:0 0 8px"><h2 class="panel-title">3. Pembeli &amp; Pembayaran</h2></div>

                        <label class="frm-label" for="consumerName">Nama Pembeli (opsional)</label>
                        <input type="text" id="consumerName" name="consumer_name" maxlength="120" value="{{ old('consumer_name') }}" class="frm-input" style="max-width:320px;margin-bottom:12px" placeholder="Kosongkan untuk konsumen umum">

                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
                            <input type="number" step="0.01" min="0" id="received" class="frm-input" style="width:180px" placeholder="Uang diterima (Rp)">
                            <select name="pay_method" class="frm-input is-select" style="width:160px">
                                <option value="cash" @selected(old('pay_method', 'cash') === 'cash')>Tunai</option>
                                <option value="transfer" @selected(old('pay_method') === 'transfer')>Transfer</option>
                                <option value="other" @selected(old('pay_method') === 'other')>Lainnya</option>
                            </select>
                            <button type="button" id="payFull" class="adm-btn adm-btn-ghost adm-btn-sm">Uang Pas</button>
                        </div>
                        <input type="hidden" name="pay_amount" id="payAmount" value="">
                        <p style="margin:0 0 4px"><strong id="changeLabel">Kembalian: Rp 0</strong></p>
                        <p class="frm-meta" style="margin:0 0 12px">Kosongkan uang diterima bila belum dibayar; invoice tetap terbit dan bisa dibayar nanti lewat menu Pembayaran. Yang dicatat sebesar total (kembalian tidak dicatat) dan masuk Buku Kas Depo.</p>

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
                            updatePay();
                        }

                        function updatePay() {
                            const raw = document.getElementById('received').value;
                            const received = parseFloat(raw) || 0;
                            const paid = Math.min(received, total);
                            document.getElementById('payAmount').value = raw === '' ? '' : paid.toFixed(2);
                            document.getElementById('changeLabel').textContent = 'Kembalian: Rp ' + fmt(Math.max(0, received - total));
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
                        document.getElementById('received').addEventListener('input', updatePay);
                        document.getElementById('payFull').addEventListener('click', () => {
                            document.getElementById('received').value = total.toFixed(2);
                            updatePay();
                        });
                        addRow();
                    })();
                </script>
            @endif
        @endif
    </div>
</x-admin-layout>
