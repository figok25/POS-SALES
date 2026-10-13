{{-- Filter riwayat per hari. $filterDate = Carbon (satu hari) atau null (semua tanggal);
     $filterRoute = nama route halaman riwayat. --}}
<form method="GET" action="{{ route($filterRoute) }}" class="bg-white rounded-lg shadow p-3 mb-3">
    <div class="flex items-center gap-2">
        <a href="{{ $filterDate ? route($filterRoute, ['date' => $filterDate->copy()->subDay()->toDateString()]) : '#' }}"
           class="px-2.5 py-1.5 border rounded text-sm text-gray-600 {{ $filterDate ? '' : 'opacity-40 pointer-events-none' }}" aria-label="Hari sebelumnya">&lsaquo;</a>
        <input type="date" name="date" value="{{ $filterDate?->toDateString() }}" max="{{ now()->toDateString() }}"
               onchange="this.form.submit()" class="flex-1 border rounded px-2 py-1.5 text-sm" aria-label="Pilih tanggal">
        <a href="{{ $filterDate && ! $filterDate->isToday() ? route($filterRoute, ['date' => $filterDate->copy()->addDay()->toDateString()]) : '#' }}"
           class="px-2.5 py-1.5 border rounded text-sm text-gray-600 {{ $filterDate && ! $filterDate->isToday() ? '' : 'opacity-40 pointer-events-none' }}" aria-label="Hari berikutnya">&rsaquo;</a>
    </div>
    <div class="flex items-center justify-between mt-2 text-xs">
        <span class="text-gray-500">{{ $filterDate ? $filterDate->translatedFormat('l, d F Y') : 'Menampilkan semua tanggal' }}</span>
        <span class="flex gap-3">
            <a href="{{ route($filterRoute) }}" class="text-indigo-600">Hari ini</a>
            <a href="{{ route($filterRoute, ['date' => 'all']) }}" class="text-indigo-600">Semua</a>
        </span>
    </div>
</form>
