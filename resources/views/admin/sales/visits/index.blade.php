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
    </div>

    <section class="panel">
        {{-- Tab status --}}
        <nav class="frm-tabs" aria-label="Status kunjungan">
            @foreach ($tabs as $key => $label)
                <a href="{{ $key === '' ? route('admin.sales.visits.index') : route('admin.sales.visits.index', ['status' => $key]) }}"
                   class="frm-tab {{ ($status ?? '') === $key ? 'is-active' : '' }}"
                   @if (($status ?? '') === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} kunjungan</span>
        </nav>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Sales</th>
                            <th>Customer</th>
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
                                <td data-label="Customer">{{ $item->customer->name ?? '-' }}</td>
                                <td data-label="Check-in" class="frm-nowrap">{{ $item->check_in_at->format('d M Y H:i') }}</td>
                                <td data-label="Check-out" class="frm-nowrap">
                                    @if ($item->check_out_at)
                                        {{ $item->check_out_at->format('d M Y H:i') }}
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td data-label="Durasi" class="frm-nowrap">
                                    @if ($dur) {{ $dur }} @else <span class="frm-dash">—</span> @endif
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
                <p class="frm-empty-title">Belum ada data</p>
                <p class="frm-empty-text">{{ $emptyText }}</p>
            </div>
        @endif
    </section>
</x-admin-layout>