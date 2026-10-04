<x-admin-layout>
    @php
        $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
        $btbStatusLabel = ['draft' => 'Menunggu Apply', 'applied' => 'Applied', 'discrepancy' => 'Discrepancy'];
    @endphp

    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Settlement {{ $settlement->code }}</h1>
                <p class="frm-sub">Cek status barang dan setoran uang, lalu Apply.</p>
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

            {{-- Barang: turunan BTB, read-only. Stok hanya dipindahkan oleh BTB. --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Barang Kembali (dari BTB Distribusi)</h2>
                    <span class="frm-count">{{ $goods['rows']->count() }} produk</span>
                </div>

                @if ($goods['has_pending'])
                    <div class="frm-alert is-error is-block" role="alert">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        <span class="frm-alert-text">
                            Settlement belum bisa di-Apply: masih ada BTB yang belum di-Apply
                            ({{ $goods['pending_btbs']->pluck('code')->implode(', ') }}). Check dan Apply BTB-nya dulu agar barang kembali ke Warehouse.
                        </span>
                    </div>
                @endif

                @if ($goods['rows']->count())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="is-num">Sudah Kembali</th>
                                    <th class="is-num">Menunggu BTB</th>
                                    <th class="is-num">Belum Diretur (Selisih)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($goods['rows'] as $row)
                                    <tr>
                                        <td><span class="frm-name">{{ $row['product']->name ?? '-' }}</span></td>
                                        <td data-label="Sudah Kembali" class="is-num"><span class="frm-num">{{ $qty($row['returned']) }}</span></td>
                                        <td data-label="Menunggu BTB" class="is-num"><span class="frm-num">{{ $qty($row['pending']) }}</span></td>
                                        <td data-label="Selisih" class="is-num">
                                            <span @class(['frm-num', 'is-strong', 'is-neg' => $row['unreturned'] > 0])>{{ $qty($row['unreturned']) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="panel-empty">Tidak ada barang yang perlu dipertanggungjawabkan (stok Sales kosong dan tidak ada BTB).</p>
                @endif

                @if ($goods['btbs']->count())
                    <div class="frm-panel-body">
                        <p class="frm-hint" style="margin-bottom:.5rem">BTB terkait:</p>
                        <ul class="frm-hint" style="margin:0;padding-left:1.1rem">
                            @foreach ($goods['btbs'] as $btb)
                                <li>
                                    <a href="{{ route('admin.distribution.btb.show', $btb) }}">{{ $btb->code }}</a>
                                    &mdash; {{ $btbStatusLabel[$btb->status] ?? $btb->status }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! $goods['has_pending'] && $goods['total_unreturned'] > 0)
                    <div class="frm-panel-body">
                        <p class="frm-hint" style="margin:0">
                            Ada {{ $qty($goods['total_unreturned']) }} unit yang masih tercatat di Sales tanpa BTB. Ini akan tercatat sebagai selisih barang saat Settlement di-Apply.
                        </p>
                    </div>
                @endif
            </section>

            {{-- Setoran uang --}}
            <form method="POST" action="{{ route('admin.finance.settlements.apply', $settlement) }}" class="frm-stack">
                @csrf

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
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm" @disabled($goods['has_pending'])>Apply Settlement</button>
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
                <p class="frm-confirm-text">Draft <strong>{{ $settlement->code }}</strong> akan dibatalkan. Payment dan BTB yang sudah diklaim akan dilepas kembali; draft baru dibuat otomatis saat ada Return Stock atau BTB di-Apply berikutnya.</p>
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
