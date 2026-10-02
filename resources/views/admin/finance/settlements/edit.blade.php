<x-admin-layout>
    @php $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'); @endphp

    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Settlement {{ $settlement->code }}</h1>
                <p class="frm-sub">Koreksi qty retur dan jumlah setoran sebelum di-Apply.</p>
            </div>
            <a href="{{ route('admin.finance.settlements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error is-block" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <div class="frm-alert-text">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="frm-stack">
            {{-- Ringkasan --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi Settlement</h2>
                    <span class="frm-status is-warn">Draft</span>
                </div>
                <dl class="frm-detail">
                    <div><dt>Sales</dt><dd>{{ $settlement->sales->name }}</dd></div>
                    <div><dt>Warehouse Tujuan</dt><dd>{{ $settlement->warehouse->name }}</dd></div>
                    <div><dt>Tanggal</dt><dd>{{ $settlement->settled_at->format('d M Y') }}</dd></div>
                    <div>
                        <dt>Cash Expected (dari {{ $settlement->payments->count() }} payment cash)</dt>
                        <dd><span class="frm-num is-strong">Rp {{ number_format($settlement->cash_expected, 0, ',', '.') }}</span></dd>
                    </div>
                </dl>
            </section>

            <form method="POST" action="{{ route('admin.finance.settlements.apply', $settlement) }}" class="frm-stack">
                @csrf

                {{-- Retur barang --}}
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Retur Barang (Sales Stock &rarr; Warehouse)</h2>
                        <span class="frm-count">{{ $settlement->items->count() }} produk</span>
                    </div>

                    @if ($settlement->items->count())
                        <div class="frm-table-wrap">
                            <table class="frm-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="is-num">Qty di Sistem</th>
                                        <th class="is-num">Qty Diretur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($settlement->items as $item)
                                        <tr>
                                            <td><span class="frm-name">{{ $item->product->name ?? '-' }}</span></td>
                                            <td data-label="Qty di Sistem" class="is-num"><span class="frm-num">{{ $qty($item->system_qty) }}</span></td>
                                            <td data-label="Qty Diretur" class="is-num">
                                                <input type="number" name="returned_qty[{{ $item->product_id }}]" step="0.01" min="0"
                                                       max="{{ $item->system_qty }}" inputmode="decimal"
                                                       value="{{ old("returned_qty.{$item->product_id}", $item->system_qty) }}"
                                                       aria-label="Qty diretur {{ $item->product->name ?? '' }}"
                                                       class="frm-input is-qty">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="panel-empty">Tidak ada Sales Stock tersisa untuk Sales ini.</p>
                    @endif
                </section>

                {{-- Setoran uang --}}
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Setoran Uang</h2>
                    </div>

                    <div class="frm-panel-body">
                        <div class="frm-field">
                            <label for="cash_deposited" class="frm-label">Jumlah Uang Disetor (Rp)</label>
                            <input type="number" id="cash_deposited" name="cash_deposited" step="0.01" min="0" inputmode="decimal"
                                   data-expected="{{ $settlement->cash_expected }}"
                                   value="{{ old('cash_deposited', $settlement->cash_expected) }}"
                                   class="frm-input @error('cash_deposited') is-invalid @enderror">
                            @error('cash_deposited')<p class="frm-error">{{ $message }}</p>@enderror
                            <p class="frm-hint">Kalau berbeda dari Cash Expected, selisihnya akan tercatat otomatis.</p>
                            <p class="frm-variance">Selisih (disetor &minus; expected): <strong id="variance-text">&mdash;</strong></p>
                        </div>

                        <div class="frm-field">
                            <label for="notes" class="frm-label">Catatan <span class="frm-opt">(opsional)</span></label>
                            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" placeholder="mis. alasan selisih"
                                   class="frm-input @error('notes') is-invalid @enderror">
                            @error('notes')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="frm-panel-foot is-split">
                        <button type="button" class="adm-btn adm-btn-ghost is-danger adm-btn-sm" id="cancel-draft-btn">Batalkan Draft</button>
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Apply Settlement</button>
                    </div>
                </section>
            </form>
        </div>
    </div>

    {{-- Konfirmasi batalkan draft (form terpisah, di luar form Apply) --}}
    <dialog id="cancel-draft-dialog" class="frm-modal is-sm" aria-labelledby="cancel-draft-title">
        <form method="POST" action="{{ route('admin.finance.settlements.destroy', $settlement) }}" class="frm-modal-form">
            @csrf
            @method('DELETE')
            <div class="frm-confirm">
                <div class="frm-confirm-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                </div>
                <h2 id="cancel-draft-title" class="frm-modal-title">Batalkan Draft ini?</h2>
                <p class="frm-confirm-text">Draft <strong>{{ $settlement->code }}</strong> akan dibatalkan. Payment yang sudah direservasi akan dilepas kembali.</p>
            </div>
            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-close>Kembali</button>
                <button type="submit" class="adm-btn adm-btn-danger adm-btn-sm">Ya, Batalkan Draft</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            // Pratinjau selisih setoran (disetor - expected)
            var input = document.getElementById('cash_deposited');
            var out = document.getElementById('variance-text');
            var expected = parseFloat(input.dataset.expected) || 0;
            var fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });

            function update() {
                var v = parseFloat(input.value);
                if (isNaN(v)) { out.textContent = '\u2014'; out.className = ''; return; }
                var d = Math.round((v - expected) * 100) / 100;
                out.textContent = (d < 0 ? '-' : d > 0 ? '+' : '') + 'Rp ' + fmt.format(Math.abs(d));
                out.className = d < 0 ? 'is-neg' : (d > 0 ? 'is-over' : '');
            }
            input.addEventListener('input', update);
            update();

            // Dialog batalkan draft
            var dialog = document.getElementById('cancel-draft-dialog');
            document.getElementById('cancel-draft-btn').addEventListener('click', function () { dialog.showModal(); });
            dialog.querySelector('[data-close]').addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
        })();
    </script>
</x-admin-layout>