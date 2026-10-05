<x-admin-layout>
    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Live Monitoring Sales</h1>
            <p class="frm-sub">
                Polling otomatis tiap {{ $refreshSeconds }} detik (MVP, bukan WebSocket).
                Klik marker atau nama Sales untuk melihat jejak perjalanan hari ini.
            </p>
        </div>
        <div class="frm-head-actions">
            @if (auth()->user()->isSuperAdmin())
                <select id="branch-filter" class="frm-input is-select is-filter" aria-label="Filter branch">
                    <option value="all">Semua Branch</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            @else
                <span class="frm-code">Depo: {{ auth()->user()->branch->name ?? '-' }}</span>
            @endif
        </div>
    </div>

    {{-- Gagal memuat data (ditampilkan lewat JS) --}}
    <div id="live-error" hidden>
        <div class="frm-alert is-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">Gagal memuat data Sales. Mencoba lagi pada pembaruan berikutnya.</span>
        </div>
    </div>

    <div class="frm-live">
        {{-- Peta --}}
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Peta Sales</h2>
                <span id="last-refresh" class="frm-count" aria-live="polite"></span>
            </div>
            <div class="frm-route-body">
                <div id="live-legend" class="frm-legend" aria-label="Keterangan status"></div>
            </div>
            <div id="map" class="frm-map is-tall"></div>
        </section>

        {{-- Daftar Sales --}}
        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Sales Sedang Tracking</h2>
                <span id="sales-count" class="frm-count"></span>
            </div>
            <div id="sales-list" class="frm-live-list" aria-live="polite">
                <p class="panel-empty">Memuat...</p>
            </div>
        </section>
    </div>

    <link href="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/maplibre-gl@4/dist/maplibre-gl.js"></script>
    <script>
        const mapStyleUrl = @js($mapStyleUrl);
        const refreshSeconds = @js($refreshSeconds);
        const liveSalesUrl = @js(route('api.admin.live-sales'));
        // route('api.admin.sales.locations', ...) butuh param sales -- kita
        // rakit manual pakai placeholder supaya tidak perlu route() per-baris.
        const salesLocationsUrlTemplate = @js(route('api.admin.sales.locations', ['sales' => '__ID__']));

        const STATUS_COLOR = {
            active: '#16a34a',       // hijau
            idle: '#eab308',         // kuning
            at_customer: '#2563eb',  // biru
            signal_lost: '#f97316',  // oranye
            offline: '#9ca3af',      // abu terang
            off_duty: '#4b5563',     // abu gelap
        };

        const STATUS_LABEL = {
            active: 'Aktif',
            idle: 'Diam Sebentar',
            at_customer: 'Di Customer',
            signal_lost: 'Sinyal Hilang',
            offline: 'Offline',
            off_duty: 'Selesai Kerja',
        };

        let map;
        let markers = {};   // sales_id -> maplibregl.Marker (di-reuse, TIDAK dibuat ulang tiap poll)
        let trackLayerAdded = false;
        let pollTimer = null;

        const colorOf = (status) => STATUS_COLOR[status] || STATUS_COLOR.offline;

        // Buat elemen dengan teks aman (tanpa innerHTML) agar nama Sales tidak bisa menyisipkan HTML.
        function el(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }

        function timeAgo(iso) {
            if (!iso) return '-';
            const diffSec = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
            if (diffSec < 60) return `${diffSec} detik lalu`;
            if (diffSec < 3600) return `${Math.floor(diffSec / 60)} menit lalu`;
            return `${Math.floor(diffSec / 3600)} jam lalu`;
        }

        function renderLegend() {
            const legend = document.getElementById('live-legend');
            Object.keys(STATUS_LABEL).forEach((key) => {
                const item = el('span');
                const dot = el('i', 'frm-dot');
                dot.style.background = STATUS_COLOR[key];
                item.append(dot, STATUS_LABEL[key]);
                legend.appendChild(item);
            });
        }

        function buildMarkerElement(status) {
            const node = el('div', 'frm-marker');
            node.style.width = '18px';
            node.style.height = '18px';
            node.style.background = colorOf(status);
            return node;
        }

        function buildPopupContent(name, status) {
            const box = el('div');
            box.append(el('div', 'frm-popup-title', name), el('div', 'frm-popup-sub', STATUS_LABEL[status] || status));
            return box;
        }

        async function showTrack(salesId) {
            try {
                const url = salesLocationsUrlTemplate.replace('__ID__', salesId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const json = await res.json();
                if (!json.success) return;

                const coords = json.data.map((p) => [parseFloat(p.longitude), parseFloat(p.latitude)]);
                if (coords.length === 0) return;

                const geojson = {
                    type: 'Feature',
                    geometry: { type: 'LineString', coordinates: coords },
                };

                if (trackLayerAdded) {
                    map.getSource('sales-track').setData(geojson);
                } else {
                    map.addSource('sales-track', { type: 'geojson', data: geojson });
                    map.addLayer({
                        id: 'sales-track-line',
                        type: 'line',
                        source: 'sales-track',
                        paint: { 'line-color': '#2563eb', 'line-width': 3, 'line-opacity': 0.7 },
                    });
                    trackLayerAdded = true;
                }

                const bounds = new maplibregl.LngLatBounds();
                coords.forEach((c) => bounds.extend(c));
                map.fitBounds(bounds, { padding: 60, maxZoom: 16 });
            } catch (e) {
                console.error('Gagal memuat jejak Sales', e);
            }
        }

        // BUGFIX/PRINSIP DESAIN: marker di-UPDATE posisinya (setLngLat),
        // BUKAN dihapus-lalu-dibuat-ulang tiap poll -- supaya tidak flicker
        // dan hemat resource browser saat jumlah Sales banyak.
        function renderSales(items) {
            const listEl = document.getElementById('sales-list');
            listEl.replaceChildren();

            const seenIds = new Set();

            items.forEach((item) => {
                const salesId = item.sales_id;
                const lng = parseFloat(item.longitude);
                const lat = parseFloat(item.latitude);
                const status = item.status;
                const name = item.sales?.name || `Sales #${salesId}`;
                seenIds.add(salesId);

                if (markers[salesId]) {
                    markers[salesId].setLngLat([lng, lat]);
                    markers[salesId].getElement().style.background = colorOf(status);
                    markers[salesId].getPopup().setDOMContent(buildPopupContent(name, status));
                } else {
                    const node = buildMarkerElement(status);
                    const marker = new maplibregl.Marker({ element: node })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup({ offset: 12 }).setDOMContent(buildPopupContent(name, status)))
                        .addTo(map);
                    node.addEventListener('click', () => showTrack(salesId));
                    markers[salesId] = marker;
                }

                const row = el('button', 'frm-live-item');
                row.type = 'button';

                const dot = el('i', 'frm-dot');
                dot.style.background = colorOf(status);

                const main = el('span', 'row-main');
                main.append(el('span', 'frm-name', name), el('span', 'row-sub', STATUS_LABEL[status] || status));

                row.append(dot, main, el('span', 'frm-meta', timeAgo(item.last_seen_at)));
                row.addEventListener('click', () => {
                    map.flyTo({ center: [lng, lat], zoom: 15 });
                    markers[salesId].togglePopup();
                    showTrack(salesId);
                });
                listEl.appendChild(row);
            });

            // Marker milik Sales yang sudah tidak muncul di response (mis.
            // difilter branch lain) dihapus dari peta.
            Object.keys(markers).forEach((id) => {
                if (!seenIds.has(Number(id))) {
                    markers[id].remove();
                    delete markers[id];
                }
            });

            if (items.length === 0) {
                listEl.appendChild(el('p', 'panel-empty', 'Tidak ada Sales yang sedang tracking.'));
            }

            document.getElementById('sales-count').textContent = items.length + ' Sales';
            document.getElementById('last-refresh').textContent = 'Update terakhir: ' + new Date().toLocaleTimeString('id-ID');
        }

        async function poll() {
            const branchFilterEl = document.getElementById('branch-filter');
            const branch = branchFilterEl ? branchFilterEl.value : null;
            const url = branch ? `${liveSalesUrl}?branch=${encodeURIComponent(branch)}` : liveSalesUrl;
            const errorEl = document.getElementById('live-error');

            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error('Respons tidak valid');
                renderSales(json.data);
                errorEl.hidden = true;
            } catch (e) {
                console.error('Gagal polling live-sales', e);
                errorEl.hidden = false;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderLegend();

            map = new maplibregl.Map({
                style: mapStyleUrl,
                container: 'map',
                center: [106.8272, -6.1751], // fallback Jakarta, dipindah otomatis begitu data masuk
                zoom: 11,
            });
            map.addControl(new maplibregl.NavigationControl(), 'top-right');

            map.on('load', () => {
                poll();
                pollTimer = setInterval(poll, refreshSeconds * 1000);
            });

            const branchFilterEl = document.getElementById('branch-filter');
            if (branchFilterEl) branchFilterEl.addEventListener('change', poll);
        });

        window.addEventListener('beforeunload', () => {
            if (pollTimer) clearInterval(pollTimer);
        });
    </script>
</x-admin-layout>