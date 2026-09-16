<x-admin-layout>
    <div class="p-6 max-w-4xl">
        <div class="detail-header">
            <h1 class="page-title">{{ $item->name }}</h1>
            <div class="detail-actions">
                <a href="{{ route('admin.sales.customer-assignments.edit', $item) }}" class="link-muted">Assign / Reassign Sales</a>
                <a href="{{ route('admin.master.customers.edit', $item) }}" class="link-muted">Edit</a>
                <a href="{{ route('admin.master.customers.index') }}" class="link-back">&larr; Kembali</a>
            </div>
        </div>

        <div class="panel panel-body detail-list mb-6">
            <div class="detail-row">
                <span class="detail-row-label">Kode</span>
                <span class="detail-row-value">{{ $item->code }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Sales Penanggung Jawab</span>
                <span class="detail-row-value">{{ $item->sales->name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Telepon</span>
                <span class="detail-row-value">{{ $item->phone ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Alamat</span>
                <span class="detail-row-value">{{ $item->address ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">NPWP</span>
                <span class="detail-row-value">{{ $item->npwp ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Status</span>
                <span class="detail-row-value">
                    @if ($item->is_active)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-neutral">Nonaktif</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h2 class="section-title">Riwayat Transaksi Penjualan</h2>
                <div class="list-panel">
                    @forelse ($transactions as $trx)
                        <a href="{{ route('admin.sales.transactions.show', $trx) }}" class="list-panel-row">
                            <span>{{ $trx->code }} <span class="list-panel-meta">({{ $trx->created_at->format('d M Y') }})</span></span>
                            <span>Rp {{ number_format($trx->total, 0, ',', '.') }}</span>
                        </a>
                    @empty
                        <p class="list-panel-empty">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="section-title">Riwayat Invoice</h2>
                <div class="list-panel">
                    @forelse ($invoices as $inv)
                        <a href="{{ route('admin.sales.invoices.show', $inv) }}" class="list-panel-row">
                            <span>{{ $inv->code }}</span>
                            <span>Rp {{ number_format($inv->grand_total, 0, ',', '.') }} - {{ ucfirst($inv->status) }}</span>
                        </a>
                    @empty
                        <p class="list-panel-empty">Belum ada invoice.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="section-title">Riwayat Kunjungan</h2>
                <div class="list-panel">
                    @forelse ($visits as $visit)
                        <div class="list-panel-row">
                            <span>{{ $visit->sales->name ?? '-' }}</span>
                            <span class="list-panel-meta">{{ $visit->check_in_at->format('d M Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="list-panel-empty">Belum ada kunjungan.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="section-title">Riwayat Customer Assignment</h2>
                <div class="list-panel">
                    @forelse ($assignments as $row)
                        <div class="list-panel-row {{ $row->isCurrent() ? 'is-current' : '' }}">
                            <span>{{ $row->sales->name ?? '-' }}</span>
                            <span class="list-panel-meta">{{ $row->assigned_at->format('d M Y') }}@if ($row->unassigned_at) &rarr; {{ $row->unassigned_at->format('d M Y') }}@endif</span>
                        </div>
                    @empty
                        <p class="list-panel-empty">Belum ada riwayat assignment.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="section-title">Riwayat Tagging</h2>
                <div class="list-panel">
                    @forelse ($taggings as $tag)
                        <a href="{{ route('admin.sales.customer-taggings.show', $tag) }}" class="list-panel-row">
                            <span>{{ $tag->sales->name ?? '-' }}</span>
                            <span>{{ ucfirst($tag->status) }}</span>
                        </a>
                    @empty
                        <p class="list-panel-empty">Belum ada tagging.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>