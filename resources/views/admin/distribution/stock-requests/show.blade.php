<x-admin-layout>
    <x-slot name="header">Permintaan Barang {{ $stockRequest->code }}</x-slot>

    <div class="frm-page" style="max-width: 760px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">{{ $stockRequest->code }}</h1>
                <p class="frm-sub">Permintaan Barang</p>
            </div>
            <a href="{{ route('admin.distribution.stock-requests.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
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
                <div><dt>Warehouse</dt><dd>{{ $stockRequest->warehouse->name ?? '—' }}</dd></div>
                <div><dt>Sales</dt><dd>{{ $stockRequest->sales->name ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd>
                    @if ($stockRequest->status === 'draft')
                        <span class="frm-status is-warn">Draft</span>
                    @elseif ($stockRequest->status === 'submitted')
                        <span class="frm-status is-on">Submitted</span>
                    @else
                        <span class="frm-status is-off">Cancelled</span>
                    @endif
                </dd></div>
                <div><dt>Dibuat oleh</dt><dd>{{ $stockRequest->creator->name ?? '—' }}</dd></div>
                <div><dt>Catatan</dt><dd>{{ $stockRequest->notes ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="panel" style="margin-bottom: 16px;">
            <div class="panel-head">
                <h2 class="panel-title">Item Produk</h2>
            </div>
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead><tr><th>Produk</th><th class="is-end">Quantity</th></tr></thead>
                    <tbody>
                        @foreach ($stockRequest->items as $line)
                            <tr>
                                <td data-label="Produk">{{ $line->product->name ?? '—' }} <span class="frm-meta">({{ $line->product->sku ?? '—' }})</span></td>
                                <td data-label="Quantity" class="is-num"><span class="frm-num">{{ number_format($line->quantity, 2) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="frm-head-actions">
            @if ($stockRequest->isDraft())
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.stock-requests.submit', $stockRequest) }}" method="POST" onsubmit="return confirm('Submit Permintaan Barang ini?')">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-primary">Submit</button>
                    </form>
                    <form action="{{ route('admin.distribution.stock-requests.destroy', $stockRequest) }}" method="POST" onsubmit="return confirm('Hapus draft ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="adm-btn adm-btn-danger">Hapus</button>
                    </form>
                @endcan
            @endif

            @if ($stockRequest->isSubmitted())
                @can('distribution.manage')
                    <a href="{{ route('admin.distribution.bkb.create', ['stock_request_id' => $stockRequest->id]) }}" class="adm-btn adm-btn-primary">Buat BKB Distribusi</a>
                @endcan
            @endif

            @if (in_array($stockRequest->status, ['draft', 'submitted']))
                @can('distribution.manage')
                    <form action="{{ route('admin.distribution.stock-requests.cancel', $stockRequest) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini?')">
                        @csrf
                        <button type="submit" class="adm-btn adm-btn-ghost">Batalkan</button>
                    </form>
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
