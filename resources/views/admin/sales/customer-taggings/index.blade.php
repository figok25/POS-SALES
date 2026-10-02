<x-admin-layout>
    @php
        $isFiltered = filled($salesId) || filled($dateFrom) || filled($dateTo);
        $isPending  = $status === 'pending';
        $tabParams  = fn (string $s) => array_merge(request()->except(['status', 'page']), ['status' => $s]);
        $tabs       = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
        $emptyText  = [
            'pending'  => 'Belum ada tagging yang menunggu review.',
            'approved' => 'Belum ada tagging yang disetujui.',
            'rejected' => 'Belum ada tagging yang ditolak.',
        ][$status] ?? 'Data tagging akan tampil di sini.';
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Tagging Toko</h1>
            <p class="frm-sub">Review toko baru yang di-tag oleh Sales di lapangan.</p>
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
    @if (session('error'))
        <div class="frm-alert is-error" role="alert" data-alert>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">{{ session('error') }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif

    <section class="panel">
        {{-- Tab status --}}
        <nav class="frm-tabs" aria-label="Status tagging">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.sales.customer-taggings.index', $tabParams($key)) }}"
                   class="frm-tab {{ $status === $key ? 'is-active' : '' }}"
                   @if ($status === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        {{-- Filter: Sales + Rentang Tanggal Tagging --}}
        <form method="GET" class="frm-toolbar is-fields">
            <input type="hidden" name="status" value="{{ $status }}">

            <div class="frm-field">
                <label for="f-sales" class="frm-label">Sales</label>
                <select id="f-sales" name="sales_id" class="frm-input is-select is-filter">
                    <option value="">Semua Sales</option>
                    @foreach ($salesList as $sales)
                        <option value="{{ $sales->id }}" @selected((string) $salesId === (string) $sales->id)>{{ $sales->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="frm-field">
                <label for="f-from" class="frm-label">Dari Tanggal</label>
                <input type="date" id="f-from" name="date_from" value="{{ $dateFrom }}" class="frm-input is-filter">
            </div>
            <div class="frm-field">
                <label for="f-to" class="frm-label">Sampai Tanggal</label>
                <input type="date" id="f-to" name="date_to" value="{{ $dateTo }}" class="frm-input is-filter">
            </div>

            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales.customer-taggings.index', ['status' => $status]) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} tagging</span>
        </form>

        {{-- Bulk approve (hanya tab Pending). Form dibuat terpisah; checkbox baris terhubung lewat atribut form="". --}}
        @if ($isPending)
            <form id="bulk-approve-form" method="POST" action="{{ route('admin.sales.customer-taggings.bulk-approve') }}" hidden>
                @csrf
            </form>

            @if ($items->count())
                <div class="frm-bulk">
                    <label class="frm-bulk-all">
                        <input type="checkbox" id="select-all-taggings" class="frm-check">
                        <span>Pilih semua di halaman ini</span>
                    </label>
                    <button type="button" id="bulk-approve-btn" class="adm-btn adm-btn-success adm-btn-sm" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        Approve Terpilih (<span id="bulk-approve-count">0</span>)
                    </button>
                </div>
            @endif
        @endif

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            @if ($isPending)<th class="frm-cell-check"><span class="sr-only">Pilih</span></th>@endif
                            <th>Waktu Tagging</th>
                            <th>Sales</th>
                            <th>Nama Toko</th>
                            <th>Telepon</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr @class(['has-check' => $isPending])>
                                @if ($isPending)
                                    <td class="frm-cell-check">
                                        <input type="checkbox" name="tagging_ids[]" value="{{ $item->id }}" form="bulk-approve-form"
                                               class="frm-check tagging-checkbox" aria-label="Pilih {{ $item->name }}">
                                    </td>
                                @endif
                                <td data-label="Waktu" class="frm-nowrap">{{ $item->tagged_at->format('d M Y H:i') }}</td>
                                <td data-label="Sales">{{ $item->sales->name ?? '-' }}</td>
                                <td><span class="frm-name">{{ $item->name }}</span></td>
                                <td data-label="Telepon">
                                    @if ($item->phone) {{ $item->phone }} @else <span class="frm-dash">—</span> @endif
                                </td>
                                <td data-label="Tipe">
                                    @if ($item->customer_type) {{ $item->customer_type }} @else <span class="frm-dash">—</span> @endif
                                </td>
                                <td class="frm-cell-status">
                                    @if ($item->status === 'approved')
                                        <span class="frm-status is-on">Approved</span>
                                    @elseif ($item->status === 'rejected')
                                        <span class="frm-status is-danger">Rejected</span>
                                    @else
                                        <span class="frm-status is-warn">Pending</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <a href="{{ route('admin.sales.customer-taggings.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Detail
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada data' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Coba ubah Sales atau rentang tanggal yang dipakai.' : $emptyText }}</p>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales.customer-taggings.index', ['status' => $status]) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
                @endif
            </div>
        @endif
    </section>

    {{-- Konfirmasi bulk approve --}}
    @if ($isPending)
        <dialog id="bulk-approve-dialog" class="frm-modal is-sm" aria-labelledby="bulk-approve-title">
            <div class="frm-confirm">
                <div class="frm-confirm-icon is-ok">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                </div>
                <h2 id="bulk-approve-title" class="frm-modal-title">Approve tagging terpilih?</h2>
                <p class="frm-confirm-text">
                    <strong id="bulk-dialog-count">0</strong> tagging akan di-approve. Customer langsung dibuat dan masuk Rute Kanvas.
                </p>
            </div>
            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-close>Batal</button>
                <button type="submit" form="bulk-approve-form" class="adm-btn adm-btn-success adm-btn-sm">Ya, Approve</button>
            </div>
        </dialog>
    @endif

    <script>
        (function () {
            // Notifikasi: tombol tutup; yang bertanda data-alert="auto" hilang sendiri.
            document.querySelectorAll('[data-alert]').forEach(function (el) {
                var close = function () {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                };
                var btn = el.querySelector('.frm-alert-close');
                if (btn) btn.addEventListener('click', close);
                if (el.dataset.alert === 'auto') setTimeout(close, 6000);
            });

            // Bulk approve
            var selectAll = document.getElementById('select-all-taggings');
            var approveBtn = document.getElementById('bulk-approve-btn');
            var dialog = document.getElementById('bulk-approve-dialog');
            if (!approveBtn || !dialog) return;

            var boxes = Array.prototype.slice.call(document.querySelectorAll('.tagging-checkbox'));
            var countEl = document.getElementById('bulk-approve-count');
            var dialogCount = document.getElementById('bulk-dialog-count');

            function refresh() {
                var checked = boxes.filter(function (cb) { return cb.checked; }).length;
                countEl.textContent = checked;
                dialogCount.textContent = checked;
                approveBtn.disabled = checked === 0;
                if (selectAll) {
                    selectAll.checked = checked > 0 && checked === boxes.length;
                    selectAll.indeterminate = checked > 0 && checked < boxes.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    boxes.forEach(function (cb) { cb.checked = selectAll.checked; });
                    refresh();
                });
            }
            boxes.forEach(function (cb) { cb.addEventListener('change', refresh); });

            approveBtn.addEventListener('click', function () {
                if (!approveBtn.disabled) dialog.showModal();
            });
            dialog.querySelector('[data-close]').addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });

            refresh();
        })();
    </script>
</x-admin-layout>