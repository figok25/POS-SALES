<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="p-6">
        <p class="text-gray-700 mb-6">Selamat datang, <strong>{{ auth()->user()->name }}</strong>.</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Produk</p>
                <p class="text-2xl font-semibold">{{ number_format($kpi['total_products']) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Total Customer</p>
                <p class="text-2xl font-semibold">{{ number_format($kpi['total_customers']) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Transaksi Bulan Ini</p>
                <p class="text-2xl font-semibold">{{ number_format($kpi['sales_count_this_month']) }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500">Penjualan Bulan Ini</p>
                <p class="text-2xl font-semibold">Rp {{ number_format($kpi['sales_total_this_month'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <a href="{{ route('admin.reports.outstanding') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">Invoice Outstanding</p>
                <p class="text-2xl font-semibold text-red-600">{{ number_format($kpi['outstanding_invoices']) }}</p>
                <p class="text-xs text-gray-400">Rp {{ number_format($kpi['outstanding_amount'], 0, ',', '.') }}</p>
            </a>
            <a href="{{ route('admin.operations.delivery-orders.index', ['status' => 'draft']) }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">DO Draft</p>
                <p class="text-2xl font-semibold text-yellow-600">{{ number_format($kpi['do_draft']) }}</p>
            </a>
            <a href="{{ route('admin.operations.delivery-orders.index', ['status' => 'dispatched']) }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">DO Dispatched</p>
                <p class="text-2xl font-semibold text-blue-600">{{ number_format($kpi['do_dispatched']) }}</p>
            </a>
            <a href="{{ route('admin.operations.monitoring.index') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">Delivered Hari Ini</p>
                <p class="text-2xl font-semibold text-green-600">{{ number_format($kpi['do_delivered_today']) }}</p>
            </a>
        </div>

        <div class="bg-white rounded shadow p-4">
            <p class="font-medium mb-2">Akses Cepat</p>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.operations.monitoring.index') }}" class="border px-3 py-1.5 rounded hover:bg-gray-50">Monitoring Pengiriman</a>
                <a href="{{ route('admin.reports.index') }}" class="border px-3 py-1.5 rounded hover:bg-gray-50">Reports</a>
                <a href="{{ route('admin.operations.delivery-orders.create') }}" class="border px-3 py-1.5 rounded hover:bg-gray-50">Buat Delivery Order</a>
            </div>
        </div>
    </div>
</x-admin-layout>
