<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Sales</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Stylesheet statis shell Sales App (topbar/bottom nav/komponen). Sengaja
         memakai palet & bahasa desain yang sama dengan public/css/admin.css. --}}
    <link rel="stylesheet" href="{{ asset('css/sales.css') }}?v={{ @filemtime(public_path('css/sales.css')) }}">

    @stack('styles')
</head>
<body class="sls-body">
@php
    $userName = auth()->user()->name ?? 'Sales';
    $userInitial = mb_strtoupper(mb_substr($userName, 0, 1));

    // Struktur ikon bottom nav: [route, label, svg path (inline)]
    $navItems = [
        ['sales.dashboard', 'Beranda', 'home'],
        ['sales.visits.index', 'Kunjungan', 'pin'],
        ['sales.transactions.index', 'Transaksi', 'receipt'],
        ['sales.stock.index', 'Stock', 'box'],
        ['sales.map.index', 'Peta', 'map'],
    ];
@endphp

<header class="sls-topbar">
    <div class="sls-topbar-row">
        <div class="sls-brand">
            <a href="{{ route('profile.edit') }}" class="sls-brand-mark" aria-label="Buka profil">{{ $userInitial }}</a>
            <span>
                <span class="sls-brand-name">{{ $userName }}</span>
                <span class="sls-brand-role">Sales App</span>
            </span>
        </div>

        <h1 class="sls-title">@isset($header){{ $header }}@endisset</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sls-logout-btn" aria-label="Logout" title="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </button>
        </form>
    </div>

    <x-sales-task-banner />
</header>

<main class="sls-content">
    {{ $slot }}
</main>

{{-- Bottom nav mobile-friendly, struktur sesuai Blueprint #48 Sales Navigation --}}
<nav class="sls-bottomnav" aria-label="Menu utama Sales App">
    @foreach ($navItems as [$routeName, $label, $icon])
        @php $isActive = request()->routeIs($routeName === 'sales.dashboard' ? $routeName : preg_replace('/\.index$/', '.*', $routeName)); @endphp
        <a href="{{ route($routeName) }}" class="sls-nav-item {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="page" @endif>
            @switch($icon)
                @case('home')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg>
                    @break
                @case('pin')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.3"/></svg>
                    @break
                @case('receipt')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-2.5-1.5L13 21l-2.5-1.5L8 21l-2-1.2V3z"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="9" y1="12" x2="15" y2="12"/></svg>
                    @break
                @case('box')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8 12 3 3 8l9 5 9-5z"/><path d="M3 8v9l9 5 9-5V8"/><line x1="12" y1="13" x2="12" y2="22"/></svg>
                    @break
                @case('map')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    @break
                @case('user')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-1a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v1"/><circle cx="12" cy="7" r="4"/></svg>
                    @break
            @endswitch
            <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>

@stack('scripts')

{{-- Tracking otomatis: start saat Admin Apply/Release, stop saat Return Stock --}}
@include('sales._tracking-autostart')

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
