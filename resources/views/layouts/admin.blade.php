<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Admin</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Stylesheet statis shell admin (sidebar/topbar/dashboard). Dimuat SETELAH
         Tailwind supaya menang saat specificity sama. ?v= untuk cache-busting. --}}
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">

    @stack('styles')
</head>
<body class="adm-body">
@php
    /*
     * Struktur menu mengikuti Blueprint #47 Admin Navigation.
     * Format item: [label, nama route | null (placeholder), ]
     * Item aktif otomatis ditandai (route .index -> seluruh route se-resource,
     * mis. admin.master.products.* ikut aktif di halaman create/edit/show).
     */
    $menu = [
        ['title' => null, 'items' => [
            ['Dashboard', 'admin.dashboard'],
        ]],
        ['title' => 'Master Data', 'items' => [
            ['Company', 'admin.master.companies.index'],
            ['Branch', 'admin.master.branches.index'],
            ['Warehouse', 'admin.master.warehouses.index'],
            ['Product', 'admin.master.products.index'],
            ['Category', 'admin.master.categories.index'],
            ['Unit', 'admin.master.units.index'],
            ['Price', 'admin.master.prices.index'],
            ['Customer', 'admin.master.customers.index'],
            ['Employee', 'admin.master.employees.index'],
            ['Sales', 'admin.master.sales.index'],
            ['Vehicle', 'admin.master.vehicles.index'],
            ['Supplier', 'admin.master.suppliers.index'],
        ]],
        ['title' => 'Inventory', 'items' => [
            ['Stock', 'admin.inventory.stock.index'],
            ['Stock Movement', 'admin.inventory.movements.index'],
            ['Stock Adjustment', 'admin.inventory.adjustments.index'],
        ]],
        ['title' => 'Distribution', 'items' => [
            ['Permintaan Barang', 'admin.distribution.stock-requests.index'],
            ['BKB Distribusi', 'admin.distribution.bkb.index'],
            ['BTB Distribusi', 'admin.distribution.btb.index'],
            ['Branch Transfer (BKB/BTB Cabang)', 'admin.distribution.branch-transfer.index'],
        ]],
        ['title' => 'Sales', 'items' => [
            ['Sales Management', 'admin.master.sales.index'],
            ['Customer', 'admin.master.customers.index'],
            ['Customer Assignment', 'admin.sales.customer-assignments.index'],
            ['Visit Plan (Rute Kanvas)', 'admin.sales.visit-plans.index'],
            ['Rute Toko per Sales', 'admin.sales.route-map.index'],
            ['Tagging Toko', 'admin.sales.customer-taggings.index'],
            ['Visit', 'admin.sales.visits.index'],
            ['Transaksi Penjualan', 'admin.sales.transactions.index'],
            ['Invoice', 'admin.sales.invoices.index'],
        ]],
        ['title' => 'Finance', 'items' => [
            ['Invoice', 'admin.sales.invoices.index'],
            ['Payment', 'admin.finance.payments.index'],
            ['Settlement', 'admin.finance.settlements.index'],
            ['Income & Expense', 'admin.finance.cash-ledgers.index'],
        ]],
        ['title' => 'Operations', 'items' => [
            ['Sales Task', 'admin.sales-tasks.index'],
            ['Live Monitoring Sales', 'admin.operations.live-monitoring.index'],
            ['Delivery Order', 'admin.operations.delivery-orders.index'],
            ['Manajemen Rute', 'admin.operations.routes.index'],
            ['Vehicle', 'admin.master.vehicles.index'],
            ['Driver', 'admin.operations.drivers.index'],
            ['Monitoring', 'admin.operations.monitoring.index'],
        ]],
        ['title' => null, 'items' => [
            ['Reports', 'admin.reports.index'],
        ]],
        ['title' => 'System', 'items' => [
            ['Users', null],
            ['Roles', null],
            ['Permissions', null],
            ['Audit Log', null],
            ['Settings', null],
        ]],
    ];

    // Beberapa menu muncul di 2 grup (Customer, Invoice, Vehicle, Sales) --
    // tandai aktif hanya pada kemunculan PERTAMA supaya tidak dobel.
    $activeTaken = false;

    $hasLogo = file_exists(public_path('images/logo.png')) ? 'images/logo.png'
        : (file_exists(public_path('images/logo.svg')) ? 'images/logo.svg' : null);

    $userName = auth()->user()->name ?? 'Admin';
    $userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
@endphp

<div class="adm-shell">
    {{-- Sidebar: scroll sendiri, terpisah dari scroll halaman. Di layar
         <= 900px berubah jadi drawer (dibuka lewat tombol hamburger). --}}
    <aside class="adm-sidebar" id="adm-sidebar" aria-label="Menu utama">
        <div class="adm-brand">
            <a href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }} - Dashboard">
                @if ($hasLogo)
                    <img src="{{ asset($hasLogo) }}" alt="{{ config('app.name') }}">
                @else
                    <span class="adm-brand-placeholder">LOGO</span>
                @endif
            </a>
        </div>

        <nav class="adm-nav" id="adm-nav">
            @foreach ($menu as $group)
                <div class="adm-nav-group">
                    @if ($group['title'])
                        <p class="adm-nav-title">{{ $group['title'] }}</p>
                    @endif

                    @foreach ($group['items'] as [$label, $routeName])
                        @php
                            $isActive = false;
                            if ($routeName && ! $activeTaken) {
                                $pattern = $routeName === 'admin.dashboard'
                                    ? $routeName
                                    : preg_replace('/\.index$/', '.*', $routeName);
                                $isActive = request()->routeIs($pattern);
                                if ($isActive) {
                                    $activeTaken = true;
                                }
                            }
                        @endphp
                        <a href="{{ $routeName ? route($routeName) : '#' }}"
                           class="{{ $isActive ? 'is-active' : '' }}"
                           @if ($isActive) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    <div class="adm-overlay" id="adm-overlay"></div>

    <div class="adm-main">
        <header class="adm-topbar">
            <button type="button" class="adm-menu-btn" id="adm-menu-btn"
                    aria-label="Buka menu" aria-controls="adm-sidebar" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            <h2 class="adm-title">@isset($header){{ $header }}@endisset</h2>

            <div class="adm-user">
                <span class="adm-avatar" aria-hidden="true">{{ $userInitial }}</span>
                <span class="adm-user-text">
                    <span class="adm-user-name">{{ $userName }}</span>
                    <span class="adm-user-role">Admin</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="adm-logout" aria-label="Logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="adm-content">
            <div class="adm-container">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>

@stack('scripts')

<script>
    (function () {
        var sidebar = document.getElementById('adm-sidebar');
        var overlay = document.getElementById('adm-overlay');
        var btn = document.getElementById('adm-menu-btn');
        var nav = document.getElementById('adm-nav');
        if (!sidebar || !overlay || !btn) return;

        function setOpen(open) {
            sidebar.classList.toggle('is-open', open);
            overlay.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        }

        btn.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('is-open'));
        });
        overlay.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setOpen(false);
        });

        // Kembali ke layout desktop -> pastikan drawer tertutup.
        var mq = window.matchMedia('(min-width: 901px)');
        var onChange = function (e) { if (e.matches) setOpen(false); };
        if (mq.addEventListener) mq.addEventListener('change', onChange);
        else if (mq.addListener) mq.addListener(onChange);

        // Sidebar panjang: gulung otomatis supaya menu aktif terlihat.
        var active = nav && nav.querySelector('a.is-active');
        if (active) {
            nav.scrollTop = Math.max(0, active.offsetTop - nav.clientHeight / 2);
        }
    })();
</script>
</body>
</html>
