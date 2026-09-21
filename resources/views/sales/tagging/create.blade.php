<x-sales-layout>
    <x-slot name="header">Tagging Toko Baru</x-slot>

    @if ($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('sales.tagging.store') }}" class="bg-white rounded-lg shadow p-4 space-y-3">
        @csrf
        <div>
            <label class="block text-sm text-gray-600 mb-1">Nama Toko</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Nomor Telepon</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Alamat</label>
            <textarea name="address" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('address') }}</textarea>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Tipe Toko</label>
            <select name="customer_type" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">- Pilih -</option>
                <option value="warung" @selected(old('customer_type') === 'warung')>Warung</option>
                <option value="toko" @selected(old('customer_type') === 'toko')>Toko</option>
                <option value="grosir" @selected(old('customer_type') === 'grosir')>Grosir</option>
                <option value="minimarket" @selected(old('customer_type') === 'minimarket')>Minimarket</option>
            </select>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Lokasi (GPS)</label>
            <button type="button" onclick="ambilLokasi()" class="w-full bg-gray-100 text-gray-700 text-sm px-3 py-2 rounded">📍 Ambil Lokasi Saat Ini</button>
            <p id="lokasi-status" class="text-xs text-gray-500 mt-1">Belum diambil.</p>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm">Kirim Tagging</button>
        <a href="{{ route('sales.tagging.index') }}" class="block text-center text-sm text-gray-500">Batal</a>
    </form>

    <script>
        function ambilLokasi() {
            const status = document.getElementById('lokasi-status');
            const native = window.Android || window.SalesNative;

            // Prioritaskan bridge native Android (Fused Location Provider):
            // navigator.geolocation TIDAK bisa dipakai di dalam WebView untuk
            // server dev HTTP (bukan HTTPS/localhost) -- Chromium menolak
            // Geolocation API di origin yang dianggap tidak aman, walau izin
            // lokasi Android sudah diizinkan user.
            if (native && typeof native.requestCurrentLocation === 'function') {
                status.textContent = 'Mengambil lokasi (GPS native)...';
                native.requestCurrentLocation();
                return;
            }

            // Fallback: browser biasa (mis. testing di Chrome desktop lewat
            // https/localhost, bukan di dalam APK).
            if (! navigator.geolocation) {
                status.textContent = 'Geolocation tidak didukung perangkat ini.';
                return;
            }
            status.textContent = 'Mengambil lokasi...';
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('latitude').value = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
                status.textContent = 'Lokasi didapat: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
            }, function () {
                status.textContent = 'Gagal mengambil lokasi. Pastikan izin GPS diaktifkan.';
            });
        }

        // Dipanggil balik oleh WebViewBridge.requestCurrentLocation() (Android native).
        window.onNativeLocationResult = function (data) {
            const status = document.getElementById('lokasi-status');
            if (data && data.available) {
                document.getElementById('latitude').value = data.latitude;
                document.getElementById('longitude').value = data.longitude;
                status.textContent = 'Lokasi didapat: ' + data.latitude.toFixed(5) + ', ' + data.longitude.toFixed(5)
                    + ' (akurasi ' + Math.round(data.accuracy) + 'm)';
            } else if (data && data.reason === 'PERMISSION_DENIED') {
                status.textContent = 'Izin lokasi belum diberikan. Buka Pengaturan > Aplikasi > Sales App > Izin > Lokasi.';
            } else {
                status.textContent = 'Gagal mendapatkan sinyal GPS. Pastikan GPS aktif & coba di area terbuka, lalu coba lagi.';
            }
        };
    </script>
</x-sales-layout>
