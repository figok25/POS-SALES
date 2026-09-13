<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Detail Tagging Toko</h1>
            <a href="{{ route('admin.sales.customer-taggings.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded shadow p-4 space-y-2 text-sm mb-4">
            <div class="flex justify-between"><span class="text-gray-500">Sales</span><span>{{ $tagging->sales->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Waktu Tagging</span><span>{{ $tagging->tagged_at->format('d M Y H:i') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Nama Toko</span><span>{{ $tagging->name }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Telepon</span><span>{{ $tagging->phone ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Alamat</span><span class="text-right">{{ $tagging->address ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Tipe Toko</span><span>{{ $tagging->customer_type ?? '-' }}</span></div>
            <div class="flex justify-between">
                <span class="text-gray-500">Koordinat</span>
                <span>
                    @if ($tagging->latitude && $tagging->longitude)
                        <a class="text-blue-600 hover:underline" target="_blank" href="https://maps.google.com/?q={{ $tagging->latitude }},{{ $tagging->longitude }}">{{ $tagging->latitude }}, {{ $tagging->longitude }}</a>
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Catatan Sales</span><span class="text-right">{{ $tagging->notes ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span>
                <span>
                    @if ($tagging->status === 'pending')
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Pending</span>
                    @elseif ($tagging->status === 'approved')
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Approved</span>
                    @else
                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Rejected</span>
                    @endif
                </span>
            </div>
            @if ($tagging->customer)
                <div class="flex justify-between"><span class="text-gray-500">Customer Terkait</span>
                    <a href="{{ route('admin.master.customers.show', $tagging->customer) }}" class="text-blue-600 hover:underline">{{ $tagging->customer->code }} - {{ $tagging->customer->name }}</a>
                </div>
            @endif
            @if ($tagging->reviewer)
                <div class="flex justify-between"><span class="text-gray-500">Direview oleh</span><span>{{ $tagging->reviewer->name }} - {{ $tagging->reviewed_at?->format('d M Y H:i') }}</span></div>
            @endif
        </div>

        @if ($duplicates->isNotEmpty())
            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4 text-sm">
                <p class="font-medium text-yellow-800 mb-2">Kemungkinan duplikasi dengan customer yang sudah ada:</p>
                <ul class="list-disc list-inside text-yellow-800">
                    @foreach ($duplicates as $dup)
                        <li>{{ $dup->code }} - {{ $dup->name }} ({{ $dup->phone ?? '-' }})</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($tagging->isPending())
            <div class="grid grid-cols-2 gap-4">
                <form action="{{ route('admin.sales.customer-taggings.approve', $tagging) }}" method="POST">
                    @csrf
                    <textarea name="review_notes" rows="2" placeholder="Catatan approval (opsional)" class="w-full border rounded px-3 py-2 text-sm mb-2"></textarea>
                    <button class="w-full bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Approve &amp; Buat Customer</button>
                </form>
                <form action="{{ route('admin.sales.customer-taggings.reject', $tagging) }}" method="POST">
                    @csrf
                    <textarea name="review_notes" rows="2" placeholder="Alasan penolakan (opsional)" class="w-full border rounded px-3 py-2 text-sm mb-2"></textarea>
                    <button class="w-full bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Reject</button>
                </form>
            </div>
        @endif
    </div>
</x-admin-layout>
