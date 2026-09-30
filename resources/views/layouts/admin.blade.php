<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Admin</title>

    {{-- Pasang tema SEBELUM stylesheet & body digambar supaya tidak berkedip
         terang dulu saat mode gelap. Urutan prioritas: pilihan user
         (localStorage) -> preferensi sistem (prefers-color-scheme) -> terang. --}}
    <script>
        (function () {
            var theme = 'light';
            try {
                var saved = localStorage.getItem('adm.theme');
                if (saved === 'light' || saved === 'dark') {
                    theme = saved;
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
            } catch (e) {}
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

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
            ['Users', 'admin.system.users.index'],
            ['Roles', 'admin.system.roles.index'],
            ['Permissions', 'admin.system.permissions.index'],
            ['Audit Log', 'admin.system.audit-log.index'],
            ['Settings', null],
        ]],
    ];

    // Beberapa menu muncul di 2 grup (Customer, Invoice, Vehicle, Sales) --
    // tandai aktif hanya pada kemunculan PERTAMA supaya tidak dobel.
    $activeTaken = false;

    // Resolusi menu satu kali jalan: URL, status aktif per item, dan status
    // aktif per grup (grup yang berisi menu aktif otomatis terbuka).
    $navGroups = [];
    foreach ($menu as $group) {
        $items = [];
        $groupActive = false;

        foreach ($group['items'] as [$label, $routeName]) {
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

            $items[] = [
                'label' => $label,
                'url' => $routeName ? route($routeName) : '#',
                'active' => $isActive,
            ];
            $groupActive = $groupActive || $isActive;
        }

        $navGroups[] = [
            'title' => $group['title'],
            'key' => \Illuminate\Support\Str::slug($group['title'] ?? ''),
            'items' => $items,
            'active' => $groupActive,
        ];
    }

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

        {{-- Pencarian menu: hanya mencocokkan NAMA menu. --}}
        <div class="adm-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" id="adm-search" placeholder="Cari menu..." autocomplete="off"
                   aria-label="Cari menu" aria-controls="adm-nav">
            <kbd aria-hidden="true">/</kbd>
        </div>

        <nav class="adm-nav no-anim" id="adm-nav">
            @foreach ($navGroups as $group)
                @if ($group['title'])
                    <div class="adm-nav-group is-collapsible {{ $group['active'] ? 'is-open' : '' }}"
                         data-group="{{ $group['key'] }}" data-active="{{ $group['active'] ? 1 : 0 }}">
                        <button type="button" class="adm-nav-title"
                                aria-expanded="{{ $group['active'] ? 'true' : 'false' }}"
                                aria-controls="adm-grp-{{ $group['key'] }}">
                            <span>{{ $group['title'] }}</span>
                            <svg class="adm-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="adm-nav-items" id="adm-grp-{{ $group['key'] }}">
                            <div class="adm-nav-items-inner">
                                @foreach ($group['items'] as $item)
                                    <a href="{{ $item['url'] }}"
                                       class="{{ $item['active'] ? 'is-active' : '' }}"
                                       @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Grup tanpa judul (Dashboard, Reports): link tunggal, tanpa dropdown. --}}
                    <div class="adm-nav-group is-flat">
                        @foreach ($group['items'] as $item)
                            <a href="{{ $item['url'] }}"
                               class="{{ $item['active'] ? 'is-active' : '' }}"
                               @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            <p class="adm-nav-empty" id="adm-nav-empty" hidden>Menu tidak ditemukan.</p>
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

            {{-- Pilih mode tampilan: terang / gelap. --}}
            <button type="button" class="adm-theme-btn" id="adm-theme-btn"
                    aria-label="Ganti ke mode gelap" title="Ganti ke mode gelap" aria-pressed="false">
                <svg class="adm-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <svg class="adm-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/>
                    <line x1="4.93" y1="4.93" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.07" y2="19.07"/>
                    <line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/>
                    <line x1="4.93" y1="19.07" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.07" y2="4.93"/>
                </svg>
            </button>

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
        var root = document.documentElement;
        var sidebar = document.getElementById('adm-sidebar');
        var overlay = document.getElementById('adm-overlay');
        var btn = document.getElementById('adm-menu-btn');
        var nav = document.getElementById('adm-nav');

        function store(key, value) { try { localStorage.setItem(key, value); } catch (e) {} }
        function load(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }

        /* ---------------- Drawer sidebar (mobile) ---------------- */
        function setOpen(open) {
            sidebar.classList.toggle('is-open', open);
            overlay.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        }

        if (sidebar && overlay && btn) {
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
        }

        /* ---------------- Mode terang / gelap ---------------- */
        var themeBtn = document.getElementById('adm-theme-btn');

        function applyTheme(theme) {
            root.setAttribute('data-theme', theme);
            if (!themeBtn) return;
            var dark = theme === 'dark';
            var label = dark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap';
            themeBtn.setAttribute('aria-pressed', dark ? 'true' : 'false');
            themeBtn.setAttribute('aria-label', label);
            themeBtn.setAttribute('title', label);
        }

        applyTheme(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                store('adm.theme', next);
                applyTheme(next);
            });
        }

        // Selama user belum memilih manual, ikuti perubahan tema sistem operasi.
        if (window.matchMedia) {
            var sysMq = window.matchMedia('(prefers-color-scheme: dark)');
            var onSys = function (e) {
                var saved = load('adm.theme');
                if (saved !== 'light' && saved !== 'dark') applyTheme(e.matches ? 'dark' : 'light');
            };
            if (sysMq.addEventListener) sysMq.addEventListener('change', onSys);
            else if (sysMq.addListener) sysMq.addListener(onSys);
        }

        /* ---------------- Sidebar: dropdown per kategori ---------------- */
        if (!nav) return;

        var OPEN_KEY = 'adm.nav.open';
        var groups = Array.prototype.slice.call(nav.querySelectorAll('.adm-nav-group.is-collapsible'));

        function readOpenList() {
            try {
                var v = JSON.parse(load(OPEN_KEY));
                return Array.isArray(v) ? v : [];
            } catch (e) { return []; }
        }

        function setGroupOpen(group, open) {
            group.classList.toggle('is-open', open);
            var t = group.querySelector('.adm-nav-title');
            if (t) t.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function saveOpenList() {
            var open = groups.filter(function (g) { return g.classList.contains('is-open'); })
                             .map(function (g) { return g.getAttribute('data-group'); });
            store(OPEN_KEY, JSON.stringify(open));
        }

        // Kondisi awal: grup yang tersimpan terbuka + grup yang berisi menu aktif.
        var savedOpen = readOpenList();
        groups.forEach(function (g) {
            var key = g.getAttribute('data-group');
            setGroupOpen(g, savedOpen.indexOf(key) !== -1 || g.getAttribute('data-active') === '1');
        });

        groups.forEach(function (g) {
            var t = g.querySelector('.adm-nav-title');
            if (!t) return;
            t.addEventListener('click', function () {
                setGroupOpen(g, !g.classList.contains('is-open'));
                saveOpenList();
            });
        });

        // Nyalakan animasi setelah state awal terpasang (hindari "lompat" saat load).
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () { nav.classList.remove('no-anim'); });
        });

        /* ---------------- Sidebar: pencarian menu (nama saja) ---------------- */
        var search = document.getElementById('adm-search');
        var emptyMsg = document.getElementById('adm-nav-empty');
        var allGroups = Array.prototype.slice.call(nav.querySelectorAll('.adm-nav-group'));

        function filterMenu() {
            var q = search.value.trim().toLowerCase();
            var searching = q !== '';
            var total = 0;

            nav.classList.toggle('is-searching', searching);

            allGroups.forEach(function (g) {
                var shown = 0;
                Array.prototype.forEach.call(g.querySelectorAll('a'), function (a) {
                    var match = !searching || a.textContent.toLowerCase().indexOf(q) !== -1;
                    a.classList.toggle('is-hidden', !match);
                    if (match) shown++;
                });
                g.classList.toggle('is-hidden', shown === 0);
                total += shown;
            });

            if (emptyMsg) emptyMsg.hidden = !(searching && total === 0);
        }

        if (search) {
            search.addEventListener('input', filterMenu);

            search.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    var first = nav.querySelector('a:not(.is-hidden)');
                    if (first && search.value.trim() !== '') {
                        e.preventDefault();
                        window.location.href = first.getAttribute('href');
                    }
                } else if (e.key === 'Escape') {
                    if (search.value !== '') {
                        search.value = '';
                        filterMenu();
                    } else {
                        search.blur();
                    }
                }
            });

            // Pintasan "/" untuk langsung fokus ke pencarian menu.
            document.addEventListener('keydown', function (e) {
                if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
                var tag = (document.activeElement && document.activeElement.tagName) || '';
                if (/^(INPUT|TEXTAREA|SELECT)$/.test(tag) || (document.activeElement && document.activeElement.isContentEditable)) return;
                e.preventDefault();
                if (sidebar && window.matchMedia('(max-width: 900px)').matches) setOpen(true);
                search.focus();
            });
        }

        // Sidebar panjang: gulung otomatis supaya menu aktif terlihat.
        var active = nav.querySelector('a.is-active');
        if (active) {
            nav.scrollTop = Math.max(0, active.offsetTop - nav.clientHeight / 2);
        }
    })();
</script>
</body>
</html>
