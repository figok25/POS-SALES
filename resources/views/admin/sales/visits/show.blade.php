<x-admin-layout>
    @php
        $hasInLoc  = filled($visit->check_in_latitude) && filled($visit->check_in_longitude);
        $hasOutLoc = filled($visit->check_out_latitude) && filled($visit->check_out_longitude);
        $mins      = $visit->check_out_at ? abs((int) $visit->check_in_at->diffInMinutes($visit->check_out_at)) : null;
        $duration  = $mins === null ? null : ($mins >= 60 ? intdiv($mins, 60) . ' jam ' . ($mins % 60) . ' menit' : $mins . ' menit');
    @endphp

    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Detail Kunjungan</h1>
                <p class="frm-sub">{{ $visit->customer->name ?? '-' }} &middot; {{ $visit->check_in_at->format('d M Y') }}</p>
            </div>
            <a href="{{ route('admin.sales.visits.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Informasi Kunjungan</h2>
                @if ($visit->status === 'ongoing')
                    <span class="frm-status is-warn">Berjalan</span>
                @else
                    <span class="frm-status is-on">Selesai</span>
                @endif
            </div>

            <dl class="frm-detail">
                <div><dt>Sales</dt><dd>{{ $visit->sales->name ?? '-' }}</dd></div>
                <div><dt>Customer</dt><dd>{{ $visit->customer->name ?? '-' }}</dd></div>
                <div><dt>Check-in</dt><dd>{{ $visit->check_in_at->format('d M Y H:i') }}</dd></div>
                <div>
                    <dt>Lokasi Check-in</dt>
                    <dd>
                        @if ($hasInLoc)
                            <a class="panel-link" target="_blank" rel="noopener"
                               href="https://maps.google.com/?q={{ $visit->check_in_latitude }},{{ $visit->check_in_longitude }}">{{ $visit->check_in_latitude }}, {{ $visit->check_in_longitude }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div><dt>Check-out</dt><dd>{{ $visit->check_out_at?->format('d M Y H:i') ?? '-' }}</dd></div>
                @if ($visit->check_out_at)
                    <div>
                        <dt>Lokasi Check-out</dt>
                        <dd>
                            @if ($hasOutLoc)
                                <a class="panel-link" target="_blank" rel="noopener"
                                   href="https://maps.google.com/?q={{ $visit->check_out_latitude }},{{ $visit->check_out_longitude }}">{{ $visit->check_out_latitude }}, {{ $visit->check_out_longitude }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div><dt>Durasi</dt><dd>{{ $duration }}</dd></div>
                @endif
                <div>
                    <dt>Kondisi Outlet</dt>
                    <dd>
                        @if ($visit->check_in_condition === 'normal')
                            <span class="frm-status is-on">Normal</span>
                        @elseif ($visit->check_in_condition === 'closed')
                            <span class="frm-status is-danger">{{ \App\Models\Visit::conditionLabel($visit->check_in_condition) }}</span>
                        @elseif ($visit->check_in_condition)
                            <span class="frm-status is-warn">{{ \App\Models\Visit::conditionLabel($visit->check_in_condition) }}</span>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div><dt>Keterangan Check-in</dt><dd>{{ $visit->notes ?? '-' }}</dd></div>
                @if ($visit->check_out_at)
                    <div><dt>Keterangan Check-out</dt><dd>{{ $visit->check_out_notes ?? '-' }}</dd></div>
                @endif
            </dl>
        </section>

        {{-- RevisiMinor #6: info Promosi/POSM hanya ada untuk kunjungan baru (diamati saat check-in) --}}
        @if ($visit->check_in_condition)
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Promosi &amp; POSM Outlet</h2>
                </div>

                @if ($visit->hasFacilityInfo())
                    <dl class="frm-detail">
                        @foreach (['has_promo' => 'Program promosi', 'has_posm' => 'POSM', 'has_banner' => 'Banner'] as $field => $label)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>
                                    @if ($visit->{$field} === null)
                                        -
                                    @elseif ($visit->{$field})
                                        <span class="frm-status is-on">Ada</span>
                                    @else
                                        <span class="frm-status is-off">Tidak ada</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                        <div><dt>Keterangan</dt><dd>{{ $visit->facility_notes ?? '-' }}</dd></div>
                    </dl>
                @else
                    <div class="frm-empty">
                        <p class="frm-empty-text">Tidak diamati pada kunjungan ini ({{ \App\Models\Visit::conditionLabel($visit->check_in_condition) }}).</p>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-admin-layout>