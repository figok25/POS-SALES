<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/admin/master-data.css'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex">
        {{-- Sidebar: struktur menu mengikuti Blueprint #47 Admin Navigation --}}
        @php
            // Shared nav-item classes driven by the sidebar theme tokens
            // (see tailwind.config.js -> theme.extend.colors.sidebar).
            $navLinkClass = fn (string|array $routes) => request()->routeIs($routes)
                ? 'block px-2 py-1.5 rounded bg-sidebar-active text-white font-medium transition-colors'
                : 'block px-2 py-1.5 rounded text-sidebar-foreground hover:bg-sidebar-hover hover:text-white transition-colors';

            // A collapsible section starts expanded when the current page
            // belongs to it, so the active item is never hidden on load.
            $sectionOpen = fn (array $routes) => request()->routeIs($routes) ? 'true' : 'false';
        @endphp
        <aside class="w-64 bg-sidebar text-sidebar-foreground flex-shrink-0 hidden md:block">
            <div class="px-4 py-4 text-lg font-semibold text-white border-b border-sidebar-border">
                {{ config('app.name', 'POS & Sales') }}
            </div>
            <nav class="px-2 py-4 space-y-1 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="{{ $navLinkClass('admin.dashboard') }}">Dashboard</a>

                <div x-data="{ open: {{ $sectionOpen([
                    'admin.master.companies.index',
                    'admin.master.branches.index',
                    'admin.master.warehouses.index',
                    'admin.master.products.index',
                    'admin.master.categories.index',
                    'admin.master.units.index',
                    'admin.master.prices.index',
                    'admin.master.customers.index',
                    'admin.master.employees.index',
                    'admin.master.sales.index',
                    'admin.master.vehicles.index',
                    'admin.master.suppliers.index',
                ]) }} }" class="py-1">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs uppercase tracking-wide text-sidebar-heading hover:text-white transition-colors">
                        <span>Master Data</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ '-rotate-180': open }">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-0.5">
                        <a href="{{ route('admin.master.companies.index') }}" class="{{ $navLinkClass('admin.master.companies.index') }}">Company</a>
                        <a href="{{ route('admin.master.branches.index') }}" class="{{ $navLinkClass('admin.master.branches.index') }}">Branch</a>
                        <a href="{{ route('admin.master.warehouses.index') }}" class="{{ $navLinkClass('admin.master.warehouses.index') }}">Warehouse</a>
                        <a href="{{ route('admin.master.products.index') }}" class="{{ $navLinkClass('admin.master.products.index') }}">Product</a>
                        <a href="{{ route('admin.master.categories.index') }}" class="{{ $navLinkClass('admin.master.categories.index') }}">Category</a>
                        <a href="{{ route('admin.master.units.index') }}" class="{{ $navLinkClass('admin.master.units.index') }}">Unit</a>
                        <a href="{{ route('admin.master.prices.index') }}" class="{{ $navLinkClass('admin.master.prices.index') }}">Price</a>
                        <a href="{{ route('admin.master.customers.index') }}" class="{{ $navLinkClass('admin.master.customers.index') }}">Customer</a>
                        <a href="{{ route('admin.master.employees.index') }}" class="{{ $navLinkClass('admin.master.employees.index') }}">Employee</a>
                        <a href="{{ route('admin.master.sales.index') }}" class="{{ $navLinkClass('admin.master.sales.index') }}">Sales</a>
                        <a href="{{ route('admin.master.vehicles.index') }}" class="{{ $navLinkClass('admin.master.vehicles.index') }}">Vehicle</a>
                        <a href="{{ route('admin.master.suppliers.index') }}" class="{{ $navLinkClass('admin.master.suppliers.index') }}">Supplier</a>
                    </div>
                </div>

                <div x-data="{ open: {{ $sectionOpen([
                    'admin.inventory.stock.index',
                    'admin.inventory.movements.index',
                    'admin.inventory.adjustments.index',
                ]) }} }" class="py-1">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs uppercase tracking-wide text-sidebar-heading hover:text-white transition-colors">
                        <span>Inventory</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ '-rotate-180': open }">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-0.5">
                        <a href="{{ route('admin.inventory.stock.index') }}" class="{{ $navLinkClass('admin.inventory.stock.index') }}">Stock</a>
                        <a href="{{ route('admin.inventory.movements.index') }}" class="{{ $navLinkClass('admin.inventory.movements.index') }}">Stock Movement</a>
                        <a href="{{ route('admin.inventory.adjustments.index') }}" class="{{ $navLinkClass('admin.inventory.adjustments.index') }}">Stock Adjustment</a>
                    </div>
                </div>

                <div x-data="{ open: {{ $sectionOpen([
                    'admin.distribution.stock-requests.index',
                    'admin.distribution.bkb.index',
                    'admin.distribution.btb.index',
                    'admin.distribution.branch-transfer.index',
                ]) }} }" class="py-1">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs uppercase tracking-wide text-sidebar-heading hover:text-white transition-colors">
                        <span>Distribution</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ '-rotate-180': open }">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-0.5">
                        <a href="{{ route('admin.distribution.stock-requests.index') }}" class="{{ $navLinkClass('admin.distribution.stock-requests.index') }}">Permintaan Barang</a>
                        <a href="{{ route('admin.distribution.bkb.index') }}" class="{{ $navLinkClass('admin.distribution.bkb.index') }}">BKB Distribusi</a>
                        <a href="{{ route('admin.distribution.btb.index') }}" class="{{ $navLinkClass('admin.distribution.btb.index') }}">BTB Distribusi</a>
                        <a href="{{ route('admin.distribution.branch-transfer.index') }}" class="{{ $navLinkClass('admin.distribution.branch-transfer.index') }}">Branch Transfer (BKB/BTB Cabang)</a>
                    </div>
                </div>

                <div x-data="{ open: {{ $sectionOpen([
                    'admin.master.sales.index',
                    'admin.master.customers.index',
                    'admin.sales.customer-assignments.index',
                    'admin.sales.customer-taggings.index',
                    'admin.sales.visits.index',
                    'admin.sales.transactions.index',
                    'admin.sales.invoices.index',
                ]) }} }" class="py-1">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs uppercase tracking-wide text-sidebar-heading hover:text-white transition-colors">
                        <span>Sales</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ '-rotate-180': open }">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-0.5">
                        <a href="{{ route('admin.master.sales.index') }}" class="{{ $navLinkClass('admin.master.sales.index') }}">Sales Management</a>
                        <a href="{{ route('admin.master.customers.index') }}" class="{{ $navLinkClass('admin.master.customers.index') }}">Customer</a>
                        <a href="{{ route('admin.sales.customer-assignments.index') }}" class="{{ $navLinkClass('admin.sales.customer-assignments.index') }}">Customer Assignment</a>
                        <a href="{{ route('admin.sales.customer-taggings.index') }}" class="{{ $navLinkClass('admin.sales.customer-taggings.index') }}">Tagging Toko</a>
                        <a href="{{ route('admin.sales.visits.index') }}" class="{{ $navLinkClass('admin.sales.visits.index') }}">Visit</a>
                        <a href="{{ route('admin.sales.transactions.index') }}" class="{{ $navLinkClass('admin.sales.transactions.index') }}">Transaksi Penjualan</a>
                        <a href="{{ route('admin.sales.invoices.index') }}" class="{{ $navLinkClass('admin.sales.invoices.index') }}">Invoice</a>
                    </div>
                </div>

                <div x-data="{ open: {{ $sectionOpen([
                    'admin.sales.invoices.index',
                    'admin.finance.payments.index',
                    'admin.finance.settlements.index',
                    'admin.finance.cash-ledgers.index',
                ]) }} }" class="py-1">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs uppercase tracking-wide text-sidebar-heading hover:text-white transition-colors">
                        <span>Finance</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ '-rotate-180': open }">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-0.5">
                        <a href="{{ route('admin.sales.invoices.index') }}" class="{{ $navLinkClass('admin.sales.invoices.index') }}">Invoice</a>
                        <a href="{{ route('admin.finance.payments.index') }}" class="{{ $navLinkClass('admin.finance.payments.index') }}">Payment</a>
                        <a href="{{ route('admin.finance.settlements.index') }}" class="{{ $navLinkClass('admin.finance.settlements.index') }}">Settlement</a>
                        <a href="{{ route('admin.finance.cash-ledgers.index') }}" class="{{ $navLinkClass('admin.finance.cash-ledgers.index') }}">Income & Expense</a>
                    </div>
                </div>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Operations</p>
                    <a href="{{ route('admin.sales-tasks.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Sales Task</a>
                    <a href="{{ route('admin.operations.live-monitoring.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Live Monitoring Sales</a>
                    <a href="{{ route('admin.operations.delivery-orders.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Delivery Order</a>
                    <a href="{{ route('admin.operations.routes.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Route</a>
                    <a href="{{ route('admin.master.vehicles.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Vehicle</a>
                    <a href="{{ route('admin.operations.drivers.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Driver</a>
                    <a href="{{ route('admin.operations.monitoring.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Monitoring</a>
                </div>

                <a href="{{ route('admin.reports.index') }}" class="{{ $navLinkClass('admin.reports.index') }}">Reports</a>

                <div x-data="{ open: false }" class="py-1">
                    <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full flex items-center justify-between px-2 py-1.5 rounded text-xs uppercase tracking-wide text-sidebar-heading hover:text-white transition-colors">
                        <span>System</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ '-rotate-180': open }">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-0.5">
                        <a href="#" class="block px-2 py-1.5 rounded text-sidebar-foreground hover:bg-sidebar-hover hover:text-white transition-colors">Users</a>
                        <a href="#" class="block px-2 py-1.5 rounded text-sidebar-foreground hover:bg-sidebar-hover hover:text-white transition-colors">Roles</a>
                        <a href="#" class="block px-2 py-1.5 rounded text-sidebar-foreground hover:bg-sidebar-hover hover:text-white transition-colors">Permissions</a>
                        <a href="#" class="block px-2 py-1.5 rounded text-sidebar-foreground hover:bg-sidebar-hover hover:text-white transition-colors">Audit Log</a>
                        <a href="#" class="block px-2 py-1.5 rounded text-sidebar-foreground hover:bg-sidebar-hover hover:text-white transition-colors">Settings</a>
                    </div>
                </div>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                    <div>
                        @isset($header)
                            <h2 class="font-semibold text-xl text-gray-800">{{ $header }}</h2>
                        @endisset
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-600">{{ auth()->user()->name }} <span class="text-xs text-gray-400">(Admin)</span></span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-red-600 hover:underline">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
