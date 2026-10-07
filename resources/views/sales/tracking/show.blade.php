<x-sales-layout>
    <x-slot name="header">Tracking</x-slot>

    {{--
        Tracking berjalan OTOMATIS: menyala saat Admin Apply/Release Sales
        Task dan mati saat Sales melakukan Return Stock. Halaman ini hanya
        menampilkan status; logika perangkat ada di
        resources/views/sales/_tracking-autostart.blade.php.
    --}}
    <div x-data="salesTrackingStatus()" x-init="init()" class="space-y-4">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-4xl mb-2" x-text="active ? '🟢' : '⚪'"></p>
            <p class="font-medium" x-text="active ? 'Tracking Aktif' : 'Tracking Tidak Aktif'"></p>
            <p class="text-xs text-gray-500 mt-1" x-show="lastSentAt">
                Terakhir dikirim: <span x-text="lastSentAt"></span>
            </p>
            <p class="text-xs text-gray-500 mt-2" x-show="!active">
                Tracking akan menyala otomatis saat Admin merilis tugas Anda.
            </p>
            <p class="text-xs text-gray-500 mt-2" x-show="active">
                Tidak perlu menekan apa pun. Tracking berhenti otomatis setelah Anda melakukan Return Stock.
            </p>
        </div>

        <template x-if="errorMessage">
            <div class="p-3 bg-red-100 text-red-800 rounded text-sm space-y-2">
                <p x-text="errorMessage"></p>
                <button type="button" @click="retry()" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs">Coba lagi</button>
            </div>
        </template>

        <template x-if="active && signalLost">
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-800">
                ⚠️ Sinyal GPS hilang. Pastikan Anda berada di area terbuka; tracking akan pulih otomatis begitu sinyal kembali.
            </div>
        </template>

        <template x-if="!isNative">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
                ⚠️ Anda membuka halaman ini lewat browser biasa. Tracking di sini hanya <strong>fallback</strong>
                yang cuma berjalan selagi halaman Sales App terbuka &amp; layar aktif. Gunakan Sales APK
                untuk tracking latar belakang yang sesungguhnya (layar mati/aplikasi pindah halaman).
            </div>
        </template>
        <template x-if="isNative">
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-green-800">
                📍 Mode Aplikasi Native — GPS dan tracking latar belakang ditangani oleh Sales APK.
            </div>
        </template>
    </div>

    @include('sales._break-button')

    @push('scripts')
    <script>
        function salesTrackingStatus() {
            return {
                active: false,
                signalLost: false,
                errorMessage: null,
                lastSentAt: null,

                get isNative() {
                    return !!(window.Android || window.SalesNative);
                },

                apply(s) {
                    this.active = !!s.active;
                    this.signalLost = !!s.signalLost;
                    this.errorMessage = s.error || null;
                    this.lastSentAt = s.lastSentAt || null;
                },

                init() {
                    window.addEventListener('sales-tracking-state', (e) => this.apply(e.detail));
                    if (window.salesAutoTracking) {
                        this.apply(window.salesAutoTracking.state);
                        window.salesAutoTracking.sync();
                    }
                },

                retry() {
                    if (window.salesAutoTracking) window.salesAutoTracking.retry();
                },
            };
        }
    </script>
    @endpush
</x-sales-layout>
