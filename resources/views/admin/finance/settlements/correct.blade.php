<x-admin-layout>
    @php
        $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    @endphp

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Koreksi Settlement {{ $settlement->code }}</h1>
                <p class="frm-sub">Perbaiki setoran uang atau pembayaran yang terkait. Cash Expected dan selisih dihitung ulang otomatis.</p>
            </div>
            <a href="{{ route('admin.finance.settlements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error is-block" role="alert">
                <div class="frm-alert-text">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.finance.settlements.correct.store', $settlement) }}" class="frm-stack">
            @csrf

            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi Settlement</h2>
                    <span class="frm-status {{ $settlement->isDraft() ? 'is-warn' : 'is-on' }}">{{ $settlement->isDraft() ? 'Draft' : 'Applied' }}</span>
                </div>
                <dl class="frm-detail">
                    <div><dt>Sales</dt><dd>{{ $settlement->sales->name }}</dd></div>
                    <div><dt>Tanggal</dt><dd>{{ $settlement->settled_at->format('d M Y') }}</dd></div>
                    <div><dt>Cash Expected saat ini</dt><dd><span class="frm-num is-strong">{{ $rp($settlement->cash_expected) }}</span></dd></div>
                    <div><dt>Disetor saat ini</dt><dd><span class="frm-num">{{ $rp($settlement->cash_deposited) }}</span></dd></div>
                    <div><dt>Selisih saat ini</dt><dd><span class="frm-num is-strong">{{ $rp($settlement->cash_variance) }}</span></dd></div>
                </dl>
            </section>

            {{-- Pembayaran yang sudah terkait --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Pembayaran cash di Settlement ini</h2>
                    <span class="frm-count">{{ $settlement->payments->count() }} pembayaran</span>
                </div>
                @if ($settlement->payments->isNotEmpty())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead><tr><th>Lepas</th><th>Kode</th><th>Invoice</th><th>Customer</th><th>Tanggal</th><th>Metode</th><th class="is-num">Jumlah</th></tr></thead>
                            <tbody>
                                @foreach ($settlement->payments as $p)
                                    <tr>
                                        <td><input type="checkbox" name="detach[]" value="{{ $p->id }}" data-amount="{{ $p->method === 'cash' ? $p->amount : 0 }}" data-kind="current" @checked(in_array($p->id, old('detach', [])))></td>
                                        <td><span class="frm-code">{{ $p->code }}</span></td>
                                        <td>{{ $p->invoice->code ?? '-' }}</td>
                                        <td>{{ $p->invoice->customer->name ?? ($p->invoice->consumer_name ?? '-') }}</td>
                                        <td class="frm-nowrap">{{ $p->paid_at?->format('d M Y') }}</td>
                                        <td>{{ ucfirst($p->method) }}</td>
                                        <td class="is-num"><span class="frm-num">{{ $rp($p->amount) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="frm-panel-body"><p class="frm-hint" style="margin:0">Centang "Lepas" jika pembayaran salah masuk Settlement ini. Pembayaran yang dilepas akan masuk Settlement berikutnya.</p></div>
                @else
                    <p class="panel-empty">Belum ada pembayaran cash yang terkait.</p>
                @endif
            </section>

            {{-- Kandidat: pembayaran cash belum ter-settlement milik Sales ini --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Pembayaran cash belum ter-settlement ({{ $settlement->sales->name }})</h2>
                    <span class="frm-count">{{ $candidates->count() }} pembayaran</span>
                </div>
                @if ($candidates->isNotEmpty())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead><tr><th>Kaitkan</th><th>Kode</th><th>Invoice</th><th>Customer</th><th>Tanggal</th><th class="is-num">Jumlah</th></tr></thead>
                            <tbody>
                                @foreach ($candidates as $p)
                                    <tr>
                                        <td><input type="checkbox" name="attach[]" value="{{ $p->id }}" data-amount="{{ $p->amount }}" data-kind="candidate" @checked(in_array($p->id, old('attach', [])))></td>
                                        <td><span class="frm-code">{{ $p->code }}</span></td>
                                        <td>{{ $p->invoice->code ?? '-' }}</td>
                                        <td>{{ $p->invoice->customer->name ?? ($p->invoice->consumer_name ?? '-') }}</td>
                                        <td class="frm-nowrap">{{ $p->paid_at?->format('d M Y') }}</td>
                                        <td class="is-num"><span class="frm-num">{{ $rp($p->amount) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="frm-panel-body"><p class="frm-hint" style="margin:0">Pembayaran yang Anda catat manual di Invoice setelah Settlement di-Apply muncul di sini. Centang yang termasuk setoran Settlement ini. Pembayaran hari lain sebaiknya jangan dicentang.</p></div>
                @else
                    <p class="panel-empty">Tidak ada pembayaran cash yang belum ter-settlement.</p>
                @endif
            </section>

            <section class="panel">
                <div class="panel-head"><h2 class="panel-title">Setoran Uang</h2></div>
                <div class="frm-panel-body">
                    <div class="frm-field">
                        <label for="cash_deposited" class="frm-label">Jumlah Uang Disetor (Rp)</label>
                        <input type="number" id="cash_deposited" name="cash_deposited" step="0.01" min="0" inputmode="decimal"
                               value="{{ old('cash_deposited', $settlement->cash_deposited) }}"
                               class="frm-input @error('cash_deposited') is-invalid @enderror">
                        @error('cash_deposited')<p class="frm-error">{{ $message }}</p>@enderror
                        <p class="frm-variance">Cash Expected setelah koreksi: <strong id="expected-text">&mdash;</strong></p>
                        <p class="frm-variance">Selisih (disetor &minus; expected): <strong id="variance-text">&mdash;</strong></p>
                    </div>
                    <div class="frm-field">
                        <label for="notes" class="frm-label">Catatan <span class="frm-opt">(opsional)</span></label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes', $settlement->notes) }}" maxlength="255" placeholder="mis. Sales lupa catat pembayaran, dicatat Admin di invoice"
                               class="frm-input @error('notes') is-invalid @enderror">
                        @error('notes')<p class="frm-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="frm-panel-foot">
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan Koreksi</button>
                </div>
            </section>
        </form>
    </div>

    <script>
        (function () {
            var base = {{ (float) $settlement->payments->where('method', 'cash')->sum('amount') }};
            var dep = document.getElementById('cash_deposited');
            var expOut = document.getElementById('expected-text');
            var varOut = document.getElementById('variance-text');
            var fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
            function rp(n) { return (n < 0 ? '-' : '') + 'Rp ' + fmt.format(Math.abs(n)); }
            function update() {
                var exp = base;
                document.querySelectorAll('input[data-kind]').forEach(function (c) {
                    if (!c.checked) return;
                    var a = parseFloat(c.dataset.amount) || 0;
                    exp += c.dataset.kind === 'candidate' ? a : -a;
                });
                exp = Math.round(exp * 100) / 100;
                expOut.textContent = rp(exp);
                var v = parseFloat(dep.value);
                if (isNaN(v)) { varOut.textContent = '—'; return; }
                var d = Math.round((v - exp) * 100) / 100;
                varOut.textContent = (d > 0 ? '+' : '') + rp(d);
            }
            document.querySelectorAll('input[data-kind]').forEach(function (c) { c.addEventListener('change', update); });
            dep.addEventListener('input', update);
            update();
        })();
    </script>
</x-admin-layout>
