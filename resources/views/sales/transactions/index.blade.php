<x-sales-layout>
    <x-slot name="header">Transaksi Penjualan</x-slot>

    <div class="flex justify-between items-center mb-3">
        <p class="text-sm text-gray-600">Riwayat transaksi Anda</p>
        <a href="{{ route('sales.transactions.create') }}" class="bg-indigo-600 text-white text-sm px-3 py-1.5 rounded">+ Transaksi</a>
    </div>

    @include('sales._date-filter', ['filterRoute' => 'sales.transactions.index'])

    <div class="flex justify-between text-sm bg-white rounded-lg shadow p-3 mb-3">
        <span class="text-gray-500">{{ $summary['count'] }} transaksi</span>
        <span class="font-semibold">Rp {{ number_format($summary['total'], 0, ',', '.') }}</span>
    </div>

    <a href="{{ route('sales.transactions.daily', $filterDate ? ['date' => $filterDate->toDateString()] : []) }}" class="block text-center bg-white border border-indigo-200 text-indigo-700 text-sm px-3 py-2 rounded-lg mb-3">🖨️ Laporan Penjualan {{ $filterDate && ! $filterDate->isToday() ? $filterDate->format('d/m/Y') : 'Hari Ini' }} (cetak)</a>

    @if (session('status'))
        <div class="mb-3 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
    @endif

    <div class="space-y-2">
        @forelse ($items as $item)
            <a href="{{ route('sales.transactions.show', $item) }}" class="block bg-white rounded-lg shadow p-3 text-sm">
                <div class="flex justify-between">
                    <span class="font-medium">{{ $item->code }}</span>
                    <span>Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                </div>
                <p class="text-gray-500 text-xs mt-1">{{ $item->customer->name ?? '-' }} &middot; {{ $item->created_at->format('d M Y H:i') }}</p>
            </a>
        @empty
            <p class="text-center text-gray-500 text-sm py-6">{{ $filterDate ? 'Tidak ada transaksi pada tanggal ini.' : 'Belum ada transaksi.' }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-sales-layout>
