<x-admin-layout>
    @php
        $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        $rp  = fn ($v) => number_format($v, 0, ',', '.');
    @endphp

    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Transaksi {{ $transaction->code }}</h1>
                <p class="frm-sub">{{ $transaction->customerLabel() }} &middot; {{ $transaction->created_at->format('d M Y H:i') }}</p>
            </div>
            <div class="frm-head-actions">
                @can('sales-management.manage')
                    @if ($transaction->status !== 'cancelled')
                        <a href="{{ route('admin.sales.transactions.edit', $transaction) }}" class="adm-btn adm-btn-primary adm-btn-sm">Edit Transaksi</a>
                    @endif
                @endcan
                <a href="{{ route('admin.sales.transactions.print', $transaction) }}" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Cetak
                </a>
                <a href="{{ route($transaction->isDepoSale() ? 'admin.depo.transactions.index' : 'admin.sales.transactions.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Kembali
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status"><span class="frm-alert-text">{{ session('status') }}</span></div>
        @endif
        @if (session('error'))
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ session('error') }}</span></div>
        @endif
        @foreach ((array) session('warnings', []) as $warning)
            <div class="frm-alert is-warn" role="alert"><span class="frm-alert-text">{{ $warning }}</span></div>
        @endforeach
        @if ($transaction->isRevised())
            <div class="frm-alert is-info" role="status"><span class="frm-alert-text">Transaksi ini pernah dikoreksi {{ $transaction->revision_count }}x (terakhir {{ $transaction->last_revised_at?->format('d/m/Y H:i') }}).</span></div>
        @endif

        <div class="frm-stack">
            {{-- Informasi transaksi --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi Transaksi</h2>
                    @if ($transaction->status === 'completed')
                        <span class="frm-status is-on">Completed</span>
                    @elseif ($transaction->status === 'cancelled')
                        <span class="frm-status is-danger">Cancelled</span>
                    @else
                        <span class="frm-status is-off">{{ ucfirst($transaction->status) }}</span>
                    @endif
                </div>
                <dl class="frm-detail">
                    <div><dt>Tanggal</dt><dd>{{ $transaction->created_at->format('d M Y H:i') }}</dd></div>
                    <div><dt>Kategori Harga</dt><dd>{{ $transaction->priceTypeLabel() }}</dd></div>
                    <div><dt>Sumber</dt><dd>{{ $transaction->isDepoSale() ? 'Penjualan langsung Depo' : 'Sales (lapangan)' }}</dd></div>
                    @if ($transaction->isDepoSale())
                        <div><dt>Depo</dt><dd>{{ $transaction->branch->name ?? '-' }}</dd></div>
                        <div><dt>Gudang</dt><dd>{{ $transaction->warehouse->name ?? '-' }}</dd></div>
                    @else
                        <div><dt>Sales</dt><dd>{{ $transaction->sales->name ?? '-' }}</dd></div>
                    @endif
                    <div><dt>Customer</dt><dd>{{ $transaction->customerLabel() }}</dd></div>
                    @if ($transaction->isEnteredForSales())
                        <div><dt>Diinput oleh</dt><dd>{{ $transaction->creator->name ?? 'Admin' }} (atas nama Sales) pada {{ $transaction->recorded_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                        @if ($transaction->entry_reason)
                            <div><dt>Catatan Input Bantuan</dt><dd>{{ $transaction->entry_reason }}</dd></div>
                        @endif
                    @endif
                    @if ($transaction->invoice)
                        <div>
                            <dt>Invoice</dt>
                            <dd><a href="{{ route('admin.sales.invoices.show', $transaction->invoice) }}" class="panel-link">{{ $transaction->invoice->code }}</a></dd>
                        </div>
                    @endif
                    @if ($transaction->notes)
                        <div><dt>Catatan</dt><dd>{{ $transaction->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Item --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Rincian Item</h2>
                    <span class="frm-count">{{ $transaction->items->count() }} item</span>
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
                            @foreach ($transaction->items as $line)
                                <tr>
                                    <td><span class="frm-name">{{ $line->product->name ?? '-' }}@if ($line->is_free) <em>(GRATIS{{ $line->shell_qty ? ' - tukar '.rtrim(rtrim(number_format($line->shell_qty, 2, '.', ''), '0'), '.').' cangkang' : '' }})</em>@endif</span></td>
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
                    <div><dt>Subtotal</dt><dd class="frm-num">Rp {{ $rp($transaction->subtotal) }}</dd></div>
                    <div><dt>Diskon</dt><dd class="frm-num">Rp {{ $rp($transaction->discount) }}</dd></div>
                    <div><dt>Pajak</dt><dd class="frm-num">Rp {{ $rp($transaction->tax) }}</dd></div>
                    <div class="is-total"><dt>Total</dt><dd class="frm-num">Rp {{ $rp($transaction->total) }}</dd></div>
                </dl>
            </section>

            @if (($history ?? collect())->isNotEmpty())
                <section class="panel">
                    <div class="panel-head"><h2 class="panel-title">Riwayat Koreksi</h2></div>
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead><tr><th>Waktu</th><th>Oleh</th><th>Alasan</th><th class="is-num">Total Lama</th><th class="is-num">Total Baru</th><th>Dampak Stok</th></tr></thead>
                            <tbody>
                                @foreach ($history as $log)
                                    @php
                                        $impact = collect($log->after['stock_impact'] ?? [])->filter(fn ($r) => (float) ($r['delta'] ?? 0) != 0.0);
                                        $names = \App\Models\Product::whereIn('id', $impact->pluck('product_id'))->pluck('name', 'id');
                                    @endphp
                                    <tr>
                                        <td data-label="Waktu">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                        <td data-label="Oleh">{{ $log->user->name ?? '-' }}</td>
                                        <td data-label="Alasan">{{ $log->after['reason'] ?? '-' }}</td>
                                        <td data-label="Total Lama" class="is-num"><span class="frm-num">Rp {{ $rp($log->after['old_total'] ?? 0) }}</span></td>
                                        <td data-label="Total Baru" class="is-num"><span class="frm-num">Rp {{ $rp($log->after['new_total'] ?? 0) }}</span></td>
                                        <td data-label="Dampak Stok">
                                            @forelse ($impact as $r)
                                                <div>{{ $names[$r['product_id']] ?? '#'.$r['product_id'] }}: {{ $qty($r['old']) }} &rarr; {{ $qty($r['new']) }}</div>
                                            @empty
                                                <span class="frm-meta">Stok tidak berubah</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-admin-layout>