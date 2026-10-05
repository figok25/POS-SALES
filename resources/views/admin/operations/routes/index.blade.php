<x-admin-layout>
    @php
        $canManage = \Illuminate\Support\Facades\Gate::allows('operations.manage');
        $isSearching = filled($search);
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Manajemen Rute</h1>
            <p class="frm-sub">Kelola rute kunjungan Sales beserta pelanggan, hari, dan minggu kunjungannya.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('admin.operations.routes.create') }}" class="adm-btn adm-btn-primary adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Tambah Route
            </a>
        @endif
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert="auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" data-alert-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="frm-alert is-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">{{ session('error') }}</span>
        </div>
    @endif

    <section class="panel">
        {{-- Pencarian --}}
        <form method="GET" class="frm-toolbar">
            <div class="frm-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari kode/nama..." aria-label="Cari rute">
            </div>
            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Cari</button>
                @if ($isSearching)
                    <a href="{{ route('admin.operations.routes.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>
            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} rute</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode Rute</th>
                            <th>Nama Rute</th>
                            <th>Jenis Rute</th>
                            <th>Salesman</th>
                            <th>Area</th>
                            <th class="is-num">Jml Pelanggan</th>
                            <th>Status</th>
                            @if ($canManage)
                                <th class="is-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>
                                    @if ($canManage)
                                        <a href="{{ route('admin.operations.routes.edit', $item) }}" class="frm-code">{{ $item->code }}</a>
                                    @else
                                        <span class="frm-code">{{ $item->code }}</span>
                                    @endif
                                </td>
                                <td><span class="frm-name">{{ $item->name }}</span></td>
                                <td data-label="Jenis">{{ $item->route_type ?: '-' }}</td>
                                <td data-label="Salesman">{{ $item->sales?->name ?: '-' }}</td>
                                <td data-label="Area"><span class="frm-clamp">{{ $item->area ?: '-' }}</span></td>
                                <td data-label="Pelanggan" class="is-num"><span class="frm-num">{{ number_format($item->route_customers_count, 0, ',', '.') }}</span></td>
                                <td class="frm-cell-status">
                                    @if ($item->is_active)
                                        <span class="frm-status is-on">Aktif</span>
                                    @else
                                        <span class="frm-status is-off">Nonaktif</span>
                                    @endif
                                </td>
                                @if ($canManage)
                                    <td class="is-end">
                                        <div class="frm-actions">
                                            <a href="{{ route('admin.operations.routes.edit', $item) }}" class="frm-icon-btn" title="Edit" aria-label="Edit {{ $item->code }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            </a>
                                            <form action="{{ route('admin.operations.routes.destroy', $item) }}" method="POST"
                                                  onsubmit="return confirm(@js('Hapus route '.$item->code.' - '.$item->name.'?'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $item->code }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="frm-empty">
                <div class="frm-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15"/><path d="M15 6v15"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isSearching ? 'Tidak ada hasil' : 'Belum ada rute' }}</p>
                <p class="frm-empty-text">{{ $isSearching ? 'Tidak ada rute yang cocok dengan pencarian ini.' : 'Tambahkan rute pertama untuk mulai mengatur kunjungan Sales.' }}</p>
                @if ($isSearching)
                    <a href="{{ route('admin.operations.routes.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                @elseif ($canManage)
                    <a href="{{ route('admin.operations.routes.create') }}" class="adm-btn adm-btn-primary adm-btn-sm">Tambah Route</a>
                @endif
            </div>
        @endif
    </section>

    <script>
        (function () {
            document.querySelectorAll('[data-alert]').forEach(function (el) {
                function dismiss() {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                }
                var close = el.querySelector('[data-alert-close]');
                if (close) close.addEventListener('click', dismiss);
                if (el.getAttribute('data-alert') === 'auto') setTimeout(dismiss, 6000);
            });
        })();
    </script>
</x-admin-layout>