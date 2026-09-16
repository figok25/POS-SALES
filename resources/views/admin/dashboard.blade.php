<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

```
<div class="p-6 lg:p-8 space-y-8">

    {{-- Welcome --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Dashboard Overview</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                Selamat datang, {{ auth()->user()->name }} 👋
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Berikut ringkasan aktivitas bisnis Anda hari ini.
            </p>
        </div>
    </div>

    {{-- Main KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Products --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Produk</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ number_format($kpi['total_products']) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>

            <div class="mt-4 flex items-center text-xs text-gray-400">
                <span>Produk terdaftar</span>
            </div>
        </div>

        {{-- Customers --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Customer</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ number_format($kpi['total_customers']) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-8a4 4 0 110 8 4 4 0 010-8zm6 4a3 3 0 10-6 0 3 3 0 006 0z"/>
                    </svg>
                </div>
            </div>

            <div class="mt-4 flex items-center text-xs text-gray-400">
                <span>Customer terdaftar</span>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Transaksi Bulan Ini</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ number_format($kpi['sales_count_this_month']) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="mt-4 flex items-center text-xs text-gray-400">
                <span>Transaksi berhasil</span>
            </div>
        </div>

        {{-- Sales --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Penjualan Bulan Ini</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rp {{ number_format($kpi['sales_total_this_month'], 0, ',', '.') }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-50 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-10v12m8-6a8 8 0 11-16 0 8 8 0 0116 0z"/>
                    </svg>
                </div>
            </div>

            <div class="mt-4 flex items-center text-xs text-gray-400">
                <span>Total pendapatan</span>
            </div>
        </div>
    </div>

    {{-- Operations --}}
    <div>
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Status Operasional</h2>
            <p class="mt-1 text-sm text-gray-500">
                Pantau invoice dan delivery order yang membutuhkan perhatian.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Outstanding --}}
            <a href="{{ route('admin.reports.outstanding') }}"
               class="group rounded-2xl border border-red-100 bg-red-50/50 p-5 transition hover:-translate-y-0.5 hover:border-red-200 hover:bg-red-50 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"/>
                        </svg>
                    </div>

                    <svg class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                <p class="mt-4 text-sm font-medium text-gray-600">
                    Invoice Outstanding
                </p>

                <p class="mt-1 text-2xl font-bold text-red-600">
                    {{ number_format($kpi['outstanding_invoices']) }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Rp {{ number_format($kpi['outstanding_amount'], 0, ',', '.') }}
                </p>
            </a>

            {{-- Draft DO --}}
            <a href="{{ route('admin.operations.delivery-orders.index', ['status' => 'draft']) }}"
               class="group rounded-2xl border border-amber-100 bg-amber-50/50 p-5 transition hover:-translate-y-0.5 hover:border-amber-200 hover:bg-amber-50 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>

                    <svg class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                <p class="mt-4 text-sm font-medium text-gray-600">DO Draft</p>

                <p class="mt-1 text-2xl font-bold text-amber-600">
                    {{ number_format($kpi['do_draft']) }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Menunggu diproses
                </p>
            </a>

            {{-- Dispatched --}}
            <a href="{{ route('admin.operations.delivery-orders.index', ['status' => 'dispatched']) }}"
               class="group rounded-2xl border border-blue-100 bg-blue-50/50 p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </div>

                    <svg class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                <p class="mt-4 text-sm font-medium text-gray-600">DO Dispatched</p>

                <p class="mt-1 text-2xl font-bold text-blue-600">
                    {{ number_format($kpi['do_dispatched']) }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Sedang dalam pengiriman
                </p>
            </a>

            {{-- Delivered --}}
            <a href="{{ route('admin.operations.monitoring.index') }}"
               class="group rounded-2xl border border-green-100 bg-green-50/50 p-5 transition hover:-translate-y-0.5 hover:border-green-200 hover:bg-green-50 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>

                    <svg class="h-5 w-5 text-gray-400 transition group-hover:translate-x-1"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                <p class="mt-4 text-sm font-medium text-gray-600">Delivered Hari Ini</p>

                <p class="mt-1 text-2xl font-bold text-green-600">
                    {{ number_format($kpi['do_delivered_today']) }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Pengiriman selesai
                </p>
            </a>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Akses Cepat</h2>
                <p class="text-sm text-gray-500">
                    Akses fitur yang sering digunakan.
                </p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">

            <a href="{{ route('admin.operations.monitoring.index') }}"
               class="group flex items-center gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-blue-200 hover:bg-blue-50/50">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V6l-5-2m0 12V6m0 12l-6-2"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Monitoring Pengiriman</p>
                    <p class="mt-0.5 text-xs text-gray-500">Pantau delivery order</p>
                </div>

                <svg class="ml-auto h-4 w-4 text-gray-400 transition group-hover:translate-x-1"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('admin.reports.index') }}"
               class="group flex items-center gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-purple-200 hover:bg-purple-50/50">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2z"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Reports</p>
                    <p class="mt-0.5 text-xs text-gray-500">Lihat laporan bisnis</p>
                </div>

                <svg class="ml-auto h-4 w-4 text-gray-400 transition group-hover:translate-x-1"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('admin.operations.delivery-orders.create') }}"
               class="group flex items-center gap-4 rounded-xl border border-gray-200 p-4 transition hover:border-green-200 hover:bg-green-50/50">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H4"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Buat Delivery Order</p>
                    <p class="mt-0.5 text-xs text-gray-500">Buat pengiriman baru</p>
                </div>

                <svg class="ml-auto h-4 w-4 text-gray-400 transition group-hover:translate-x-1"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7"/>
                </svg>
            </a>

        </div>
    </div>

</div>
```

</x-admin-layout>
