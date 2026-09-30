@php
    $labels = [
        'draft' => ['Menunggu Penugasan Admin', 'gray'],
        'applied' => ['Dokumen Sedang Disiapkan', 'gray'],
        'document_available' => ['Dokumen Tersedia - Verifikasi Stock', 'amber'],
        'stock_verification' => ['Verifikasi Stock Berjalan', 'amber'],
        'ready_to_work' => ['Siap Bekerja - Mulai Kerja', 'blue'],
        'working' => ['Sedang Bekerja', 'green'],
        'completed' => ['Tugas Selesai', 'gray'],
    ];
    [$label, $tone] = $task ? ($labels[$task->status] ?? ['-', 'gray']) : ['Belum Ada Tugas Hari Ini', 'red'];
@endphp

<a href="{{ route('sales.task.show') }}" class="sls-banner tone-{{ $tone }}">
    <strong>Tugas:</strong> {{ $label }}
    @unless (request()->routeIs('sales.task.show'))
        <span class="sls-banner-arrow">Lihat &rarr;</span>
    @endunless
</a>
