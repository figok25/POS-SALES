<x-admin-layout>
    @php
        $isFiltered   = filled($method);
        $methodLabels = ['cash' => 'Cash', 'transfer' => 'Transfer', 'other' => 'Lainnya'];
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Payment</h1>
            <p class="frm-sub">Pembayaran invoice yang sudah dicatat beserta status settlement-nya.</p>
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
        {{-- Filter --}}
        <form method="GET" class="frm-toolbar">
            <select name="method" class="frm-input is-select is-filter" aria-label="Filter metode" onchange="this.form.submit()">
                <option value="">Semua Metode</option>
                @foreach ($methodLabels as $value => $label)
                    <option value="{{ $value }}" @selected($method === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button></noscript>

            @if ($isFiltered)
                <a href="{{ route('admin.finance.payments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} payment</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Sales</th>
                            <th>Metode</th>
                            <th class="is-num">Jumlah</th>
                            <th>Status Settlement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $payment)
                            <tr>
                                <td><span class="frm-code">{{ $payment->code }}</span></td>
                                <td data-label="Tanggal" class="frm-nowrap">{{ $payment->paid_at->format('d M Y') }}</td>
                                <td data-label="Invoice">
                                    @if ($payment->invoice)
                                        <a href="{{ route('admin.sales.invoices.show', $payment->invoice) }}" class="frm-name">{{ $payment->invoice->code }}</a>
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td data-label="Customer">{{ $payment->invoice->customerLabel() }}</td>
                                <td data-label="Sales">{{ $payment->invoice->sales->name ?? 'Toko Depo' }}</td>
                                <td data-label="Metode">{{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}</td>
                                <td data-label="Jumlah" class="is-num"><span class="frm-num is-strong">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span></td>
                                <td class="frm-cell-status">
                                    @if ($payment->isSettled())
                                        <span class="frm-status is-on">Settled</span>
                                    @else
                                        <span class="frm-status is-off">Belum</span>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada payment' }}</p>
                <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada payment dengan metode ini.' : 'Payment dicatat dari halaman detail Invoice.' }}</p>
                @if ($isFiltered)
                    <a href="{{ route('admin.finance.payments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</a>
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