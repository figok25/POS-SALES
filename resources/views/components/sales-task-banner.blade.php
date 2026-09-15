@php
    $labels = [
        'draft' => ['Menunggu Penugasan Admin', 'bg-gray-100 text-gray-600'],
        'applied' => ['Dokumen Sedang Disiapkan', 'bg-gray-100 text-gray-600'],
        'document_available' => ['Dokumen Tersedia - Verifikasi Stock', 'bg-yellow-100 text-yellow-700'],
        'stock_verification' => ['Verifikasi Stock Berjalan', 'bg-yellow-100 text-yellow-700'],
        'ready_to_work' => ['Siap Bekerja - Mulai Kerja', 'bg-blue-100 text-blue-700'],
        'working' => ['Sedang Bekerja', 'bg-green-100 text-green-700'],
        'completed' => ['Tugas Selesai', 'bg-gray-100 text-gray-600'],
    ];
    [$label, $classes] = $task ? ($labels[$task->status] ?? ['-', 'bg-gray-100 text-gray-600']) : ['Belum Ada Tugas Hari Ini', 'bg-red-100 text-red-700'];
@endphp

<a href="{{ route('sales.task.show') }}" class="block px-4 py-1.5 text-xs {{ $classes }} {{ request()->routeIs('sales.task.show') ? '' : 'hover:opacity-80' }}">
    <span class="font-medium">Tugas:</span> {{ $label }}
    @unless (request()->routeIs('sales.task.show'))
        <span class="float-right">Lihat &rarr;</span>
    @endunless
</a>
