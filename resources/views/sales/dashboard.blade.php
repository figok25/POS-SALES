<x-sales-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-gray-700">Halo, <strong>{{ auth()->user()->name }}</strong>.</p>
        <p class="text-sm text-gray-500 mt-2">
            Ini Dashboard Sales (Fase 1). Menu Customer, Tagging Toko, Kunjungan, Transaksi
            Penjualan, dan Sales Stock akan aktif pada Fase 6 (Sales & Customer).
        </p>
    </div>
</x-sales-layout>
