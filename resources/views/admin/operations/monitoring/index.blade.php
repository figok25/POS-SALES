<x-admin-layout>
    @php
        // Tiga kolom papan: koleksi, judul, kelas pill, teks kosong, dan baris info ketiga per kartu.
        $columns = [
            [
                'title' => 'Menunggu',
                'tone' => 'is-warn',
                'items' => $draft,
                'empty' => 'Tidak ada.',
                'meta' => fn ($do) => $do->scheduled_date?->format('d/m/Y') ?? 'Belum dijadwalkan',
            ],
            [
                'title' => 'Dalam Pengiriman',
                'tone' => 'is-info',
                'items' => $dispatched,
                'empty' => 'Tidak ada.',
                'meta' => fn ($do) => ($do->vehicle->name ?? '-').' · '.($do->driver->name ?? '-'),
            ],
            [
                'title' => 'Terkirim Hari Ini',
                'tone' => 'is-on',
                'items' => $deliveredToday,
                'empty' => 'Belum ada.',
                'meta' => fn ($do) => $do->delivered_at ? 'Pukul '.$do->delivered_at->format('H:i') : '-',
            ],
        ];
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Monitoring Pengiriman</h1>
            <p class="frm-sub">Ringkasan Delivery Order yang menunggu, sedang diantar, dan sudah terkirim hari ini.</p>
        </div>
        <a href="{{ route('admin.operations.delivery-orders.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Semua DO</a>
    </div>

    <div class="frm-board">
        @foreach ($columns as $col)
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">{{ $col['title'] }}</h2>
                    <span class="frm-status {{ $col['tone'] }}">{{ $col['items']->count() }}</span>
                </div>

                @if ($col['items']->count())
                    <ul class="row-list frm-board-list">
                        @foreach ($col['items'] as $do)
                            <li>
                                <a href="{{ route('admin.operations.delivery-orders.show', $do) }}" class="row-item frm-board-item">
                                    <span class="row-main">
                                        <span class="row-code">{{ $do->code }}</span>
                                        <span class="frm-line frm-name">{{ $do->salesTransaction->customer->name ?? '-' }}</span>
                                        <span class="row-sub">{{ $col['meta']($do) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="panel-empty">{{ $col['empty'] }}</p>
                @endif
            </section>
        @endforeach
    </div>
</x-admin-layout>