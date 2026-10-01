<x-admin-layout>
    <x-slot name="header">BKB Distribusi {{ $bkb->code }}</x-slot>

    <div class="frm-page" style="max-width: 760px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">{{ $bkb->code }}</h1>
                <p class="frm-sub">BKB Distribusi — Warehouse &rarr; Sales</p>
            </div>
            <a href="{{ route('admin.distribution.bkb.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
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
                <div><dt>Warehouse Asal</dt><dd>{{ $bkb->warehouse->name ?? '—' }}</dd></div>
                <div><dt>Sales Tujuan</dt><dd>{{ $bkb->sales->name ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd>
                    @if ($bkb->status === 'draft')
                        <span class="frm-status is-warn">Draft</span>
                    @elseif ($bkb->status === 'applied')
                        <span class="frm-status is-on">Applied</span>
                    @else
                        <span class="frm-status is-off">Cancelled</span>
                    @endif
                </dd></div>
                <div><dt>Referensi Permintaan Barang</dt><dd>{{ $bkb->stockRequest->code ?? '—' }}</dd></div>
                <div><dt>Catatan</dt><dd>{{ $bkb->notes ?: '—' }}</dd></div>
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
                            <th class="is-end">Quantity Diminta</th>
                            @if ($bkb->isDraft())<th class="is-end">Stok Warehouse Saat Ini</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bkb->items as $line)
                            @php $stok = $availability[$line->product_id] ?? null; @endphp
                            <tr>
                                <td data-label="Produk">{{ $line->product->name ?? '—' }} <span class="frm-meta">({{ $line->product->sku ?? '—' }})</span></td>
                                <td data-label="Qty Diminta" class="is-num"><span class="frm-num">{{ number_format($line->quantity, 2) }}</span></td>
                                @if ($bkb->isDraft())
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

        @if ($bkb->isDraft())
            <div class="frm-alert is-warn" role="status" style="margin-bottom: 16px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="m10.3 3.9-8 14A1.5 1.5 0 0 0 3.6 20h16.8a1.5 1.5 0 0 0 1.3-2.2l-8-14a1.5 1.5 0 0 0-2.6 0Z"/></svg>
                <span class="frm-alert-text">Tahap Check: pastikan stok warehouse mencukupi sebelum Apply. Apply akan mengurangi Warehouse Stock dan menambah Sales Stock.</span>
            </div>
        @endif

        <div class="frm-head-actions">
            @if ($bkb->isDraft())
                @can('distribution.apply')
                    <form action="{{ route('admin.distribution.bkb.apply', $bkb) }}" method="POST" onsubmit="return confirm('Apply BKB Distribusi ini? Stok akan berubah.')">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-primary">Apply</button>
                    </form>
                @endcan
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.bkb.cancel', $bkb) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-ghost">Batalkan</button>
                    </form>
                @endcan
            @endif

            @if ($bkb->status === 'applied')
                @can('sales-task.manage')
                    @if ($bkb->isReadyForAssignment())
                        <a href="{{ route('admin.sales-tasks.create', ['bkb_distribusi_id' => $bkb->id]) }}" class="adm-btn adm-btn-primary">Buat Sales Task (Tugaskan)</a>
                    @else
                        <span class="frm-meta" style="align-self:center;">Sudah ditugaskan lewat Sales Task {{ $bkb->salesTask->code ?? '' }}</span>
                    @endif
                @endcan
                @can('distribution.manage')
                    <a href="{{ route('admin.distribution.btb.create', ['bkb_distribusi_id' => $bkb->id]) }}" class="adm-btn adm-btn-ghost">Buat BTB (Pengembalian)</a>
                @endcan
            @endif
        </div>
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
