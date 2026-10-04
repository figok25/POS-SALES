<x-admin-layout>
    @php $isFiltered = filled($status); @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Settlement</h1>
            <p class="frm-sub">Setoran uang Sales. Draft dibuat otomatis saat Sales Return Stock atau BTB di-Apply; tinggal Cek lalu Apply. Barang kembali lewat BTB Distribusi.</p>
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
    @if ($errors->any())
        <div class="frm-alert is-error" role="alert" data-alert>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">{{ $errors->first() }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif

    <section class="panel">
        {{-- Filter --}}
        <form method="GET" class="frm-toolbar">
            <select name="status" class="frm-input is-select is-filter" aria-label="Filter status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="applied" @selected($status === 'applied')>Applied</option>
            </select>

            <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button></noscript>

            @if ($isFiltered)
                <a href="{{ route('admin.finance.settlements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} settlement</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Sales</th>
                            <th>Tanggal</th>
                            <th class="is-num">Cash Expected</th>
                            <th class="is-num">Cash Deposited</th>
                            <th class="is-num">Selisih</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $settlement)
                            <tr>
                                <td><span class="frm-code">{{ $settlement->code }}</span></td>
                                <td data-label="Sales"><span class="frm-name">{{ $settlement->sales->name ?? '-' }}</span></td>
                                <td data-label="Tanggal" class="frm-nowrap">{{ $settlement->settled_at->format('d M Y') }}</td>
                                <td data-label="Expected" class="is-num"><span class="frm-num">Rp {{ number_format($settlement->cash_expected, 0, ',', '.') }}</span></td>
                                <td data-label="Deposited" class="is-num"><span class="frm-num">Rp {{ number_format($settlement->cash_deposited, 0, ',', '.') }}</span></td>
                                <td data-label="Selisih" class="is-num">
                                    <span @class(['frm-num', 'is-strong', 'is-neg' => $settlement->cash_variance < 0, 'is-over' => $settlement->cash_variance > 0])>Rp {{ number_format($settlement->cash_variance, 0, ',', '.') }}</span>
                                </td>
                                <td class="frm-cell-status">
                                    @if ($settlement->status === 'applied')
                                        <span class="frm-status is-on">Applied</span>
                                    @else
                                        <span class="frm-status is-warn">Draft</span>
                                        @if ($settlement->pending_btbs_count > 0)
                                            <span class="frm-meta">Menunggu {{ $settlement->pending_btbs_count }} BTB</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="is-end">
                                    @if ($settlement->status === 'draft')
                                        <a href="{{ route('admin.finance.settlements.edit', $settlement) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                            Cek &amp; Apply
                                        </a>
                                    @endif
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada settlement' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada settlement dengan status ini.' : 'Draft akan muncul otomatis saat Sales melakukan Return Stock atau BTB di-Apply.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route('admin.finance.settlements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
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