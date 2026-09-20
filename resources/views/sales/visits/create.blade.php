<x-sales-layout>
    <x-slot name="header">Check-in Kunjungan</x-slot>

    @if ($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales.visits.store') }}" onsubmit="return isiLokasi(event)" class="bg-white rounded-lg shadow p-4 space-y-3">
        @csrf
        <div>
            <label class="block text-sm text-gray-600 mb-1">Pilih Customer</label>
            <select name="customer_id" required class="w-full border rounded px-3 py-2 text-sm">
                <option value="">- Pilih Customer -</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>

        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <p id="lokasi-status" class="text-xs text-gray-500">Lokasi akan diambil otomatis saat Check-in.</p>

        <button type="submit" class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm">Check-in</button>
        <a href="{{ route('sales.visits.index') }}" class="block text-center text-sm text-gray-500">Batal</a>
    </form>

    <script>
        function isiLokasi(e) {
            e.preventDefault();
            const form = e.target;
            const status = document.getElementById('lokasi-status');
            const done = () => form.submit();

            // PERBAIKAN AUDIT #15: di dalam Sales APK, GPS HARUS dari Native
            // Bridge (Fused Location), bukan navigator.geolocation browser -
            // supaya konsisten dengan sumber lokasi yang dipakai tracking &
            // tidak kena blokir secure-context saat dibuka lewat http://IP.
            const bridge = window.Android || window.SalesNative;
            if (bridge && (bridge.getCurrentLocation || bridge.requestCurrentLocation)) {
                status.textContent = 'Mengambil lokasi dari GPS Native...';
                try {
                    const raw = bridge.getCurrentLocation ? bridge.getCurrentLocation() : bridge.requestCurrentLocation();
                    const loc = typeof raw === 'string' ? JSON.parse(raw) : raw;
                    document.getElementById('latitude').value = loc.latitude;
                    document.getElementById('longitude').value = loc.longitude;
                } catch (err) {
                    status.textContent = 'Gagal mengambil lokasi Native, mengirim tanpa koordinat.';
                }
                return done();
            }

            if (! navigator.geolocation) return done();
            status.textContent = 'Mengambil lokasi...';
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('latitude').value = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
                done();
            }, done);
            return false;
        }
    </script>
</x-sales-layout>
