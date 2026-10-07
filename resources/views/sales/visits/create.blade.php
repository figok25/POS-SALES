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

    @if ($customers->isEmpty() && ! $usingFallback)
        <div class="bg-white rounded-lg shadow p-4 text-sm text-gray-500">
            Tidak ada toko terjadwal untuk dikunjungi hari ini di Rute Kanvas Anda.
        </div>
    @else
        @if ($usingFallback)
            <div class="mb-3 p-3 bg-amber-50 text-amber-800 rounded text-xs">
                Rute Kanvas Anda belum pernah diatur Admin -- daftar di bawah menampilkan semua toko yang di-assign ke Anda untuk sementara.
            </div>
        @endif
    <form method="POST" action="{{ route('sales.visits.store') }}" onsubmit="return isiLokasi(event)" class="bg-white rounded-lg shadow p-4 space-y-3">
        @csrf
        <div>
            <label class="block text-sm text-gray-600 mb-1">Pilih Customer</label>
            <x-searchable-select
                name="customer_id"
                :options="$customers->map(fn ($c) => ['id' => $c->id, 'label' => $c->name . ($c->code ? ' (' . $c->code . ')' : '')])"
                placeholder="Cari nama atau kode customer..."
                required
            />
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Kondisi Outlet <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Kondisi outlet">
                @foreach (['normal' => 'Normal', 'closed' => 'Toko Tutup', 'other' => 'Kendala Lain'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="condition" value="{{ $value }}" class="peer sr-only" required @checked(old('condition') === $value)>
                        <span class="block text-center border rounded px-2 py-2 text-sm text-gray-700 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Alasan wajib HANYA bila bermasalah (toko tutup / kendala lain). --}}
        <div>
            <label for="notes" class="block text-sm text-gray-600 mb-1">Keterangan / Alasan <span id="notes-star" class="text-red-500 hidden">*</span></label>
            <textarea id="notes" name="notes" rows="3" minlength="3" maxlength="1000" class="w-full border rounded px-3 py-2 text-sm" placeholder="Mis. toko buka, pemilik ada">{{ old('notes') }}</textarea>
            <p id="notes-hint" class="text-xs text-gray-400 mt-1">Opsional bila toko normal. Wajib diisi bila toko tutup atau ada kendala.</p>
        </div>

        {{-- Promosi / POSM: Sales cukup mencentang item yang ADA (tidak dicentang = Tidak ada).
             Selalu tampil, termasuk saat toko tutup. Daftar item dikelola Admin. --}}
        @if ($promoItems->isNotEmpty())
            @php $oldPromo = array_map('strval', (array) old('promo_items', [])); @endphp
            <fieldset class="border rounded p-3 space-y-2">
                <legend class="text-sm font-medium text-gray-700 px-1">Promosi &amp; POSM Outlet</legend>
                <p class="text-xs text-gray-400">Centang yang ada di outlet.</p>
                @foreach ($promoItems as $promo)
                    <label class="flex items-center justify-between gap-2 cursor-pointer">
                        <span class="text-sm text-gray-700">{{ $promo->name }}</span>
                        <span>
                            <input type="checkbox" name="promo_items[]" value="{{ $promo->id }}" class="peer sr-only" @checked(in_array((string) $promo->id, $oldPromo, true))>
                            <span class="block border rounded px-3 py-1 text-xs text-gray-500 peer-checked:hidden peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400">Tidak ada</span>
                            <span class="hidden border rounded px-3 py-1 text-xs bg-green-600 text-white border-green-600 peer-checked:block peer-focus-visible:ring-2 peer-focus-visible:ring-green-400">Ada</span>
                        </span>
                    </label>
                @endforeach
                <div>
                    <label for="facility_notes" class="block text-xs text-gray-500 mb-1">Keterangan promosi / POSM (opsional)</label>
                    <textarea id="facility_notes" name="facility_notes" rows="2" maxlength="1000" class="w-full border rounded px-3 py-2 text-sm" placeholder="Mis. banner promo terpasang di depan, rak display ada">{{ old('facility_notes') }}</textarea>
                </div>
            </fieldset>
        @endif

        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <p id="lokasi-status" class="text-xs text-gray-500">Lokasi akan diambil otomatis saat Check-in.</p>

        <button type="submit" class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm">Check-in</button>
        <a href="{{ route('sales.visits.index') }}" class="block text-center text-sm text-gray-500">Batal</a>
    </form>
    @endif

    <script>
        // Keterangan/alasan menjadi WAJIB hanya saat kondisi "Toko Tutup" atau "Kendala Lain";
        // placeholder menyesuaikan kondisi. Blok Promosi/POSM selalu tampil.
        (function () {
            const notes = document.getElementById('notes');
            const star = document.getElementById('notes-star');
            const hint = document.getElementById('notes-hint');
            if (! notes) return;

            const placeholders = {
                normal: 'Mis. toko buka, pemilik ada, stok rak terlihat',
                closed: 'Mis. toko tutup, pemilik sedang keluar kota',
                other: 'Jelaskan kendala di lapangan (renovasi, pemilik tidak ada, dll)'
            };

            function sync() {
                const checked = document.querySelector('input[name="condition"]:checked');
                const value = checked ? checked.value : null;
                const needsReason = value !== null && value !== 'normal';

                notes.required = needsReason;
                star.classList.toggle('hidden', ! needsReason);
                hint.textContent = needsReason
                    ? 'Wajib diisi: jelaskan alasan/kondisi di lokasi.'
                    : 'Opsional bila toko normal. Wajib diisi bila toko tutup atau ada kendala.';
                if (value && placeholders[value]) notes.placeholder = placeholders[value];
            }

            document.querySelectorAll('input[name="condition"]').forEach(function (r) {
                r.addEventListener('change', sync);
            });
            sync();
        })();

        function isiLokasi(e) {
            e.preventDefault();
            const form = e.target;
            const status = document.getElementById('lokasi-status');
            const done = () => form.submit();

            // PERBAIKAN: getCurrentLocation() sinkron (JSON string {available,...}),
            // requestCurrentLocation() ASYNC - hasil lewat window.onNativeLocationResult(json).
            const bridge = window.Android || window.SalesNative;
            if (bridge) {
                status.textContent = 'Mengambil lokasi dari GPS Native...';

                if (bridge.getCurrentLocation) {
                    try {
                        const loc = JSON.parse(bridge.getCurrentLocation());
                        if (loc.available) {
                            document.getElementById('latitude').value = loc.latitude;
                            document.getElementById('longitude').value = loc.longitude;
                            return done();
                        }
                    } catch (err) { /* lanjut ke requestCurrentLocation() di bawah */ }
                }

                if (bridge.requestCurrentLocation) {
                    window.onNativeLocationResult = function (loc) {
                        window.onNativeLocationResult = null;
                        if (loc && loc.available) {
                            document.getElementById('latitude').value = loc.latitude;
                            document.getElementById('longitude').value = loc.longitude;
                        } else {
                            status.textContent = 'Gagal mengambil lokasi Native, mengirim tanpa koordinat.';
                        }
                        done();
                    };
                    bridge.requestCurrentLocation();
                    setTimeout(function () {
                        if (window.onNativeLocationResult) {
                            window.onNativeLocationResult = null;
                            status.textContent = 'Timeout GPS Native, mengirim tanpa koordinat.';
                            done();
                        }
                    }, 12000);
                    return false;
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
