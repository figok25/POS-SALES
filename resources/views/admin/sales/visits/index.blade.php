<x-admin-layout>
    @php
        $status = $status ?? null;
        $tabs = [
            ''          => 'Semua',
            'ongoing'   => 'Sedang Berjalan',
            'completed' => 'Selesai',
        ];
        $emptyText = [
            'ongoing'   => 'Tidak ada kunjungan yang sedang berjalan.',
            'completed' => 'Belum ada kunjungan yang selesai.',
        ][$status ?? ''] ?? 'Kunjungan akan tampil di sini setelah Sales melakukan check-in.';
        $duration = function ($in, $out) {
            if (! $out) {
                return null;
            }
            $mins = abs((int) $in->diffInMinutes($out));
            return $mins >= 60 ? intdiv($mins, 60) . ' j ' . ($mins % 60) . ' m' : $mins . ' m';
        };
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Kunjungan (Visit)</h1>
            <p class="frm-sub">Pantau check-in dan check-out Sales di setiap customer.</p>
        </div>
        @can('sales-management.manage')
            <a href="{{ route('admin.sales.visits.create-for-sales') }}" class="adm-btn adm-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Check-in Bantuan
            </a>
        @endcan
    </div>

    <section class="panel">
        {{-- Tab status (filter lain ikut terbawa) --}}
        <nav class="frm-tabs" aria-label="Status kunjungan">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.sales.visits.index', array_filter(array_merge(request()->except(['status', 'page']), ['status' => $key]), fn ($v) => $v !== '' && $v !== null)) }}"
                   class="frm-tab {{ ($status ?? '') === $key ? 'is-active' : '' }}"
                   @if (($status ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <span class="frm-count">{{ number_format($summary['count'], 0, ',', '.') }} kunjungan · {{ number_format($summary['sales'], 0, ',', '.') }} Sales · {{ number_format($summary['stores'], 0, ',', '.') }} toko</span>
        </nav>

        {{-- Filter lengkap --}}
        <form method="GET" class="frm-toolbar" style="flex-wrap:wrap;gap:.5rem;align-items:flex-end">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif

            @if ($branches->isNotEmpty())
            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Depo
                <select name="branch" class="frm-input is-select is-filter" aria-label="Filter Depo">
                    <option value="all" @selected($filters['branch'] === 'all')>Semua Depo</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected($filters['branch'] === (string) $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </label>
            @endif

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Dari Tanggal
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="frm-input" aria-label="Dari tanggal">
            </label>
            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Sampai Tanggal
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="frm-input" aria-label="Sampai tanggal">
            </label>

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Sales
                <select name="sales_id" class="frm-input is-select is-filter" aria-label="Filter Sales">
                    <option value="">Semua Sales</option>
                    @foreach ($salesList as $s)
                        <option value="{{ $s->id }}" @selected((string) $filters['sales_id'] === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Kondisi
                <select name="condition" class="frm-input is-select is-filter" aria-label="Filter Kondisi">
                    <option value="">Semua Kondisi</option>
                    <option value="normal" @selected($filters['condition'] === 'normal')>Normal</option>
                    <option value="closed" @selected($filters['condition'] === 'closed')>Toko Tutup</option>
                    <option value="other" @selected($filters['condition'] === 'other')>Kendala Lain</option>
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Hasil
                <select name="outcome" class="frm-input is-select is-filter" aria-label="Filter Hasil">
                    <option value="">Semua Hasil</option>
                    <option value="transaction" @selected($filters['outcome'] === 'transaction')>Transaksi (EC)</option>
                    <option value="no_transaction" @selected($filters['outcome'] === 'no_transaction')>Tanpa Transaksi (Call Made)</option>
                    <option value="closed" @selected($filters['outcome'] === 'closed')>Toko Tutup</option>
                    <option value="ongoing" @selected($filters['outcome'] === 'ongoing')>Sedang Berkunjung</option>
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:.15rem;font-size:.75rem">Cari
                <input type="search" name="q" value="{{ $filters['q'] }}" class="frm-input" placeholder="Toko / kode / Sales">
            </label>

            <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan</button>

            @if ($isFiltered)
                <a href="{{ route('admin.sales.visits.index', ['branch' => 'all']) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th>Customer</th>
                            <th>Kondisi</th>
                            <th>Hasil</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php $dur = $duration($item->check_in_at, $item->check_out_at); @endphp
                            <tr>
                                <td><span class="frm-name">{{ $item->sales->name ?? '-' }}</span></td>
                                <td data-label="Customer">{{ $item->customer->name ?? '-' }}
                                    @if ($item->isAssisted())<br><span class="frm-status is-warn">{{ $item->assistedLabel() }}</span>@endif
                                </td>
                                <td data-label="Kondisi">
                                    @if ($item->check_in_condition === 'normal')
                                        <span class="frm-status is-on">Normal</span>
                                    @elseif ($item->check_in_condition === 'closed')
                                        <span class="frm-status is-danger">{{ \App\Models\Visit::conditionLabel($item->check_in_condition) }}</span>
                                    @elseif ($item->check_in_condition)
                                        <span class="frm-status is-warn">{{ \App\Models\Visit::conditionLabel($item->check_in_condition) }}</span>
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td data-label="Hasil">
                                    @php $outcome = $item->outcome(); @endphp
                                    <span class="frm-status {{ ['closed' => 'is-danger', 'transaction' => 'is-on', 'no_transaction' => 'is-warn', 'ongoing' => 'is-warn'][$outcome] ?? 'is-off' }}">{{ \App\Models\Visit::outcomeLabel($outcome) }}</span>
                                </td>
                                <td data-label="Check-in" class="frm-nowrap">{{ $item->check_in_at->format('d M Y H:i') }}</td>
                                <td data-label="Check-out" class="frm-nowrap">
                                    @if ($item->check_out_at)
                                        {{ $item->check_out_at->format('d M Y H:i') }}
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td data-label="Durasi" class="frm-nowrap">
                                    @if ($dur && ! $item->isAssisted()) {{ $dur }} @else <span class="frm-dash">—</span> @endif
                                </td>
                                <td class="frm-cell-status">
                                    @if ($item->status === 'ongoing')
                                        <span class="frm-status is-warn">Berjalan</span>
                                    @else
                                        <span class="frm-status is-on">Selesai</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <a href="{{ route('admin.sales.visits.show', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
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
                <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada kunjungan yang cocok dengan filter.' : $emptyText }}</p>
            </div>
        @endif
    </section>
</x-admin-layout>