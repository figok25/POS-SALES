<x-admin-layout>
    <x-slot name="header">BKB Distribusi</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">BKB Distribusi</h1>
                <p class="frm-sub">Warehouse &rarr; Sales. Draft &rarr; Check &rarr; Apply (memindahkan stock).</p>
            </div>
            @can('distribution.manage')
                <a href="{{ route('admin.distribution.bkb.create') }}" class="adm-btn adm-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Buat Draft
                </a>
            @endcan
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

        <div class="panel">
            <form method="GET" class="frm-toolbar">
                <select name="status" class="frm-input is-select is-filter" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="applied" @selected($status === 'applied')>Applied</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
                <span class="frm-count">{{ $items->total() }} dokumen</span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 7 9-4 9 4-9 4-9-4Z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/></svg>
                    </div>
                    <p class="frm-empty-title">Belum ada data</p>
                    <p class="frm-empty-text">Belum ada BKB Distribusi untuk filter ini.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Warehouse</th>
                                <th>Sales</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td data-label="Kode"><span class="frm-code">{{ $item->code }}</span></td>
                                    <td data-label="Warehouse">{{ $item->warehouse->name ?? '—' }}</td>
                                    <td data-label="Sales">{{ $item->sales->name ?? '—' }}</td>
                                    <td data-label="Status" class="frm-cell-status">
                                        @if ($item->status === 'draft')
                                            <span class="frm-status is-warn">Draft</span>
                                        @elseif ($item->status === 'applied')
                                            <span class="frm-status is-on">Applied</span>
                                        @else
                                            <span class="frm-status is-off">Cancelled</span>
                                        @endif
                                    </td>
                                    <td data-label="Dibuat" class="frm-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="is-end">
                                        <a href="{{ route('admin.distribution.bkb.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="frm-pager">{{ $items->links() }}</div>
                @endif
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
