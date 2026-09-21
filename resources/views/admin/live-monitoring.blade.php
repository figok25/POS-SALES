<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Live Sales Monitoring</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{--
        Section 3.2: Admin Map -> MapLibre GL JS.
        CDN dipakai di sini supaya contoh ini langsung jalan tanpa build step (npm/vite).
        Kalau project kamu sudah pakai bundler (Vite/Mix), pindahkan ke `npm install maplibre-gl`
        dan import biasa -- lebih baik untuk production.
    --}}
    <link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>

    <style>
        html, body, #map { height: 100%; margin: 0; padding: 0; }
        .sales-popup { font-family: sans-serif; font-size: 13px; }
        .status-badge {
            display: inline-block; padding: 2px 8px; border-radius: 10px;
            font-size: 11px; color: #fff;
        }
        .status-ACTIVE { background: #2e7d32; }
        .status-PAUSED { background: #f9a825; }
        .status-STOPPED, .status-PERMISSION_LOST { background: #c62828; }
    </style>
</head>
<body>
<div id="map"></div>

<script>
    // Section 96-97: tile style publik gratis (OpenFreeMap, vector, tanpa API key).
    // Untuk production, tinjau kebijakan/kapasitas provider tile pilihan kamu.
    const STYLE_URL = "https://tiles.openfreemap.org/styles/liberty";
    const DATA_URL = "{{ route('admin.live-monitoring.data') }}";
    const POLL_INTERVAL_MS = 15000; // selaras dengan interval kirim GPS Android (AppConfig.LOCATION_UPDATE_INTERVAL_MS)

    const map = new maplibregl.Map({
        container: 'map',
        style: STYLE_URL,
        center: [106.8272, -6.1751], // TODO: ganti ke koordinat default cabang kamu
        zoom: 11
    });
    map.addControl(new maplibregl.NavigationControl());

    const markers = {}; // key: sales id -> maplibregl.Marker

    function renderSalesMarkers(salesList) {
        const seenIds = new Set();

        salesList.forEach(sales => {
            seenIds.add(sales.id);
            const lngLat = [parseFloat(sales.last_longitude), parseFloat(sales.last_latitude)];

            if (markers[sales.id]) {
                markers[sales.id].setLngLat(lngLat);
                markers[sales.id].getPopup().setHTML(popupHtml(sales));
            } else {
                const popup = new maplibregl.Popup({ offset: 16 }).setHTML(popupHtml(sales));
                const marker = new maplibregl.Marker({ color: '#1976D2' })
                    .setLngLat(lngLat)
                    .setPopup(popup)
                    .addTo(map);
                markers[sales.id] = marker;
            }
        });

        // Hapus marker Sales yang sudah tidak ada di response (mis. logout)
        Object.keys(markers).forEach(id => {
            if (!seenIds.has(parseInt(id))) {
                markers[id].remove();
                delete markers[id];
            }
        });
    }

    function popupHtml(sales) {
        const status = sales.tracking_status || 'STOPPED';
        return `<div class="sales-popup">
            <strong>${sales.name}</strong><br>
            <span class="status-badge status-${status}">${status}</span><br>
            <small>Update terakhir: ${sales.last_location_at ?? '-'}</small>
        </div>`;
    }

    async function fetchAndRender() {
        try {
            const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (json.success) {
                renderSalesMarkers(json.sales);
            }
        } catch (e) {
            console.error('Gagal memuat live monitoring:', e);
        }
    }

    map.on('load', () => {
        fetchAndRender();
        setInterval(fetchAndRender, POLL_INTERVAL_MS);
    });
</script>
</body>
</html>
