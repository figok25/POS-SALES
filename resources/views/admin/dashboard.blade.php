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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <a href="{{ route('admin.finance.settlements.index', ['status' => 'draft']) }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">Settlement Draft Pending</p>
                <p class="text-2xl font-semibold text-yellow-600">{{ number_format($kpi['settlement_draft_count']) }}</p>
            </a>
            <a href="{{ route('admin.finance.settlements.index', ['status' => 'applied']) }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">Settlement Di-Apply (Bulan Ini)</p>
                <p class="text-2xl font-semibold text-green-600">{{ number_format($kpi['settlement_applied_this_month']) }}</p>
            </a>
            <a href="{{ route('admin.finance.settlements.index', ['status' => 'applied']) }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">Selisih Uang (Bulan Ini)</p>
                <p class="text-2xl font-semibold {{ $kpi['settlement_cash_variance_this_month'] < 0 ? 'text-red-600' : 'text-gray-800' }}">Rp {{ number_format($kpi['settlement_cash_variance_this_month'], 0, ',', '.') }}</p>
            </a>
            <a href="{{ route('admin.finance.settlements.index', ['status' => 'applied']) }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="text-xs text-gray-500">Selisih Barang (Bulan Ini)</p>
                <p class="text-2xl font-semibold {{ $kpi['settlement_goods_variance_this_month'] > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ number_format($kpi['settlement_goods_variance_this_month'], 0, ',', '.') }} unit</p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded shadow">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <p class="font-medium">Transaksi Terbaru</p>
                    <a href="{{ route('admin.sales.transactions.index') }}" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left">Kode</th>
                            <th class="px-3 py-2 text-left">Customer</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $trx)
                            <tr class="border-b last:border-b-0">
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.sales.transactions.show', $trx) }}" class="text-blue-600 hover:underline font-medium">{{ $trx->code }}</a>
                                </td>
                                <td class="px-3 py-2">{{ $trx->customer->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    @if ($trx->status === 'completed')
                                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Completed</span>
                                    @else
                                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Cancelled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded shadow">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <p class="font-medium">Invoice Terbaru</p>
                    <a href="{{ route('admin.sales.invoices.index') }}" class="text-sm text-blue-600 hover:underline">Lihat Semua</a>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left">Kode</th>
                            <th class="px-3 py-2 text-left">Customer</th>
                            <th class="px-3 py-2 text-right">Grand Total</th>
                            <th class="px-3 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentInvoices as $inv)
                            <tr class="border-b last:border-b-0">
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.sales.invoices.show', $inv) }}" class="text-blue-600 hover:underline font-medium">{{ $inv->code }}</a>
                                </td>
                                <td class="px-3 py-2">{{ $inv->customer->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    @if ($inv->status === 'paid')
                                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Paid</span>
                                    @elseif ($inv->status === 'partial')
                                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Partial</span>
                                    @else
                                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Unpaid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada invoice.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
