<x-admin-layout>
    @php
        $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $rp  = fn ($v) => number_format($v, 0, ',', '.');
        $outstanding = $invoice->outstanding();
    @endphp

    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Invoice {{ $invoice->code }}</h1>
                <p class="frm-sub">{{ $invoice->customerLabel() }} &middot; {{ $invoice->date->format('d M Y') }}</p>
            </div>
            <div class="frm-head-actions">
                <a href="{{ route('admin.sales.invoices.print', $invoice) }}" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Cetak
                </a>
                @if (! $invoice->isFullyPaid())
                    <a href="{{ route('admin.finance.payments.create', $invoice) }}" class="adm-btn adm-btn-primary adm-btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        Catat Payment
                    </a>
                @endif
                <a href="{{ route('admin.sales.invoices.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Kembali
                </a>
            </div>
        </div>

        <div class="frm-stack">
            {{-- Informasi invoice --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi Invoice</h2>
                    @if ($invoice->status === 'paid')
                        <span class="frm-status is-on">Paid</span>
                    @elseif ($invoice->status === 'partial')
                        <span class="frm-status is-warn">Partial</span>
                    @else
                        <span class="frm-status is-danger">Unpaid</span>
                    @endif
                </div>
                <dl class="frm-detail">
                    <div><dt>Tanggal</dt><dd>{{ $invoice->date->format('d M Y') }}</dd></div>
                    <div>
                        <dt>Sales Transaction</dt>
                        <dd><a href="{{ route('admin.sales.transactions.show', $invoice->salesTransaction) }}" class="panel-link">{{ $invoice->salesTransaction->code }}</a></dd>
                    </div>
                    <div><dt>Customer</dt><dd>{{ $invoice->customerLabel() }}</dd></div>
                    <div><dt>Sales</dt><dd>{{ $invoice->sales->name ?? '-' }}</dd></div>
                    <div>
                        <dt>Outstanding</dt>
                        <dd><span @class(['frm-num', 'is-strong', 'is-neg' => $outstanding > 0])>Rp {{ $rp($outstanding) }}</span></dd>
                    </div>
                </dl>
            </section>

            {{-- Item --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Rincian Item</h2>
                    <span class="frm-count">{{ $invoice->items->count() }} item</span>
                </div>
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="is-num">Qty</th>
                                <th class="is-num">Harga</th>
                                <th class="is-num">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $line)
                                <tr>
                                    <td><span class="frm-name">{{ $line->product->name ?? '-' }}</span></td>
                                    <td data-label="Qty" class="is-num"><span class="frm-num">{{ $qty($line->quantity) }}</span></td>
                                    <td data-label="Harga" class="is-num"><span class="frm-num">Rp {{ $rp($line->price) }}</span></td>
                                    <td data-label="Subtotal" class="is-num"><span class="frm-num is-strong">Rp {{ $rp($line->subtotal) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Total --}}
            <section class="panel frm-totals">
                <dl class="frm-detail">
                    <div><dt>Subtotal</dt><dd class="frm-num">Rp {{ $rp($invoice->subtotal) }}</dd></div>
                    <div><dt>Diskon</dt><dd class="frm-num">Rp {{ $rp($invoice->discount) }}</dd></div>
                    <div><dt>Pajak</dt><dd class="frm-num">Rp {{ $rp($invoice->tax) }}</dd></div>
                    <div class="is-total"><dt>Grand Total</dt><dd class="frm-num">Rp {{ $rp($invoice->grand_total) }}</dd></div>
                    <div><dt>Terbayar</dt><dd class="frm-num">Rp {{ $rp($invoice->paid_amount) }}</dd></div>
                </dl>
            </section>

            {{-- Riwayat payment --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Riwayat Payment</h2>
                    <span class="frm-count">{{ $invoice->payments->count() }} payment</span>
                </div>

                @if ($invoice->payments->count())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th class="is-num">Jumlah</th>
                                    <th>Diterima Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->payments as $payment)
                                    <tr>
                                        <td><span class="frm-code">{{ $payment->code }}</span></td>
                                        <td data-label="Tanggal" class="frm-nowrap">{{ $payment->paid_at->format('d M Y') }}</td>
                                        <td data-label="Metode" class="frm-cap">{{ $payment->method }}</td>
                                        <td data-label="Jumlah" class="is-num"><span class="frm-num is-strong">Rp {{ $rp($payment->amount) }}</span></td>
                                        <td data-label="Diterima">{{ $payment->receivedBy->name ?? 'Sales (lapangan)' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="frm-empty">
                        <div class="frm-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        </div>
                        <p class="frm-empty-title">Belum ada payment</p>
                        <p class="frm-empty-text">Pembayaran untuk invoice ini akan tercatat di sini.</p>
                        @if (! $invoice->isFullyPaid())
                            <a href="{{ route('admin.finance.payments.create', $invoice) }}" class="adm-btn adm-btn-primary adm-btn-sm">Catat Payment</a>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>