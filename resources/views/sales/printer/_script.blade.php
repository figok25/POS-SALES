{{--
    ThermalPrinter engine (Fase 9 - Bluetooth Thermal Print, Blueprint #12.7).

    Di-@include di setiap halaman yang butuh cetak (Printer Settings,
    Transaksi > Show, Sales Stock > Index) supaya window.ThermalPrinter
    selalu tersedia sebelum script halaman memanggilnya.

    Cara kerja koneksi (2 jalur, otomatis pilih salah satu):
    1. Native bridge Android (window.Android / window.SalesNative) -- kalau
       WebView APK punya method `printEscPosBase64(base64String)`, jalur ini
       dipakai. Ini SATU-SATUNYA jalur yang jalan untuk printer thermal
       Bluetooth CLASSIC/SPP (mis. BP ECO58) dan di dalam WebView Android
       (yang tidak punya navigator.bluetooth). Sisi Android: lihat
       SalesApp/.../printer/BluetoothPrinterManager.kt & WebViewBridge.kt
       (printEscPosBase64, getPairedPrinters, selectPrinter, dst.). Printer
       di-PAIR sekali lewat Pengaturan Bluetooth Android, lalu dipilih di
       halaman Atur Printer. Hasil cetak dikembalikan lewat
       window.onNativePrintResult({success, message}).
    2. Web Bluetooth API (navigator.bluetooth) -- jalur fallback yang
       jalan langsung dari browser/WebView tanpa perlu native. CATATAN
       PENTING (lihat juga app/Http/Controllers/Sales/PrinterController.php):
       - Web Bluetooth HANYA bisa ke printer Bluetooth LOW ENERGY (BLE).
         Printer yang cuma punya mode Bluetooth Classic/SPP (banyak printer
         mini murah) TIDAK akan muncul di daftar & tidak akan bisa dipakai
         lewat jalur ini -- itu harus lewat jalur native bridge di atas.
       - Web Bluetooth WAJIB secure context (HTTPS, atau localhost saat
         dev). Sama seperti kasus Geolocation API di file lain pada
         project ini, kalau app dibuka lewat HTTP biasa di WebView, jalur
         ini tidak akan berfungsi.
       - Baru didukung oleh browser berbasis Chromium (Chrome/Edge di
         Android & Desktop). TIDAK didukung Safari/iOS.
--}}
<script>
window.ThermalPrinter = window.ThermalPrinter || (function () {
    'use strict';

    var STORAGE_DEVICE_ID = 'thermal_printer_device_id';
    var STORAGE_DEVICE_NAME = 'thermal_printer_device_name';
    var STORAGE_WIDTH = 'thermal_printer_width';
    var NATIVE_METHOD = 'printEscPosBase64';

    // Kandidat service UUID BLE yang umum dipakai printer thermal mini
    // generik 58mm/80mm (mis. keluarga Zjiang/Goojprt/HM-10 UART).
    // Printer lain bisa saja pakai UUID berbeda -- lihat catatan di atas.
    var KNOWN_SERVICES = [
        '000018f0-0000-1000-8000-00805f9b34fb',
        '49535343-fe7d-4ae5-8fa9-9fafd205e455',
        '0000ff00-0000-1000-8000-00805f9b34fb',
        '6e400001-b5a3-f393-e0a9-e50e24dcca9e',
    ];

    var state = { device: null, characteristic: null };

    // ---------- Util dasar ----------

    function getWidth() {
        var w = parseInt(localStorage.getItem(STORAGE_WIDTH) || '32', 10);
        return w === 48 ? 48 : 32;
    }

    function setWidth(w) {
        localStorage.setItem(STORAGE_WIDTH, parseInt(w, 10) === 48 ? '48' : '32');
    }

    function getSavedDeviceName() {
        return localStorage.getItem(STORAGE_DEVICE_NAME) || null;
    }

    function rememberDevice(device) {
        try {
            localStorage.setItem(STORAGE_DEVICE_ID, device.id);
            localStorage.setItem(STORAGE_DEVICE_NAME, device.name || 'Printer Bluetooth');
        } catch (e) { /* mode private / storage penuh - abaikan */ }
    }

    function forgetDevice() {
        var bridge = getNativeBridge();
        if (bridge && typeof bridge.clearSelectedPrinter === 'function') {
            bridge.clearSelectedPrinter();
        }
        localStorage.removeItem(STORAGE_DEVICE_ID);
        localStorage.removeItem(STORAGE_DEVICE_NAME);
        if (state.device && state.device.gatt && state.device.gatt.connected) {
            try { state.device.gatt.disconnect(); } catch (e) { /* noop */ }
        }
        state.device = null;
        state.characteristic = null;
    }

    function getNativeBridge() {
        var bridge = window.Android || window.SalesNative;
        return (bridge && typeof bridge[NATIVE_METHOD] === 'function') ? bridge : null;
    }

    function usesNativeBridge() {
        return !!getNativeBridge();
    }

    // Ada bridge Android (kita di dalam aplikasi Sales App) TAPI belum punya
    // method cetak -> APK-nya masih versi lama, perlu di-install ulang.
    function isInAppWithoutPrintSupport() {
        var bridge = window.Android || window.SalesNative;
        return !!(bridge && typeof bridge[NATIVE_METHOD] !== 'function');
    }

    // APK versi terbaru punya API pilih-printer + callback hasil cetak.
    // APK yang HANYA punya printEscPosBase64 (tanpa API ini) tetap dipakai
    // dengan mode kirim-lalu-lupa, seperti kontrak awal.
    function nativeSupportsPrinterApi() {
        var bridge = getNativeBridge();
        return !!(bridge && typeof bridge.getSelectedPrinter === 'function');
    }

    function nativeJson(method, fallback) {
        var bridge = getNativeBridge();
        if (!bridge || typeof bridge[method] !== 'function') return fallback;
        try {
            return JSON.parse(bridge[method]());
        } catch (e) {
            return fallback;
        }
    }

    function getNativeSelectedPrinter() {
        if (!nativeSupportsPrinterApi()) return null;
        var p = nativeJson('getSelectedPrinter', {});
        return (p && p.address) ? p : null;
    }

    function bytesToBase64(bytes) {
        var binary = '';
        for (var i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary);
    }

    // Codepage default printer thermal umumnya CP437/1252 (single-byte),
    // jadi hanya karakter ASCII dasar (32-126) yang dijamin tercetak benar.
    // Tanda baca "pintar" umum diganti padanan ASCII-nya; sisanya jadi '?'.
    var CHAR_MAP = {
        '\u2018': "'", '\u2019': "'", '\u201c': '"', '\u201d': '"',
        '\u2013': '-', '\u2014': '-', '\u2026': '...',
    };
    function toPrinterBytes(str) {
    str = String(str === null || str === undefined ? '' : str);

    var out = [];

    for (var i = 0; i < str.length; i++) {
        var ch = str[i];

        // Pertahankan karakter kontrol yang memang dibutuhkan
        // untuk pindah baris pada printer thermal.
        if (ch === '\n') {
            out.push(0x0A);
            continue;
        }

        if (ch === '\r') {
            out.push(0x0D);
            continue;
        }

        if (ch === '\t') {
            out.push(0x09);
            continue;
        }

        // Konversi tanda baca Unicode ke ASCII.
        if (CHAR_MAP[ch]) {
            var mapped = CHAR_MAP[ch];

            for (var j = 0; j < mapped.length; j++) {
                out.push(mapped.charCodeAt(j));
            }

            continue;
        }

        var code = str.charCodeAt(i);

        // ASCII standar.
        if (code >= 32 && code <= 126) {
            out.push(code);
        } else {
            // Karakter yang tidak didukung printer.
            out.push(63); // ?
        }
    }

    return out;
}

    function money(n) {
        n = Math.round(Number(n) || 0);
        return n.toLocaleString('id-ID');
    }

    function qty(n) {
        n = Number(n) || 0;
        var s = n.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
        return s === '' ? '0' : s;
    }

    // ---------- ESC/POS builder ----------

    function Builder(width) {
        this.width = width || getWidth();
        this.bytes = [];
    }
    Builder.prototype.raw = function () {
        for (var i = 0; i < arguments.length; i++) this.bytes.push(arguments[i]);
        return this;
    };
    Builder.prototype.init = function () { return this.raw(0x1b, 0x40); }; // ESC @
    Builder.prototype.align = function (mode) { // 'left' | 'center' | 'right'
        var n = mode === 'center' ? 1 : (mode === 'right' ? 2 : 0);
        return this.raw(0x1b, 0x61, n); // ESC a n
    };
    Builder.prototype.bold = function (on) { return this.raw(0x1b, 0x45, on ? 1 : 0); }; // ESC E n
    Builder.prototype.big = function (on) { return this.raw(0x1d, 0x21, on ? 0x11 : 0x00); }; // GS ! n
    Builder.prototype.text = function (str) {
        var b = toPrinterBytes(str);
        for (var i = 0; i < b.length; i++) this.bytes.push(b[i]);
        return this;
    };
    Builder.prototype.line = function (str) { return this.text((str || '') + '\n'); };
    Builder.prototype.feed = function (n) {
        n = n || 1;
        for (var i = 0; i < n; i++) this.bytes.push(0x0a);
        return this;
    };
    Builder.prototype.cut = function () { return this.feed(4).raw(0x1d, 0x56, 0x01); }; // feed + GS V 1 (printer tanpa cutter seperti BP ECO58 mengabaikan GS V; feed 4 baris memastikan struk keluar melewati sobekan)
    Builder.prototype.divider = function (ch) {
        return this.line(new Array(this.width + 1).join(ch || '-'));
    };
    // Dua kolom rata kiri/kanan yang muat dalam this.width karakter.
    // Kalau teks kiri kepanjangan, dipindah ke baris sendiri dulu.
    Builder.prototype.twoCol = function (left, right) {
        left = String(left === null || left === undefined ? '' : left);
        right = String(right === null || right === undefined ? '' : right);
        var space = this.width - left.length - right.length;
        if (space >= 1) return this.line(left + new Array(space + 1).join(' ') + right);
        this.line(left);
        var pad = this.width - right.length;
        return this.line((pad > 0 ? new Array(pad + 1).join(' ') : '') + right);
    };
    // Bungkus teks panjang (mis. nama produk) jadi beberapa baris.
    Builder.prototype.wrap = function (text) {
        text = String(text === null || text === undefined ? '' : text);
        var width = this.width;
        var words = text.split(' ');
        var line = '';
        for (var i = 0; i < words.length; i++) {
            var w = words[i];
            var candidate = line ? (line + ' ' + w) : w;
            if (candidate.length > width) {
                if (line) this.line(line);
                while (w.length > width) {
                    this.line(w.slice(0, width));
                    w = w.slice(width);
                }
                line = w;
            } else {
                line = candidate;
            }
        }
        if (line) this.line(line);
        return this;
    };
    Builder.prototype.toBytes = function () { return new Uint8Array(this.bytes); };

    // ---------- Koneksi Web Bluetooth ----------

    function isWebBluetoothSupported() {
        return !!(navigator.bluetooth);
    }

    async function findWritableCharacteristic(server) {
        var services = await server.getPrimaryServices();
        for (var i = 0; i < services.length; i++) {
            var chars;
            try {
                chars = await services[i].getCharacteristics();
            } catch (e) {
                continue;
            }
            for (var j = 0; j < chars.length; j++) {
                var c = chars[j];
                if (c.properties.write || c.properties.writeWithoutResponse) return c;
            }
        }
        return null;
    }

    async function attachDevice(device) {
        state.device = device;
        device.addEventListener('gattserverdisconnected', function () {
            state.characteristic = null;
        });
        var server = await device.gatt.connect();
        var characteristic = await findWritableCharacteristic(server);
        if (!characteristic) {
            throw new Error('Printer terhubung tapi tidak ditemukan jalur tulis (characteristic) yang cocok. Coba printer lain, atau printer ini mungkin hanya mendukung Bluetooth Classic (bukan BLE).');
        }
        state.characteristic = characteristic;
        rememberDevice(device);
        return true;
    }

    // Coba sambung diam-diam ke printer yang sudah pernah dipasangkan
    // (Chrome menyimpan izin Bluetooth per-device), TANPA memunculkan
    // dialog pilih perangkat. Kalau tidak didukung/gagal -> false, caller
    // jatuh ke requestAndConnect() yang butuh tap tombol pengguna.
    async function tryReconnectSilently() {
        if (!isWebBluetoothSupported() || typeof navigator.bluetooth.getDevices !== 'function') {
            return false;
        }
        var savedId = localStorage.getItem(STORAGE_DEVICE_ID);
        if (!savedId) return false;
        try {
            var devices = await navigator.bluetooth.getDevices();
            var match = null;
            for (var i = 0; i < devices.length; i++) {
                if (devices[i].id === savedId) { match = devices[i]; break; }
            }
            if (!match) return false;
            await attachDevice(match);
            return true;
        } catch (e) {
            return false;
        }
    }

    // Munculkan dialog pilih perangkat Bluetooth bawaan browser. WAJIB
    // dipanggil langsung dari event klik tombol (user gesture) - tidak
    // bisa dijalankan otomatis lewat kode.
    async function requestAndConnect() {
        if (!isWebBluetoothSupported()) {
            if (isInAppWithoutPrintSupport()) {
                throw new Error('Versi aplikasi Sales App di HP ini belum mendukung cetak. Install APK versi terbaru, lalu buka lagi halaman ini.');
            }
            throw new Error('Web Bluetooth tidak tersedia di browser ini. Untuk mencetak ke printer thermal (mis. BP ECO58), buka lewat aplikasi Sales App (APK terbaru).');
        }
        var device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: KNOWN_SERVICES,
        });
        await attachDevice(device);
        return true;
    }

    async function ensureConnected() {
        if (usesNativeBridge()) {
            // Android 12+: izin Bluetooth diminta otomatis di sini kalau Sales
            // langsung menekan Cetak tanpa lewat halaman Atur Printer dulu.
            if (nativeSupportsPrinterApi() && !native.hasPermission()) {
                var granted = await native.requestPermission();
                if (!granted) {
                    throw new Error('Izin Bluetooth ditolak. Izinkan "Perangkat di sekitar" untuk aplikasi ini di Pengaturan HP agar bisa mencetak.');
                }
            }
            // APK terbaru: wajib sudah ada printer yang dipilih di Atur Printer.
            if (nativeSupportsPrinterApi() && !getNativeSelectedPrinter()) {
                throw new Error('Belum ada printer dipilih. Buka "Atur Printer", lalu pilih printer Bluetooth Anda (mis. BP ECO58) yang sudah dipasangkan.');
            }
            return 'native';
        }
        if (state.characteristic && state.device && state.device.gatt.connected) return 'ble';
        var reconnected = await tryReconnectSilently();
        if (reconnected) return 'ble';
        await requestAndConnect();
        return 'ble';
    }

    // Kirim ke native & TUNGGU hasilnya (window.onNativePrintResult) supaya
    // pesan sukses/gagal di halaman akurat. Untuk APK lama yang belum punya
    // callback, langsung dianggap terkirim (perilaku kontrak awal).
    function writeNative(bridge, bytes) {
        if (!nativeSupportsPrinterApi()) {
            bridge[NATIVE_METHOD](bytesToBase64(bytes));
            return Promise.resolve();
        }
        return new Promise(function (resolve, reject) {
            var timer = setTimeout(function () {
                window.onNativePrintResult = null;
                reject(new Error('Printer tidak merespons. Pastikan printer menyala, dekat dengan HP, dan coba lagi.'));
            }, 30000);
            window.onNativePrintResult = function (res) {
                clearTimeout(timer);
                window.onNativePrintResult = null;
                if (res && res.success) resolve();
                else reject(new Error((res && res.message) || 'Gagal mencetak.'));
            };
            bridge[NATIVE_METHOD](bytesToBase64(bytes));
        });
    }

    async function writeBytes(bytes) {
        var bridge = getNativeBridge();
        if (bridge) {
            return writeNative(bridge, bytes);
        }
        if (!state.characteristic) throw new Error('Printer belum terhubung.');
        var chunkSize = 100;
        var useWithoutResponse = !!state.characteristic.properties.writeWithoutResponse;
        for (var i = 0; i < bytes.length; i += chunkSize) {
            var chunk = bytes.slice(i, i + chunkSize);
            if (useWithoutResponse) {
                await state.characteristic.writeValueWithoutResponse(chunk);
            } else {
                await state.characteristic.writeValue(chunk);
            }
            await new Promise(function (r) { setTimeout(r, 15); });
        }
    }

    function statusLabel() {
        if (usesNativeBridge()) {
            if (!nativeSupportsPrinterApi()) return 'Terhubung (lewat aplikasi Android)';
            var selected = getNativeSelectedPrinter();
            return selected
                ? ('Printer: ' + selected.name)
                : 'Belum ada printer dipilih (pilih di bawah)';
        }
        if (isInAppWithoutPrintSupport()) {
            return 'Aplikasi Sales App perlu diperbarui (APK terbaru)';
        }
        if (state.characteristic && state.device && state.device.gatt.connected) {
            return 'Terhubung: ' + (state.device.name || 'Printer Bluetooth');
        }
        var saved = getSavedDeviceName();
        return saved ? ('Belum terhubung (tersimpan: ' + saved + ')') : 'Belum ada printer tersimpan';
    }

    // ---------- Dokumen cetak ----------

    function buildReceipt(payload, width) {
        var b = new Builder(width);
        b.init().align('center').bold(true).big(true).line(payload.company_name || 'POS & Sales').big(false).bold(false);
        if (payload.company_address) b.line(payload.company_address);
        if (payload.company_phone) b.line(payload.company_phone);
        b.align('left').divider();
        b.twoCol('No. Transaksi', payload.code || '-');
        b.twoCol('Tanggal', payload.date || '-');
        b.twoCol('Sales', payload.sales_name || '-');
        // Nama Toko (Customer) sengaja TIDAK pakai twoCol seperti baris lain
        // di atas -- twoCol tidak pernah membungkus/memotong teks di sisi
        // kanan, jadi nama toko yang panjang bisa overflow lebar kertas dan
        // di-wrap paksa oleh firmware printer (bisa putus di tengah kata).
        // Dicetak BOLD di baris sendiri + wrap() (fungsi yang sama dipakai
        // nama produk di bawah) supaya nama toko selalu utuh & jelas
        // terbaca berapa pun panjangnya.
        b.line('Customer:');
        b.bold(true).wrap(payload.customer_name || '-').bold(false);
        b.divider();
        (payload.items || []).forEach(function (item) {
            b.wrap(item.name || '-');
            b.twoCol(qty(item.quantity) + ' x ' + money(item.price), 'Rp ' + money(item.subtotal));
        });
        b.divider();
        b.twoCol('Subtotal', 'Rp ' + money(payload.subtotal));
        if (Number(payload.discount) > 0) b.twoCol('Diskon', 'Rp ' + money(payload.discount));
        if (Number(payload.tax) > 0) b.twoCol('Pajak', 'Rp ' + money(payload.tax));
        b.bold(true).twoCol('TOTAL', 'Rp ' + money(payload.total)).bold(false);
        if (payload.payment_status) {
            b.divider();
            b.twoCol('Status Bayar', payload.payment_status);
            if (payload.outstanding_label) b.twoCol('Outstanding', payload.outstanding_label);
        }
        b.divider();
        b.align('center').line('Terima kasih').feed(1);
        b.cut();
        return b.toBytes();
    }

    function buildStock(payload, width) {
        var b = new Builder(width);
        b.init().align('center').bold(true).line(payload.company_name || 'POS & Sales').bold(false);
        b.line('LAPORAN STOCK SALES');
        b.align('left').divider();
        b.twoCol('Sales', payload.sales_name || '-');
        b.twoCol('Tanggal', payload.date || '-');
        b.divider();
        (payload.items || []).forEach(function (item) {
            b.wrap(item.name || '-');
            b.twoCol('', qty(item.quantity) + ' ' + (item.unit || ''));
        });
        b.divider();
        b.twoCol('Total SKU', String((payload.items || []).length));
        b.divider();
        b.align('center').line('Dicetak dari Sales App').feed(1);
        b.cut();
        return b.toBytes();
    }

    function buildTest(payload, width) {
        var b = new Builder(width);
        b.init().align('center').bold(true).big(true).line('TEST PRINT').big(false).bold(false);
        b.line(payload.company_name || 'POS & Sales');
        b.divider();
        b.align('left');
        b.twoCol('Sales', payload.sales_name || '-');
        b.twoCol('Waktu', payload.date || '-');
        b.twoCol('Lebar kertas', width + ' kolom');
        b.divider('=');
        b.line('Normal - abcdefgh ABCDEFGH 0123456789');
        b.bold(true).line('Bold - abcdefgh ABCDEFGH').bold(false);
        b.twoCol('Kiri', 'Kanan');
        b.divider();
        b.align('center').line('Jika teks di atas rapi & terbaca,').line('printer siap dipakai.').feed(1);
        b.cut();
        return b.toBytes();
    }

    // ---------- API publik ----------

    // Fungsi khusus jalur native (aplikasi Android): izin Bluetooth, daftar
    // printer yang sudah di-pair, memilih printer, dan membuka Pengaturan
    // Bluetooth untuk pairing.
    var native = {
        supportsPrinterApi: nativeSupportsPrinterApi,
        isInAppWithoutPrintSupport: isInAppWithoutPrintSupport,
        hasPermission: function () {
            var bridge = getNativeBridge();
            if (!bridge || typeof bridge.hasBluetoothPermission !== 'function') return true;
            return !!bridge.hasBluetoothPermission();
        },
        // Resolve true/false setelah user menjawab dialog izin Android.
        requestPermission: function () {
            return new Promise(function (resolve) {
                var bridge = getNativeBridge();
                if (!bridge || typeof bridge.requestBluetoothPermission !== 'function') return resolve(true);
                if (native.hasPermission()) return resolve(true);
                window.onNativeBluetoothPermissionResult = function (granted) {
                    window.onNativeBluetoothPermissionResult = null;
                    resolve(!!granted);
                };
                bridge.requestBluetoothPermission();
            });
        },
        // {available, enabled, permission, devices:[{name,address,is_printer}]}
        listPaired: function () {
            return nativeJson('getPairedPrinters', { available: false, enabled: false, permission: false, devices: [] });
        },
        select: function (address, name) {
            var bridge = getNativeBridge();
            if (!bridge || typeof bridge.selectPrinter !== 'function') return false;
            return !!bridge.selectPrinter(address, name || '');
        },
        getSelected: getNativeSelectedPrinter,
        openBluetoothSettings: function () {
            var bridge = getNativeBridge();
            if (bridge && typeof bridge.openBluetoothSettings === 'function') bridge.openBluetoothSettings();
        },
    };

    return {
        native: native,
        isSupported: function () { return isWebBluetoothSupported() || usesNativeBridge(); },
        usesNativeBridge: usesNativeBridge,
        getWidth: getWidth,
        setWidth: setWidth,
        getSavedDeviceName: getSavedDeviceName,
        isConnected: function () {
            if (usesNativeBridge()) return nativeSupportsPrinterApi() ? !!getNativeSelectedPrinter() : true;
            return !!(state.characteristic && state.device && state.device.gatt.connected);
        },
        statusLabel: statusLabel,
        connect: async function () {
            if (usesNativeBridge()) return 'native';
            await requestAndConnect();
            return 'ble';
        },
        tryReconnectSilently: tryReconnectSilently,
        forget: forgetDevice,
        printReceipt: async function (payload) {
            await ensureConnected();
            await writeBytes(buildReceipt(payload, getWidth()));
        },
        printStock: async function (payload) {
            await ensureConnected();
            await writeBytes(buildStock(payload, getWidth()));
        },
        printTest: async function (payload) {
            await ensureConnected();
            await writeBytes(buildTest(payload || {}, getWidth()));
        },
    };
})();
</script>
