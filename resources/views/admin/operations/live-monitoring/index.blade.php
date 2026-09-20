<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Live Monitoring Sales</h1>
            <div class="flex items-center gap-2">
                <span id="last-refresh" class="text-xs text-gray-400"></span>
                <select id="branch-filter" class="border rounded px-3 py-2 text-sm">
                    <option value="">Semua Branch</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-3">
                <div id="map" class="w-full rounded shadow bg-gray-200" style="height: 70vh;"></div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded shadow">
                    <div class="px-3 py-2 border-b font-medium text-sm">Sales Sedang Tracking</div>
                    <div id="sales-list" class="divide-y max-h-[70vh] overflow-y-auto">
                        <p class="px-3 py-6 text-center text-gray-400 text-sm">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-3">
            Halaman ini polling otomatis tiap {{ $refreshSeconds }} detik (MVP, bukan WebSocket). Klik marker atau nama Sales untuk melihat jejak perjalanan hari ini.
        </p>
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

        function timeAgo(iso) {
            if (!iso) return '-';
            const diffSec = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
            if (diffSec < 60) return `${diffSec} detik lalu`;
            if (diffSec < 3600) return `${Math.floor(diffSec / 60)} menit lalu`;
            return `${Math.floor(diffSec / 3600)} jam lalu`;
        }

        function buildMarkerElement(status) {
            const el = document.createElement('div');
            el.style.width = '16px';
            el.style.height = '16px';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid white';
            el.style.boxShadow = '0 0 2px rgba(0,0,0,0.5)';
            el.style.background = STATUS_COLOR[status] || STATUS_COLOR.offline;
            return el;
        }

        async function showTrack(salesId) {
            const url = salesLocationsUrlTemplate.replace('__ID__', salesId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
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
        }

        // BUGFIX/PRINSIP DESAIN: marker di-UPDATE posisinya (setLngLat),
        // BUKAN dihapus-lalu-dibuat-ulang tiap poll -- supaya tidak flicker
        // dan hemat resource browser saat jumlah Sales banyak.
        function renderSales(items) {
            const listEl = document.getElementById('sales-list');
            listEl.innerHTML = '';

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
                    markers[salesId].getElement().style.background = STATUS_COLOR[status] || STATUS_COLOR.offline;
                } else {
                    const el = buildMarkerElement(status);
                    const marker = new maplibregl.Marker({ element: el })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup({ offset: 12 }).setHTML(
                            `<p class="font-medium text-sm">${name}</p><p class="text-xs">${STATUS_LABEL[status] || status}</p>`
                        ))
                        .addTo(map);
                    el.addEventListener('click', () => showTrack(salesId));
                    markers[salesId] = marker;
                }

                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between';
                row.innerHTML = `
                    <span>
                        <span class="inline-block w-2 h-2 rounded-full mr-2" style="background:${STATUS_COLOR[status] || STATUS_COLOR.offline}"></span>
                        ${name}
                    </span>
                    <span class="text-xs text-gray-400">${timeAgo(item.last_seen_at)}</span>
                `;
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
                listEl.innerHTML = '<p class="px-3 py-6 text-center text-gray-400 text-sm">Tidak ada Sales yang sedang tracking.</p>';
            }

            document.getElementById('last-refresh').textContent = 'Update terakhir: ' + new Date().toLocaleTimeString('id-ID');
        }

        async function poll() {
            const branchId = document.getElementById('branch-filter').value;
            const url = branchId ? `${liveSalesUrl}?branch_id=${branchId}` : liveSalesUrl;

            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (json.success) renderSales(json.data);
            } catch (e) {
                console.error('Gagal polling live-sales', e);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
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

            document.getElementById('branch-filter').addEventListener('change', poll);
        });

        window.addEventListener('beforeunload', () => {
            if (pollTimer) clearInterval(pollTimer);
        });
    </script>
</x-admin-layout>
