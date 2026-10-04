<x-admin-layout>
    @php
        $isSuper    = auth()->user()->isSuperAdmin();
        $isFiltered = filled($type) || ($isSuper && ! $branchContext->isAll());
        $balance    = $summaryIncome - $summaryExpense;
        // Super Admin: Tambah Catatan membawa Depo yang sedang difilter.
        $createUrl  = route('admin.finance.cash-ledgers.create', ($isSuper && ! $branchContext->isAll()) ? ['branch' => $branchContext->branchId()] : []);
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Income &amp; Expense</h1>
            <p class="frm-sub">Catatan pemasukan dan pengeluaran kas{{ $isSuper ? '' : ' Depo '.(auth()->user()->branch->name ?? '-') }}.</p>
        </div>
        <a href="{{ $createUrl }}" class="adm-btn adm-btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            Tambah Catatan
        </a>
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

    {{-- Ringkasan --}}
    <div class="dash-section">
        <div class="kpi-grid">
            <div class="kpi tone-green">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/></svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">Total Income</p>
                    <p class="kpi-value is-success">Rp {{ number_format($summaryIncome, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="kpi tone-red">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 17-8.5-8.5-5 5L2 7"/><path d="M16 17h6v-6"/></svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">Total Expense</p>
                    <p class="kpi-value is-danger">Rp {{ number_format($summaryExpense, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="kpi tone-blue">
                <div class="kpi-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">Selisih</p>
                    <p @class(['kpi-value', 'is-danger' => $balance < 0])>{{ $balance < 0 ? '-' : '' }}Rp {{ number_format(abs($balance), 0, ',', '.') }}</p>
                    <p class="kpi-hint">Income &minus; Expense</p>
                </div>
            </div>
        </div>
    </div>

    <section class="panel">
        {{-- Filter --}}
        <form method="GET" class="frm-toolbar">
            @if ($isSuper)
                {{-- Hanya Super Admin yang boleh memilih Depo (BranchContext). --}}
                <select name="branch" class="frm-input is-select is-filter" aria-label="Filter Depo" onchange="this.form.submit()">
                    <option value="all">Semua Depo</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(! $branchContext->isAll() && $branchContext->branchId() === $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif

            <select name="type" class="frm-input is-select is-filter" aria-label="Filter tipe" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="income" @selected($type === 'income')>Income</option>
                <option value="expense" @selected($type === 'expense')>Expense</option>
            </select>

            <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button></noscript>

            @if ($isFiltered)
                <a href="{{ route('admin.finance.cash-ledgers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} catatan</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            @if ($isSuper)<th>Depo</th>@endif
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Kategori</th>
                            <th class="is-num">Jumlah</th>
                            <th>Keterangan</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $entry)
                            <tr>
                                <td><span class="frm-code">{{ $entry->code }}</span></td>
                                @if ($isSuper)
                                    <td data-label="Depo">{{ $entry->branch->name ?? '—' }}</td>
                                @endif
                                <td data-label="Tanggal" class="frm-nowrap">{{ $entry->date->format('d M Y') }}</td>
                                <td class="frm-cell-status">
                                    @if ($entry->type === 'income')
                                        <span class="frm-status is-on">Income</span>
                                    @else
                                        <span class="frm-status is-danger">Expense</span>
                                    @endif
                                </td>
                                <td data-label="Kategori"><span class="frm-name">{{ $entry->category }}</span></td>
                                <td data-label="Jumlah" class="is-num">
                                    <span @class(['frm-num', 'is-strong', 'is-pos' => $entry->type === 'income', 'is-neg' => $entry->type !== 'income'])>Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                                </td>
                                <td data-label="Keterangan">
                                    @if ($entry->description)
                                        <span class="frm-clamp" title="{{ $entry->description }}">{{ $entry->description }}</span>
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <div class="frm-actions">
                                        <button type="button" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $entry->code }}"
                                                data-delete data-code="{{ $entry->code }}"
                                                data-action="{{ route('admin.finance.cash-ledgers.destroy', $entry) }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                        </button>
                                    </div>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada catatan' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada catatan untuk filter ini.' : 'Mulai catat pemasukan dan pengeluaran kas.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route('admin.finance.cash-ledgers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
                @else
                    <a href="{{ $createUrl }}" class="adm-btn adm-btn-primary adm-btn-sm">Tambah Catatan</a>
                @endif
            </div>
        @endif
    </section>

    {{-- Konfirmasi hapus --}}
    @if ($items->count())
        <dialog id="delete-dialog" class="frm-modal is-sm" aria-labelledby="delete-title">
            <form method="POST" id="delete-form" class="frm-modal-form">
                @csrf
                @method('DELETE')
                <div class="frm-confirm">
                    <div class="frm-confirm-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </div>
                    <h2 id="delete-title" class="frm-modal-title">Hapus catatan ini?</h2>
                    <p class="frm-confirm-text">Catatan <strong id="delete-code"></strong> akan dihapus.</p>
                </div>
                <div class="frm-modal-foot">
                    <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-close>Batal</button>
                    <button type="submit" class="adm-btn adm-btn-danger adm-btn-sm">Ya, Hapus</button>
                </div>
            </form>
        </dialog>
    @endif

    <script>
        (function () {
            document.querySelectorAll('[data-alert]').forEach(function (el) {
                var close = function () {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                };
                var btn = el.querySelector('.frm-alert-close');
                if (btn) btn.addEventListener('click', close);
                if (el.dataset.alert === 'auto') setTimeout(close, 6000);
            });

            var dialog = document.getElementById('delete-dialog');
            if (!dialog) return;
            var form = document.getElementById('delete-form');
            var code = document.getElementById('delete-code');

            document.querySelectorAll('[data-delete]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = btn.dataset.action;
                    code.textContent = btn.dataset.code;
                    dialog.showModal();
                });
            });
            dialog.querySelector('[data-close]').addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
        })();
    </script>
</x-admin-layout>