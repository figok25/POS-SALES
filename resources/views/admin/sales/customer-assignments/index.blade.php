<x-admin-layout>
    @php
        $isFiltered = filled($search) || filled($salesFilter) || $unassignedOnly;
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Customer Assignment</h1>
            <p class="frm-sub">Atur Sales penanggung jawab untuk setiap customer.</p>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif

    <section class="panel">
        {{-- Toolbar filter --}}
        <form method="GET" class="frm-toolbar">
            <div class="frm-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / kode customer..." aria-label="Cari customer" autocomplete="off">
            </div>

            <select name="sales_id" class="frm-input is-select is-filter" aria-label="Filter Sales" onchange="this.form.submit()">
                <option value="">Semua Sales</option>
                @foreach ($saless as $sales)
                    <option value="{{ $sales->id }}" @selected((string) $salesFilter === (string) $sales->id)>{{ $sales->name }}</option>
                @endforeach
            </select>

            <label class="frm-switch">
                <input type="checkbox" name="unassigned" value="1" @checked($unassignedOnly) onchange="this.form.submit()">
                <span class="frm-switch-track"></span>
                <span class="frm-switch-text">Belum di-assign saja</span>
            </label>

            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales.customer-assignments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} customer</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Customer</th>
                            <th>Sales Saat Ini</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><span class="frm-code">{{ $item->code }}</span></td>
                                <td><span class="frm-name">{{ $item->name }}</span></td>
                                <td data-label="Sales">
                                    @if ($item->sales)
                                        {{ $item->sales->name }}
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td class="frm-cell-status">
                                    @if ($item->sales_id)
                                        <span class="frm-status is-on">Assigned</span>
                                    @else
                                        <span class="frm-status is-warn">Belum Assigned</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <a href="{{ route('admin.sales.customer-assignments.edit', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                                        @if ($item->sales_id)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/></svg>
                                            Reassign
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                                            Assign
                                        @endif
                                    </a>
                                </td>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada data' }}</p>
                <p class="frm-empty-text">
                    {{ $isFiltered ? 'Coba ubah kata kunci atau filter yang dipakai.' : 'Data customer akan tampil di sini.' }}
                </p>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales.customer-assignments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
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
            setTimeout(close, 6000);
        });
    </script>
</x-admin-layout>