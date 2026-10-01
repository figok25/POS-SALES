<x-admin-layout>
    <x-slot name="header">Branch Transfer</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Branch Transfer</h1>
                <p class="frm-sub">BKB Cabang (kirim) + BTB Cabang (terima) antar warehouse/cabang.</p>
            </div>
            @can('distribution.manage')
                <a href="{{ route('admin.distribution.branch-transfer.create') }}" class="adm-btn adm-btn-primary">
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
                    <option value="sent" @selected($status === 'sent')>Sent (BKB Cabang)</option>
                    <option value="received" @selected($status === 'received')>Received (BTB Cabang)</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
                <span class="frm-count">{{ $items->total() }} dokumen</span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3v10M17 13l-4-4M17 13l4-4"/><path d="M7 21V11M7 11l-4 4M7 11l4 4"/></svg>
                    </div>
                    <p class="frm-empty-title">Belum ada data</p>
                    <p class="frm-empty-text">Belum ada Branch Transfer untuk filter ini.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Warehouse Asal</th>
                                <th>Warehouse Tujuan</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $variant = ['draft' => 'is-warn', 'sent' => 'is-on', 'received' => 'is-on', 'cancelled' => 'is-off'][$item->status] ?? 'is-off';
                                    $label = ['draft' => 'Draft', 'sent' => 'Sent', 'received' => 'Received', 'cancelled' => 'Cancelled'][$item->status] ?? ucfirst($item->status);
                                @endphp
                                <tr>
                                    <td data-label="Kode"><span class="frm-code">{{ $item->code }}</span></td>
                                    <td data-label="Asal">{{ $item->fromWarehouse->name ?? '—' }}</td>
                                    <td data-label="Tujuan">{{ $item->toWarehouse->name ?? '—' }}</td>
                                    <td data-label="Status" class="frm-cell-status"><span class="frm-status {{ $variant }}">{{ $label }}</span></td>
                                    <td data-label="Dibuat" class="frm-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="is-end">
                                        <a href="{{ route('admin.distribution.branch-transfer.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Detail</a>
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
