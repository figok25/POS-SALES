<x-admin-layout>
    <x-slot name="header">BTB Distribusi</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">BTB Distribusi</h1>
                <p class="frm-sub">Sales &rarr; Warehouse (pengembalian). Menunggu Check &rarr; Approved/Discrepancy.</p>
            </div>
            @can('distribution.manage')
                <a href="{{ route('admin.distribution.btb.create') }}" class="adm-btn adm-btn-primary">
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
            <form method="GET" class="frm-filter" style="display:block;padding:16px 18px;border-bottom:1px solid var(--adm-border,#e5e9f2)">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px 14px;align-items:end">
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Status
                        <select name="status" class="frm-input is-select is-filter" style="width:100%">
                            <option value="">Semua Status</option>
                            <option value="draft" @selected($status === 'draft')>Menunggu Check</option>
                            <option value="applied" @selected($status === 'applied')>Approved</option>
                            <option value="discrepancy" @selected($status === 'discrepancy')>Discrepancy</option>
                            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                        </select>
                    </label>
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Dari Tanggal
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="frm-input" style="width:100%">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Sampai Tanggal
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="frm-input" style="width:100%">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Cari
                        <input type="search" name="q" value="{{ request('q') }}" class="frm-input" placeholder="Kode / nama Sales / Warehouse" style="width:100%">
                    </label>
                </div>
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-top:12px">
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan Filter</button>
                    @if (request()->hasAny(['q', 'date_from', 'date_to', 'status']))
                        <a href="{{ route('admin.distribution.btb.index', request()->only('branch')) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                    <span class="frm-count" style="margin-left:auto">{{ $items->total() }} dokumen</span>
                </div>
                <p style="margin:8px 0 0;font-size:.72rem;color:var(--adm-muted,#64748b)">Tanggal dihitung dari tanggal dokumen. Semua filter berlaku bersamaan dan ikut terbawa saat pindah halaman.</p>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </div>
                    <p class="frm-empty-title">Belum ada data</p>
                    <p class="frm-empty-text">Belum ada BTB Distribusi untuk filter ini.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Sales</th>
                                <th>Warehouse</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td data-label="Kode"><span class="frm-code">{{ $item->code }}</span></td>
                                    <td data-label="Sales">{{ $item->sales->name ?? '—' }}</td>
                                    <td data-label="Warehouse">{{ $item->warehouse->name ?? '—' }}</td>
                                    <td data-label="Status" class="frm-cell-status">
                                        @if ($item->status === 'draft')
                                            <span class="frm-status is-warn">Menunggu Check</span>
                                        @elseif ($item->status === 'applied')
                                            <span class="frm-status is-on">Approved</span>
                                        @elseif ($item->status === 'discrepancy')
                                            <span class="frm-status is-danger">Discrepancy</span>
                                        @else
                                            <span class="frm-status is-off">Cancelled</span>
                                        @endif
                                    </td>
                                    <td data-label="Dibuat" class="frm-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="is-end">
                                        <a href="{{ route('admin.distribution.btb.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Detail</a>
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
