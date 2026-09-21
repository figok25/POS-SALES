<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Sales</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 pb-16">
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="px-4 py-3 flex items-center justify-between">
            <span class="font-semibold text-gray-800">{{ config('app.name', 'POS & Sales') }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600">Logout</button>
            </form>
        </div>
        @isset($header)
            <div class="px-4 pb-2 text-sm text-gray-500">{{ $header }}</div>
        @endisset
    </header>

    <x-sales-task-banner />

    <main class="p-4 max-w-md mx-auto">
        {{ $slot }}
    </main>

    {{-- Bottom nav mobile-friendly, struktur sesuai Blueprint #48 Sales Navigation --}}
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t flex justify-around text-xs text-gray-600 py-2">
        <a href="{{ route('sales.dashboard') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('sales.dashboard') ? 'text-indigo-600 font-semibold' : '' }}">
            <span>Dashboard</span>
        </a>
        <a href="{{ route('sales.visits.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('sales.visits.*') ? 'text-indigo-600 font-semibold' : '' }}">
            <span>Kunjungan</span>
        </a>
        <a href="{{ route('sales.transactions.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('sales.transactions.*') ? 'text-indigo-600 font-semibold' : '' }}">
            <span>Transaksi</span>
        </a>
        <a href="{{ route('sales.stock.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('sales.stock.*') ? 'text-indigo-600 font-semibold' : '' }}">
            <span>Stock</span>
        </a>
        <a href="{{ route('sales.map.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('sales.map.*') ? 'text-indigo-600 font-semibold' : '' }}">
            <span>Peta</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('profile.edit') ? 'text-indigo-600 font-semibold' : '' }}">
            <span>Profile</span>
        </a>
    </nav>

    @stack('scripts')

    {{--
        PERBAIKAN AUDIT #2/#7 (P0): kirim Sanctum token ke Android SETIAP kali
        WebView memuat halaman Sales, supaya Retrofit native (background
        service) selalu punya Authorization: Bearer <token> yang valid.
        Endpoint ini aman dipanggil berkali-kali (rotasi token 'sales-app').
        Di browser biasa (tanpa bridge Android), fetch ini tidak berdampak apa-apa.
    --}}
    <script>
        (function () {
            var bridge = window.Android || window.SalesNative;
            if (!bridge || typeof bridge.setAuthToken !== 'function') return;

            fetch('{{ route('sales.native-token') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (json.success && json.data && json.data.token) {
                        bridge.setAuthToken(json.data.token);
                    }
                })
                .catch(function () {
                    // Diam-diam gagal - WebView tetap jalan pakai session biasa,
                    // hanya background native sync yang akan menunggu retry berikutnya.
                });
        })();
    </script>
</body>
</html>
