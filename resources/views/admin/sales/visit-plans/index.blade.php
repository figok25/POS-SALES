<x-admin-layout>
    @php $isFiltered = filled($search); @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Visit Plan Mingguan per Sales</h1>
            <p class="frm-sub">Susun jadwal kunjungan berulang per hari untuk tiap Sales. Dipakai otomatis saat membuat Sales Task baru, tidak perlu input urutan kunjungan manual lagi.</p>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert="auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif

    <section class="panel">
        {{-- Pencarian --}}
        <form method="GET" class="frm-toolbar">
            <div class="frm-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama Sales..." aria-label="Cari Sales" autocomplete="off">
            </div>

            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Cari</button>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales.visit-plans.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>

            <span class="frm-count">{{ number_format($saless->total(), 0, ',', '.') }} Sales</span>
        </form>

        @if ($saless->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th>Jumlah Outlet Ter-tag</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($saless as $sales)
                            <tr>
                                <td><span class="frm-name">{{ $sales->name }}</span></td>
                                <td data-label="Outlet">
                                    @if ($sales->customers_count > 0)
                                        <span class="frm-num is-strong">{{ number_format($sales->customers_count, 0, ',', '.') }}</span> outlet
                                    @else
                                        <span class="frm-status is-warn">Belum ada outlet</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <a href="{{ route('admin.sales.visit-plans.edit', $sales) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                                        Atur Visit Plan
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($saless->hasPages())
                <div class="frm-pager">{{ $saless->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="frm-empty">
                <div class="frm-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada data' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Coba kata kunci lain.' : 'Data Sales akan tampil di sini.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales.visit-plans.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Pencarian</a>
                @endif
            </div>
        @endif
    </section>

    <script>
        document.querySelectorAll('[data-alert]').forEach(function (el) {
            var close = function () {
                el.classList.add('is-leaving');
                setTimeout(function () { el.remove(); }, 300);
            };
            var btn = el.querySelector('.frm-alert-close');
            if (btn) btn.addEventListener('click', close);
            if (el.dataset.alert === 'auto') setTimeout(close, 6000);
        });
    </script>
</x-admin-layout>