<x-sales-layout>
    <x-slot name="header">Sales Stock</x-slot>

    <p class="text-sm text-gray-600 mb-3">Stok yang ada pada Anda saat ini (hasil BKB dari Gudang).</p>

    <div class="bg-white rounded-lg shadow divide-y text-sm">
        @forelse ($items as $item)
            <div class="flex justify-between px-3 py-2.5">
                <span>{{ $item->product->name ?? '-' }}</span>
                <span class="font-medium">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</span>
            </div>
        @empty
            <p class="text-center text-gray-500 text-sm py-6">Sales Stock Anda kosong.</p>
        @endforelse
    </div>
</x-sales-layout>
