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

        <div>
            <label for="notes" class="block text-sm text-gray-600 mb-1">Keterangan Kondisi Outlet <span class="text-red-500">*</span></label>
            <textarea id="notes" name="notes" rows="3" required minlength="3" maxlength="1000" class="w-full border rounded px-3 py-2 text-sm" placeholder="Jelaskan kondisi outlet saat Anda tiba">{{ old('notes') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Wajib diisi di setiap kunjungan, terutama jika toko tutup atau ada kendala.</p>
        </div>

        {{-- Promosi / POSM: hanya diamati (dan wajib) jika outlet normal/buka. --}}
        <fieldset id="promo-section" class="border rounded p-3 space-y-2 hidden" disabled>
            <legend class="text-sm font-medium text-gray-700 px-1">Promosi &amp; POSM Outlet</legend>
            @foreach (['has_promo' => 'Program promosi', 'has_posm' => 'POSM', 'has_banner' => 'Banner'] as $field => $label)
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-gray-600">{{ $label }} <span class="text-red-500">*</span></span>
                    <div class="flex gap-2">
                        @foreach (['1' => 'Ada', '0' => 'Tidak ada'] as $value => $text)
                            <label class="cursor-pointer">
                                <input type="radio" name="{{ $field }}" value="{{ $value }}" class="peer sr-only" required @checked((string) old($field) === (string) $value && old($field) !== null)>
                                <span class="block border rounded px-3 py-1 text-xs text-gray-700 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400">{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div>
                <label for="facility_notes" class="block text-xs text-gray-500 mb-1">Keterangan promosi / POSM / fasilitas lain (opsional)</label>
                <textarea id="facility_notes" name="facility_notes" rows="2" maxlength="1000" class="w-full border rounded px-3 py-2 text-sm" placeholder="Mis. banner promo rokok sudah terpasang, rak display ada">{{ old('facility_notes') }}</textarea>
            </div>
        </fieldset>

        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <p id="lokasi-status" class="text-xs text-gray-500">Lokasi akan diambil otomatis saat Check-in.</p>

        <button type="submit" class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm">Check-in</button>
        <a href="{{ route('sales.visits.index') }}" class="block text-center text-sm text-gray-500">Batal</a>
    </form>
    @endif

    <script>
        // RevisiMinor #5/#6: blok Promosi/POSM hanya muncul & wajib saat kondisi "Normal";
        // placeholder keterangan menyesuaikan kondisi. <fieldset disabled> membuat
        // radio di dalamnya tidak ikut terkirim/divalidasi saat tersembunyi.
        (function () {
            const promo = document.getElementById('promo-section');
            const notes = document.getElementById('notes');
            if (! promo || ! notes) return;

            const hints = {
                normal: 'Mis. toko buka, pemilik ada, stok rak terlihat',
                closed: 'Mis. toko tutup, pemilik sedang keluar kota',
                other: 'Jelaskan kendala di lapangan (renovasi, pemilik tidak ada, dll)'
            };

            function sync() {
                const checked = document.querySelector('input[name="condition"]:checked');
                const value = checked ? checked.value : null;
                const show = value === 'normal';
                promo.disabled = ! show;
                promo.classList.toggle('hidden', ! show);
                if (value && hints[value]) notes.placeholder = hints[value];
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
