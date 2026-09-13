<x-sales-layout>
    <x-slot name="header">Kunjungan</x-slot>

    @if (session('status'))
        <div class="mb-3 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($ongoing)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-yellow-800">Sedang berkunjung ke:</p>
            <p class="text-lg font-semibold">{{ $ongoing->customer->name ?? '-' }}</p>
            <p class="text-xs text-gray-500 mb-3">Check-in: {{ $ongoing->check_in_at->format('d M Y H:i') }}</p>

            <form method="POST" action="{{ route('sales.visits.check-out', $ongoing) }}" onsubmit="return isiLokasiCheckout(event)">
                @csrf
                <input type="hidden" name="latitude" id="co-latitude">
                <input type="hidden" name="longitude" id="co-longitude">
                <textarea name="notes" rows="2" placeholder="Catatan kunjungan (opsional)" class="w-full border rounded px-3 py-2 text-sm mb-2"></textarea>
                <button type="submit" class="w-full bg-red-600 text-white px-3 py-2 rounded text-sm">Check-out</button>
            </form>
        </div>
    @else
        <a href="{{ route('sales.visits.create') }}" class="block text-center bg-indigo-600 text-white px-3 py-2 rounded text-sm mb-4">+ Check-in Kunjungan Baru</a>
    @endif

    <p class="text-sm text-gray-600 mb-2">Riwayat kunjungan selesai</p>
    <div class="space-y-2">
        @forelse ($items as $item)
            <div class="bg-white rounded-lg shadow p-3 text-sm">
                <p class="font-medium">{{ $item->customer->name ?? '-' }}</p>
                <p class="text-xs text-gray-500">{{ $item->check_in_at->format('d M Y H:i') }} - {{ $item->check_out_at?->format('H:i') }}</p>
            </div>
        @empty
            <p class="text-center text-gray-500 text-sm py-6">Belum ada riwayat kunjungan.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    <script>
        function isiLokasiCheckout(e) {
            e.preventDefault();
            const form = e.target;
            const done = () => form.submit();
            if (! navigator.geolocation) return done();
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('co-latitude').value = pos.coords.latitude;
                document.getElementById('co-longitude').value = pos.coords.longitude;
                done();
            }, done);
            return false;
        }
    </script>
</x-sales-layout>
