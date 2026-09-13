<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-4">Reports</h1>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.reports.sales') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="font-medium">Sales Report</p>
                <p class="text-xs text-gray-500 mt-1">Rekap penjualan per Sales periode tertentu.</p>
            </a>
            <a href="{{ route('admin.reports.delivery') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="font-medium">Delivery Report</p>
                <p class="text-xs text-gray-500 mt-1">Status Delivery Order & pengiriman terbaru.</p>
            </a>
            <a href="{{ route('admin.reports.stock') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="font-medium">Stock Report</p>
                <p class="text-xs text-gray-500 mt-1">Posisi stok per lokasi (Warehouse & Sales).</p>
            </a>
            <a href="{{ route('admin.reports.outstanding') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="font-medium">Outstanding Invoice</p>
                <p class="text-xs text-gray-500 mt-1">Invoice yang belum lunas / partial.</p>
            </a>
            <a href="{{ route('admin.reports.audit') }}" class="bg-white rounded shadow p-4 hover:shadow-md">
                <p class="font-medium">Audit Report</p>
                <p class="text-xs text-gray-500 mt-1">Riwayat aktivitas seluruh modul (Audit Log).</p>
            </a>
        </div>
    </div>
</x-admin-layout>
