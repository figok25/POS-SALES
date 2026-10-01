<x-admin-layout>
    <x-slot name="header">BTB Distribusi {{ $btb->code }}</x-slot>

    <div class="frm-page" style="max-width: 760px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">{{ $btb->code }}</h1>
                <p class="frm-sub">BTB Distribusi — Sales &rarr; Warehouse (pengembalian)</p>
            </div>
            <a href="{{ route('admin.distribution.btb.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status" data-alert>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                <span class="frm-alert-text">{{ session('status') }}</span>
                <button type="button" class="frm-alert-close" aria-label="Tutup pesan" data-alert-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div class="frm-alert is-error" role="alert" data-alert>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                <span class="frm-alert-text">{{ session('error') }}</span>
                <button type="button" class="frm-alert-close" aria-label="Tutup pesan" data-alert-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="panel" style="margin-bottom: 16px;">
            <dl class="frm-detail">
                <div><dt>Sales Asal</dt><dd>{{ $btb->sales->name ?? '—' }}</dd></div>
                <div><dt>Warehouse Tujuan</dt><dd>{{ $btb->warehouse->name ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd>
                    @if ($btb->status === 'draft')
                        <span class="frm-status is-warn">Menunggu Check</span>
                    @elseif ($btb->status === 'applied')
                        <span class="frm-status is-on">Approved</span>
                    @elseif ($btb->status === 'discrepancy')
                        <span class="frm-status is-danger">Discrepancy</span>
                    @else
                        <span class="frm-status is-off">Cancelled</span>
                    @endif
                </dd></div>
                <div><dt>Asal Dokumen</dt><dd>{{ $btb->source === 'return_stock' ? 'Return Stock (Sales Mobile)' : 'Dibuat Manual oleh Admin' }}</dd></div>
                <div><dt>Referensi BKB</dt><dd>{{ $btb->bkbDistribusi->code ?? '—' }}</dd></div>
                <div><dt>Diperiksa (Check)</dt><dd>{{ $btb->checked_at ? $btb->checked_at->format('d/m/Y H:i') : 'Belum diperiksa' }}</dd></div>
                <div><dt>Catatan</dt><dd style="white-space: pre-line;">{{ $btb->notes ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="panel" style="margin-bottom: 16px;">
            <div class="panel-head">
                <h2 class="panel-title">Item Produk</h2>
            </div>
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="is-end">Qty Dikembalikan</th>
                            @if ($btb->isDraft())<th class="is-end">Sales Stock Saat Ini</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($btb->items as $line)
                            @php $stok = $availability[$line->product_id] ?? null; @endphp
                            <tr>
                                <td data-label="Produk">{{ $line->product->name ?? '—' }} <span class="frm-meta">({{ $line->product->sku ?? '—' }})</span></td>
                                <td data-label="Qty Kembali" class="is-num"><span class="frm-num">{{ number_format($line->quantity, 2) }}</span></td>
                                @if ($btb->isDraft())
                                    <td data-label="Stok Saat Ini" class="is-num">
                                        <span class="frm-num {{ $stok !== null && $stok < $line->quantity ? 'is-strong is-neg' : '' }}">{{ number_format($stok ?? 0, 2) }}</span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($btb->isDraft())
            <div class="frm-alert is-warn" role="status" style="margin-bottom: 16px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="m10.3 3.9-8 14A1.5 1.5 0 0 0 3.6 20h16.8a1.5 1.5 0 0 0 1.3-2.2l-8-14a1.5 1.5 0 0 0-2.6 0Z"/></svg>
                <span class="frm-alert-text">Tahap Check: pastikan Sales Stock mencukupi sebelum Apply. Apply akan mengurangi Sales Stock dan menambah Warehouse Stock.</span>
            </div>
        @endif

        <div class="frm-head-actions" style="margin-bottom: 16px;">
            @if ($btb->isDraft())
                @can('distribution.apply')
                    <form action="{{ route('admin.distribution.btb.apply', $btb) }}" method="POST" onsubmit="return confirm('Apply (Approve) BTB Distribusi ini? Stok akan berubah.')">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-primary">Apply / Approve</button>
                    </form>
                    <button type="button" class="adm-btn adm-btn-danger" onclick="document.getElementById('discrepancy-panel').hidden = !document.getElementById('discrepancy-panel').hidden">Tandai Discrepancy</button>
                @endcan
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.btb.cancel', $btb) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-ghost">Batalkan</button>
                    </form>
                @endcan
            @endif
        </div>

        @if ($btb->isDraft())
            @can('distribution.apply')
                <div class="panel" id="discrepancy-panel" hidden>
                    <div class="panel-head"><h2 class="panel-title">Tandai Discrepancy</h2></div>
                    <form action="{{ route('admin.distribution.btb.discrepancy', $btb) }}" method="POST" onsubmit="return confirm('Tandai dokumen ini Discrepancy? Stock TIDAK akan berpindah.')">
                        @csrf
                        <div class="frm-modal-body" style="padding: 18px 22px;">
                            <div class="frm-field">
                                <label class="frm-label" for="discNotes">Catatan Selisih <span class="frm-req">*</span></label>
                                <textarea id="discNotes" name="discrepancy_notes" rows="2" class="frm-input is-area" required placeholder="Jelaskan selisih yang ditemukan saat Check fisik..."></textarea>
                            </div>
                        </div>
                        <div class="frm-modal-foot">
                            <button type="submit" class="adm-btn adm-btn-danger">Simpan Discrepancy</button>
                        </div>
                    </form>
                </div>
            @endcan
        @endif
    </div>

    <script>
        document.querySelectorAll('[data-alert]').forEach(function (alertEl) {
            var closeBtn = alertEl.querySelector('[data-alert-close]');
            if (closeBtn) closeBtn.addEventListener('click', function () { alertEl.remove(); });
            setTimeout(function () {
                alertEl.classList.add('is-leaving');
                setTimeout(function () { alertEl.remove(); }, 300);
            }, 6000);
        });
    </script>
</x-admin-layout>
