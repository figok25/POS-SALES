<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS & Sales') }} - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex">
        {{-- Sidebar: struktur menu mengikuti Blueprint #47 Admin Navigation --}}
        <aside class="w-64 bg-gray-900 text-gray-200 flex-shrink-0 hidden md:block">
            <div class="px-4 py-4 text-lg font-semibold text-white border-b border-gray-800">
                {{ config('app.name', 'POS & Sales') }}
            </div>
            <nav class="px-2 py-4 space-y-4 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Dashboard</a>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Master Data</p>
                    <a href="{{ route('admin.master.companies.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Company</a>
                    <a href="{{ route('admin.master.branches.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Branch</a>
                    <a href="{{ route('admin.master.warehouses.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Warehouse</a>
                    <a href="{{ route('admin.master.products.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Product</a>
                    <a href="{{ route('admin.master.categories.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Category</a>
                    <a href="{{ route('admin.master.units.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Unit</a>
                    <a href="{{ route('admin.master.prices.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Price</a>
                    <a href="{{ route('admin.master.customers.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Customer</a>
                    <a href="{{ route('admin.master.employees.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Employee</a>
                    <a href="{{ route('admin.master.sales.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Sales</a>
                    <a href="{{ route('admin.master.vehicles.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Vehicle</a>
                    <a href="{{ route('admin.master.suppliers.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Supplier</a>
                </div>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Inventory</p>
                    <a href="{{ route('admin.inventory.stock.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Stock</a>
                    <a href="{{ route('admin.inventory.movements.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Stock Movement</a>
                    <a href="{{ route('admin.inventory.adjustments.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Stock Adjustment</a>
                </div>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Distribution</p>
                    <a href="{{ route('admin.distribution.stock-requests.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Permintaan Barang</a>
                    <a href="{{ route('admin.distribution.bkb.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">BKB Distribusi</a>
                    <a href="{{ route('admin.distribution.btb.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">BTB Distribusi</a>
                    <a href="{{ route('admin.distribution.branch-transfer.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Branch Transfer (BKB/BTB Cabang)</a>
                </div>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Sales</p>
                    <a href="{{ route('admin.master.sales.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Sales Management</a>
                    <a href="{{ route('admin.master.customers.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Customer</a>
                    <a href="{{ route('admin.sales.customer-taggings.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Tagging Toko</a>
                    <a href="{{ route('admin.sales.visits.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Visit</a>
                    <a href="{{ route('admin.sales.transactions.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Transaksi Penjualan</a>
                    <a href="{{ route('admin.sales.invoices.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Invoice</a>
                </div>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Finance</p>
                    <a href="{{ route('admin.sales.invoices.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Invoice</a>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Payment</a>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Settlement</a>
                </div>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">Operations</p>
                    <a href="{{ route('admin.operations.delivery-orders.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Delivery Order</a>
                    <a href="{{ route('admin.operations.routes.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Route</a>
                    <a href="{{ route('admin.master.vehicles.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Vehicle</a>
                    <a href="{{ route('admin.operations.drivers.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Driver</a>
                    <a href="{{ route('admin.operations.monitoring.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Monitoring</a>
                </div>

                <a href="{{ route('admin.reports.index') }}" class="block px-2 py-1.5 rounded hover:bg-gray-800">Reports</a>

                <div>
                    <p class="px-2 text-xs uppercase tracking-wide text-gray-500 mb-1">System</p>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Users</a>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Roles</a>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Permissions</a>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Audit Log</a>
                    <a href="#" class="block px-2 py-1.5 rounded hover:bg-gray-800">Settings</a>
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
