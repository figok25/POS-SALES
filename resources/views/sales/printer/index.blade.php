<x-sales-layout>
    <x-slot name="header">Pengaturan Printer</x-slot>

    <div class="bg-white rounded-lg shadow p-4 text-sm mb-4">
        <div class="flex justify-between items-center mb-1">
            <span class="text-gray-500">Status</span>
            <span id="printer-status" class="font-medium text-right">Memeriksa...</span>
        </div>
        <p class="text-xs text-gray-400">
            Sambungkan sekali di sini, lalu tombol "Cetak" di halaman Transaksi &amp; Stock
            akan otomatis memakai printer yang sama tanpa perlu pairing ulang.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-4 text-sm mb-4 space-y-3">
        <button type="button" id="btn-connect" class="w-full bg-indigo-600 text-white rounded-lg py-2.5">
            Hubungkan / Ganti Printer
        </button>
        <button type="button" id="btn-test" class="w-full bg-gray-100 text-gray-800 rounded-lg py-2.5">
            Cetak Test
        </button>
        <button type="button" id="btn-forget" class="w-full text-red-600 text-xs py-1">
            Lupakan Printer Tersimpan
        </button>
    </div>

    {{-- Panel pemilih printer: HANYA tampil di aplikasi Android (jalur native). --}}
    <div id="native-panel" class="hidden bg-white rounded-lg shadow p-4 text-sm mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="font-medium text-gray-700">Pilih Printer (sudah di-pair)</p>
            <button type="button" id="btn-native-refresh" class="text-xs text-indigo-600">Muat ulang</button>
        </div>
        <div id="native-devices" class="space-y-2 mb-3"></div>
        <p id="native-empty" class="hidden text-xs text-gray-500 mb-3"></p>
        <button type="button" id="btn-native-settings" class="w-full border border-gray-300 text-gray-700 rounded-lg py-2 text-xs">
            Buka Pengaturan Bluetooth HP (untuk pairing printer baru)
        </button>
        <p class="text-xs text-gray-400 mt-2">
            Belum muncul di daftar? Nyalakan printer, buka Pengaturan Bluetooth HP, pasangkan printer
            (PIN biasanya <strong>0000</strong> atau <strong>1234</strong>), lalu kembali ke sini dan tekan "Muat ulang".
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-4 text-sm mb-4">
        <p class="text-gray-500 mb-2">Lebar Kertas</p>
        <div class="flex gap-2">
            <button type="button" data-width="32" class="btn-width flex-1 border rounded-lg py-2">58mm (32 kolom)</button>
            <button type="button" data-width="48" class="btn-width flex-1 border rounded-lg py-2">80mm (48 kolom)</button>
        </div>
        <p class="text-xs text-gray-400 mt-2">Printer BP ECO58 = kertas 58mm (32 kolom).</p>
    </div>

    <div id="printer-message" class="text-sm mb-4"></div>

    <div id="note-native" class="hidden bg-blue-50 text-blue-800 rounded-lg p-3 text-xs leading-relaxed">
        <p class="font-semibold mb-1">Cetak lewat Aplikasi Sales App</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Mendukung printer thermal <strong>Bluetooth Classic (SPP)</strong> seperti BP ECO58 &mdash; tidak perlu HTTPS.</li>
            <li>Printer harus sudah <strong>di-pair</strong> di Pengaturan Bluetooth HP, menyala, dan berada dekat HP saat mencetak.</li>
            <li>Pastikan printer tidak sedang tersambung ke HP/aplikasi lain (printer hanya menerima satu koneksi).</li>
        </ul>
    </div>

    <div id="note-web" class="hidden bg-yellow-50 text-yellow-800 rounded-lg p-3 text-xs leading-relaxed">
        <p class="font-semibold mb-1">Catatan Kompatibilitas</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Untuk printer thermal seperti <strong>BP ECO58</strong>, buka halaman ini lewat aplikasi <strong>Sales App</strong> (APK terbaru) &mdash; printer jenis ini memakai Bluetooth Classic yang tidak bisa dijangkau browser.</li>
            <li>Di browser, fitur ini memakai <strong>Web Bluetooth</strong> dan hanya mendukung printer <strong>Bluetooth Low Energy (BLE)</strong>.</li>
            <li>Di browser harus dibuka lewat alamat <strong>HTTPS</strong>, hanya di Chrome/Chromium (Android/Desktop), tidak di Safari/iOS.</li>
        </ul>
    </div>

    @include('sales.printer._script')
    <script>
        (function () {
            var TP = window.ThermalPrinter;
            var statusEl = document.getElementById('printer-status');
            var msgEl = document.getElementById('printer-message');
            var panelEl = document.getElementById('native-panel');
            var devicesEl = document.getElementById('native-devices');
            var emptyEl = document.getElementById('native-empty');
            var useNativePicker = TP.usesNativeBridge() && TP.native.supportsPrinterApi();

            var testPayload = @json([
                'company_name' => $sales->branch?->company?->name,
                'sales_name' => $sales->name,
            ]);

            function showMessage(text, isError) {
                msgEl.textContent = text;
                msgEl.className = 'text-sm mb-4 ' + (isError ? 'text-red-600' : 'text-green-700');
            }

            function refreshStatus() {
                statusEl.textContent = TP.statusLabel();
                var width = TP.getWidth();
                document.querySelectorAll('.btn-width').forEach(function (btn) {
                    var active = parseInt(btn.dataset.width, 10) === width;
                    btn.className = 'btn-width flex-1 border rounded-lg py-2 ' + (active ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700');
                });
            }

            // ---------- Jalur native (aplikasi Android) ----------

            function renderNativeDevices() {
                var info = TP.native.listPaired();
                var selected = TP.native.getSelected();
                devicesEl.innerHTML = '';
                emptyEl.classList.add('hidden');

                if (!info.available) {
                    emptyEl.textContent = 'HP ini tidak mendukung Bluetooth.';
                    emptyEl.classList.remove('hidden');
                    return;
                }
                if (!info.permission) {
                    emptyEl.textContent = 'Izin Bluetooth belum diberikan. Tekan "Hubungkan / Ganti Printer" lalu izinkan.';
                    emptyEl.classList.remove('hidden');
                    return;
                }
                if (!info.enabled) {
                    emptyEl.textContent = 'Bluetooth HP sedang mati. Aktifkan Bluetooth, lalu tekan "Muat ulang".';
                    emptyEl.classList.remove('hidden');
                    return;
                }
                if (!info.devices.length) {
                    emptyEl.textContent = 'Belum ada perangkat Bluetooth yang di-pair. Pasangkan printer dulu lewat tombol di bawah.';
                    emptyEl.classList.remove('hidden');
                    return;
                }

                info.devices.forEach(function (dev) {
                    var isSelected = !!(selected && selected.address === dev.address);
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full flex items-center justify-between border rounded-lg px-3 py-2 text-left ' +
                        (isSelected ? 'bg-indigo-50 border-indigo-500' : 'border-gray-300');

                    var left = document.createElement('span');
                    var nameEl = document.createElement('span');
                    nameEl.className = 'block font-medium text-gray-800';
                    nameEl.textContent = dev.name;
                    var addrEl = document.createElement('span');
                    addrEl.className = 'block text-xs text-gray-400';
                    addrEl.textContent = dev.address + (dev.is_printer ? ' - kemungkinan printer' : '');
                    left.appendChild(nameEl);
                    left.appendChild(addrEl);

                    var right = document.createElement('span');
                    right.className = 'text-xs ' + (isSelected ? 'text-indigo-600 font-semibold' : 'text-gray-400');
                    right.textContent = isSelected ? 'Dipilih' : 'Pilih';

                    btn.appendChild(left);
                    btn.appendChild(right);
                    btn.addEventListener('click', function () {
                        if (TP.native.select(dev.address, dev.name)) {
                            showMessage('Printer "' + dev.name + '" dipilih. Tekan "Cetak Test" untuk mencoba.');
                        } else {
                            showMessage('Gagal menyimpan printer pilihan.', true);
                        }
                        renderNativeDevices();
                        refreshStatus();
                    });
                    devicesEl.appendChild(btn);
                });
            }

            async function openNativePicker() {
                var granted = await TP.native.requestPermission();
                if (!granted) {
                    showMessage('Izin Bluetooth ditolak. Izinkan di Pengaturan aplikasi agar bisa memilih printer.', true);
                    return;
                }
                panelEl.classList.remove('hidden');
                renderNativeDevices();
                showMessage('Pilih printer dari daftar di bawah.');
            }

            // ---------- Event ----------

            document.getElementById('btn-connect').addEventListener('click', function () {
                if (useNativePicker) {
                    openNativePicker();
                    return;
                }
                showMessage('Membuka daftar perangkat Bluetooth...');
                TP.connect().then(function () {
                    showMessage('Printer berhasil terhubung.');
                    refreshStatus();
                }).catch(function (err) {
                    showMessage(err.message || 'Gagal terhubung ke printer.', true);
                });
            });

            document.getElementById('btn-test').addEventListener('click', function () {
                testPayload.date = new Date().toLocaleString('id-ID');
                showMessage('Mengirim test print...');
                TP.printTest(testPayload).then(function () {
                    showMessage('Test print terkirim. Periksa hasil di printer.');
                    refreshStatus();
                }).catch(function (err) {
                    showMessage(err.message || 'Gagal mencetak.', true);
                });
            });

            document.getElementById('btn-forget').addEventListener('click', function () {
                TP.forget();
                showMessage('Printer tersimpan sudah dilupakan.');
                if (useNativePicker && !panelEl.classList.contains('hidden')) renderNativeDevices();
                refreshStatus();
            });

            document.getElementById('btn-native-refresh').addEventListener('click', function () {
                renderNativeDevices();
            });

            document.getElementById('btn-native-settings').addEventListener('click', function () {
                TP.native.openBluetoothSettings();
            });

            document.querySelectorAll('.btn-width').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    TP.setWidth(btn.dataset.width);
                    refreshStatus();
                });
            });

            // Kembali dari Pengaturan Bluetooth (pairing) -> daftar otomatis dimuat ulang.
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible' && useNativePicker && !panelEl.classList.contains('hidden')) {
                    renderNativeDevices();
                }
            });

            // ---------- Init ----------

            document.getElementById(useNativePicker ? 'note-native' : 'note-web').classList.remove('hidden');

            if (useNativePicker) {
                // Tampilkan langsung kalau belum ada printer terpilih, supaya
                // Sales tidak bingung harus menekan apa dulu.
                if (!TP.native.getSelected() && TP.native.hasPermission()) {
                    panelEl.classList.remove('hidden');
                    renderNativeDevices();
                }
                refreshStatus();
            } else {
                // Coba reconnect diam-diam kalau browser mendukung (Web Bluetooth).
                TP.tryReconnectSilently().finally(refreshStatus);
            }
        })();
    </script>
</x-sales-layout>
