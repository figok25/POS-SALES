<x-admin-layout>
    <x-slot name="header">Rute Toko per Sales</x-slot>

    @php
        $selectedDay   = collect($weekDays)->firstWhere('isSelected', true);
        $selectedDate  = $selectedDay['date'] ?? null;
        $selectedLabel = $selectedDay['label'] ?? '';
    @endphp

    {{-- Info: dibuka dari tautan Sales Task --}}
    @if ($anchorDate)
        <div class="frm-alert is-info" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span class="frm-alert-text">
                Menampilkan rute untuk tanggal <strong>{{ $anchorDate->format('d M Y') }}</strong> (dibuka dari tautan Sales Task).
                <a href="{{ route('admin.sales.route-map.index') }}" class="panel-link">Lihat minggu berjalan</a>
            </span>
        </div>
    @endif

    <div class="frm-stack">
        {{-- Pilih Sales --}}
        <section class="panel">
            <div class="frm-panel-body">
                <div class="frm-field">
                    <label for="route-sales" class="frm-label">Sales <span class="frm-opt">(yang bertugas hari ini)</span></label>
                    <select id="route-sales" class="frm-input is-select is-wide"
                            onchange="if (this.value) window.location.href = this.value">
                        @forelse ($salesList as $s)
                            <option value="{{ route('admin.sales.route-map.index', ['sales_id' => $s->id, 'date' => $selectedDate?->toDateString()]) }}"
                                    @selected($selectedSales && $s->id === $selectedSales->id)>
                                {{ $s->name }}
                            </option>
                        @empty
                            <option value="">Tidak ada Sales dengan Rute Kanvas hari ini</option>
                        @endforelse
                    </select>
                    <p class="frm-hint">
                        Sales yang belum punya Rute Kanvas untuk hari ini tidak muncul di sini. Atur dulu di
                        <a href="{{ route('admin.sales.visit-plans.index') }}" class="panel-link">menu Visit Plan</a>.
                    </p>
                </div>
            </div>
        </section>

        @if (! $selectedSales)
            <section class="panel">
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <p class="frm-empty-title">Tidak ada Sales yang bertugas</p>
                    <p class="frm-empty-text">Tidak ada Sales yang punya Rute Kanvas pada hari yang dipilih.</p>
                </div>
            </section>
        @else
            @php
                $total        = $customers->count();
                $visitedCount = $customers->filter(fn ($c) => $visitedCustomerIds->contains($c->id))->count();
                $pendingCount = $total - $visitedCount;
                $percent      = $total > 0 ? round($visitedCount / $total * 100) : 0;
                $orderById    = $customers->pluck('id')->values()->flip();
            @endphp

            {{-- Hari + ringkasan --}}
            <section class="panel">
                <nav class="frm-days" aria-label="Pilih hari">
                    @foreach ($weekDays as $option)
                        <a href="{{ route('admin.sales.route-map.index', ['sales_id' => $selectedSales->id, 'date' => $option['date']->toDateString()]) }}"
                           @class(['frm-day', 'is-active' => $option['isSelected'], 'is-today' => ! $option['isSelected'] && $option['isToday']])
                           @if ($option['isSelected']) aria-current="date" @endif>
                            <span class="frm-day-label">{{ $option['label'] }}</span>
                            <span class="frm-day-date">{{ $option['date']->format('d M') }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="frm-route-meta">
                    <p>
                        <strong>{{ $total }}</strong> toko untuk <strong>{{ $selectedSales->name }}</strong>
                        di hari {{ $selectedLabel }}.
                    </p>
                    <a href="{{ route('admin.sales.visit-plans.edit', $selectedSales) }}" class="panel-link">Edit Rute Kanvas</a>
                </div>

                <div class="frm-route-body">
                    @if ($usingFallback)
                        <div class="frm-alert is-warn" role="status">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <span class="frm-alert-text">Rute Kanvas hari ini belum diatur untuk Sales ini &mdash; menampilkan semua toko yang di-assign.</span>
                        </div>
                    @endif

                    @if ($customers->isNotEmpty())
                        <div class="frm-legend">
                            <span><i class="frm-dot is-visited"></i> Sudah dikunjungi ({{ $visitedCount }})</span>
                            <span><i class="frm-dot"></i> Belum dikunjungi ({{ $pendingCount }})</span>
                            <span class="frm-legend-total">{{ $percent }}% selesai</span>
                        </div>
                        <div class="frm-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="Progres kunjungan">
                            <span style="--p: {{ $percent }}%"></span>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Peta --}}
            <section class="panel">
                @if ($customersWithLocation->isEmpty())
                    <p class="panel-empty">Belum ada Customer dengan titik lokasi yang tersimpan untuk hari ini.</p>
                @else
                    <div id="map" class="frm-map"></div>
                @endif
            </section>

            {{-- Daftar toko --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Daftar Toko</h2>
                    <span class="frm-count">{{ $total }} toko</span>
                </div>

                @if ($customers->isEmpty())
                    <p class="panel-empty">Belum ada Customer yang di-assign ke Sales ini.</p>
                @else
                    <ul class="row-list">
                        @foreach ($customers as $index => $customer)
                            @php $isVisited = $visitedCustomerIds->contains($customer->id); @endphp
                            <li class="row-item is-stop">
                                <div class="frm-stop">
                                    <span @class(['frm-stop-no', 'is-visited' => $isVisited])>
                                        @if ($isVisited)
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-label="Sudah dikunjungi"><path d="M20 6 9 17l-5-5"/></svg>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                    <div class="row-main">
                                        <span class="frm-name">{{ $customer->name }}</span>
                                        <p class="row-sub">{{ $customer->address ?? 'Alamat belum diisi' }}</p>
                                    </div>
                                </div>
                                <div class="row-side">
                                    <span @class(['frm-status', 'is-on' => $isVisited, 'is-off' => ! $isVisited])>{{ $isVisited ? 'Sudah Dikunjungi' : 'Belum Dikunjungi' }}</span>
                                    <span @class(['frm-loc', 'is-set' => $customer->hasLocation()])>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $customer->hasLocation() ? 'Ada Lokasi' : 'Belum Ada Lokasi' }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>

    @if ($selectedSales && $customersWithLocation->isNotEmpty())
        @php
            $customerMarkersData = $customersWithLocation->map(fn ($c) => [
                'id'      => $c->id,
                'name'    => $c->name,
                'order'   => ($orderById[$c->id] ?? 0) + 1,
                'lat'     => (float) $c->latitude,
                'lng'     => (float) $c->longitude,
                'visited' => $visitedCustomerIds->contains($c->id),
            ])->values();
        @endphp
        @push('styles')
        <link href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css" rel="stylesheet" />
        @endpush
        @push('scripts')
        <script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"></script>
        <script>
            const customerMarkers = @json($customerMarkersData);

            document.addEventListener('DOMContentLoaded', () => {
                const map = new maplibregl.Map({
                    container: 'map',
                    style: @js($mapStyleUrl),
                    center: [customerMarkers[0].lng, customerMarkers[0].lat],
                    zoom: 12,
                });
                map.addControl(new maplibregl.NavigationControl(), 'top-right');

                const bounds = new maplibregl.LngLatBounds();

                customerMarkers.forEach((c) => {
                    // Penanda: hijau + centang = sudah dikunjungi, abu-abu bernomor = belum.
                    // Nomor mengikuti urutan di Daftar Toko. Gaya ada di .frm-marker (form.css).
                    const el = document.createElement('div');
                    el.className = 'frm-marker' + (c.visited ? ' is-visited' : '');
                    el.textContent = c.visited ? '✓' : c.order;

                    // Popup dibuat lewat DOM + textContent (bukan HTML string) agar nama toko aman.
                    const content = document.createElement('div');
                    const title = document.createElement('div');
                    title.className = 'frm-popup-title';
                    title.textContent = c.order + '. ' + c.name;
                    const sub = document.createElement('div');
                    sub.className = 'frm-popup-sub' + (c.visited ? ' is-visited' : '');
                    sub.textContent = c.visited ? '✓ Sudah dikunjungi' : 'Belum dikunjungi';
                    content.append(title, sub);

                    new maplibregl.Marker({ element: el })
                        .setLngLat([c.lng, c.lat])
                        .setPopup(new maplibregl.Popup({ offset: 16 }).setDOMContent(content))
                        .addTo(map);

                    bounds.extend([c.lng, c.lat]);
                });

                if (customerMarkers.length > 1) {
                    map.fitBounds(bounds, { padding: 40, maxZoom: 15 });
                }
            });
        </script>
        @endpush
    @endif
</x-admin-layout>