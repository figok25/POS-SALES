<x-admin-layout>
    @php
        $canManage = \Illuminate\Support\Facades\Gate::allows('sales-task.manage');
        $isFiltered = filled($status);

        // status => [label, nada pill]. Disarankan dipindah ke model (statusLabel()/statusTone()) seperti DeliveryOrder.
        $statusMap = [
            'draft' => ['Draft', 'is-off'],
            'document_available' => ['Document Available', 'is-info'],
            'stock_verification' => ['Stock Verification', 'is-warn'],
            'stock_variance' => ['Stock Variance', 'is-danger'],
            'ready_to_work' => ['Ready to Work', 'is-info'],
            'working' => ['Working', 'is-warn'],
            'completed' => ['Completed', 'is-on'],
            'cancelled' => ['Cancelled', 'is-danger'],
        ];

        $tabs = ['' => 'Semua'] + collect($statusMap)->map(fn ($s) => $s[0])->all();
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Sales Task / Penugasan</h1>
            <p class="frm-sub">Penugasan stock dan rute kunjungan harian untuk Sales.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('admin.sales-tasks.create') }}" class="adm-btn adm-btn-primary adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Buat Task
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
        {{-- Tab status --}}
        <nav class="frm-tabs" aria-label="Filter status">
            @foreach ($tabs as $value => $label)
                @php $active = ($status ?? '') === (string) $value; @endphp
                <a href="{{ route('admin.sales-tasks.index', $value === '' ? [] : ['status' => $value]) }}"
                   class="frm-tab {{ $active ? 'is-active' : '' }}"
                   @if ($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} task</span>
        </nav>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Sales</th>
                            <th>Branch</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                [$statusLabel, $statusTone] = $statusMap[$item->status] ?? [ucwords(str_replace('_', ' ', $item->status)), 'is-off'];
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.sales-tasks.show', $item) }}" class="frm-code">{{ $item->code }}</a></td>
                                <td data-label="Sales"><span class="frm-name">{{ $item->sales->name ?? '-' }}</span></td>
                                <td data-label="Branch">{{ $item->branch->name ?? '-' }}</td>
                                <td data-label="Tanggal" class="frm-nowrap">{{ $item->task_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="frm-cell-status"><span class="frm-status {{ $statusTone }}">{{ $statusLabel }}</span></td>
                                <td class="is-end">
                                    <div class="frm-actions">
                                        <a href="{{ route('admin.sales-tasks.show', $item) }}" class="frm-icon-btn" title="Detail" aria-label="Detail {{ $item->code }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada Sales Task' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada task dengan status ini.' : 'Buat task pertama dari BKB Distribusi yang sudah Apply.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route('admin.sales-tasks.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Lihat semua task</a>
                @elseif ($canManage)
                    <a href="{{ route('admin.sales-tasks.create') }}" class="adm-btn adm-btn-primary adm-btn-sm">Buat Task</a>
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