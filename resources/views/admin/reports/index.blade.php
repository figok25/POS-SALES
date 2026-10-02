<x-admin-layout>
    <x-slot name="header">Reports</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Reports</h1>
                <p class="frm-sub">Reporting murni dari data yang sudah ada di modul lain — pilih salah satu di bawah.</p>
            </div>
        </div>

        <div class="frm-link-grid">
            <a href="{{ route('admin.reports.sales') }}" class="frm-link-card">
                <p class="frm-link-card-title">Sales Report</p>
                <p class="frm-link-card-text">Rekap penjualan per Sales periode tertentu.</p>
            </a>
            <a href="{{ route('admin.reports.delivery') }}" class="frm-link-card">
                <p class="frm-link-card-title">Delivery Report</p>
                <p class="frm-link-card-text">Status Delivery Order &amp; pengiriman terbaru.</p>
            </a>
            <a href="{{ route('admin.reports.stock') }}" class="frm-link-card">
                <p class="frm-link-card-title">Stock Report</p>
                <p class="frm-link-card-text">Posisi stok per lokasi (Warehouse &amp; Sales).</p>
            </a>
            <a href="{{ route('admin.reports.outstanding') }}" class="frm-link-card">
                <p class="frm-link-card-title">Outstanding Invoice</p>
                <p class="frm-link-card-text">Invoice yang belum lunas / partial.</p>
            </a>
            <a href="{{ route('admin.reports.audit') }}" class="frm-link-card">
                <p class="frm-link-card-title">Audit Report</p>
                <p class="frm-link-card-text">Riwayat aktivitas seluruh modul (Audit Log).</p>
            </a>
        </div>
    </div>
</x-admin-layout>
