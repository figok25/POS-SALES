<x-sales-layout>
    <x-slot name="header">Tagging Toko</x-slot>

    <div class="flex justify-between items-center mb-3">
        <p class="text-sm text-gray-600">Riwayat tagging toko Anda</p>
        <a href="{{ route('sales.tagging.create') }}" class="bg-indigo-600 text-white text-sm px-3 py-1.5 rounded">+ Tagging</a>
    </div>

    @if (session('status'))
        <div class="mb-3 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
    @endif

    <div class="space-y-2">
        @forelse ($items as $item)
            <div class="bg-white rounded-lg shadow p-3 text-sm">
                <div class="flex justify-between">
                    <span class="font-medium">{{ $item->name }}</span>
                    @if ($item->status === 'pending')
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Pending</span>
                    @elseif ($item->status === 'approved')
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Approved</span>
                    @else
                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Rejected</span>
                    @endif
                </div>
                <p class="text-gray-500 text-xs mt-1">{{ $item->tagged_at->format('d M Y H:i') }}</p>
                @if ($item->review_notes)
                    <p class="text-gray-500 text-xs mt-1">Catatan Admin: {{ $item->review_notes }}</p>
                @endif
            </div>
        @empty
            <p class="text-center text-gray-500 text-sm py-6">Belum ada tagging toko.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-sales-layout>
