<x-admin-layout>
    @php
        // Aturan yang sama dengan daftar DO: tanpa kendaraan/driver = serah langsung
        // (satu langkah Selesai); ada kendaraan/driver = diantar terpisah (Kirim, lalu Terkirim).
        $mode = $deliveryOrder->isDraft()
            ? ($deliveryOrder->isSeparateDelivery() ? 'dispatch' : 'complete')
            : ($deliveryOrder->isDispatched() ? 'deliver' : null);

        $primary = [
            'complete' => ['label' => 'Selesai', 'route' => 'admin.operations.delivery-orders.complete', 'confirm' => 'Tandai DO ini selesai (barang sudah diserahkan ke toko)?'],
            'dispatch' => ['label' => 'Kirim', 'route' => 'admin.operations.delivery-orders.dispatch', 'confirm' => 'Kirim DO ini sekarang?'],
            'deliver' => ['label' => 'Tandai Terkirim', 'route' => 'admin.operations.delivery-orders.deliver', 'confirm' => 'Tandai barang sudah sampai di toko?'],
        ][$mode] ?? null;

        $hint = [
            'complete' => 'Tanpa kendaraan/driver: barang diserahkan langsung oleh Sales ke toko. Klik Selesai bila sudah diserahkan.',
            'dispatch' => 'Kendaraan/driver sudah diisi: barang diantar terpisah. Klik Kirim, lalu tandai Terkirim setelah barang sampai.',
            'deliver' => 'Barang sedang diantar. Klik Tandai Terkirim setelah barang sampai di toko.',
        ][$mode] ?? null;

        $vehicle = $deliveryOrder->vehicle;
    @endphp

    <div class="frm-page is-medium">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Delivery Order {{ $deliveryOrder->code }}</h1>
                <p class="frm-sub">
                    <span class="frm-status {{ $deliveryOrder->statusTone() }}">{{ $deliveryOrder->statusLabel() }}</span>
                </p>
            </div>
            <a href="{{ route('admin.operations.delivery-orders.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        {{-- Notifikasi --}}
        @if (session('status'))
            <div class="frm-alert" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                <span class="frm-alert-text">{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">{{ session('error') }}</span>
            </div>
        @endif

        <div class="frm-stack">
            {{-- Informasi --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi</h2>
                </div>
                <dl class="frm-detail">
                    <div><dt>Sales Transaction</dt><dd>{{ $deliveryOrder->salesTransaction->code ?? '-' }}</dd></div>
                    <div><dt>Customer</dt><dd>{{ $deliveryOrder->salesTransaction->customer->name ?? '-' }}</dd></div>
                    <div><dt>Catatan</dt><dd>{{ $deliveryOrder->notes ?: '-' }}</dd></div>
                </dl>
            </section>

            {{-- Pengiriman --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Pengiriman</h2>
                </div>
                <dl class="frm-detail">
                    <div>
                        <dt>Metode</dt>
                        <dd>{{ $deliveryOrder->isSeparateDelivery() ? 'Diantar terpisah' : 'Serah langsung oleh Sales' }}</dd>
                    </div>
                    <div>
                        <dt>Kendaraan</dt>
                        <dd>{{ $vehicle ? $vehicle->name.' ('.$vehicle->plate_number.')' : '-' }}</dd>
                    </div>
                    <div><dt>Driver</dt><dd>{{ $deliveryOrder->driver->name ?? '-' }}</dd></div>
                    <div><dt>Rute</dt><dd>{{ $deliveryOrder->route->name ?? '-' }}</dd></div>
                    <div><dt>Jadwal</dt><dd>{{ $deliveryOrder->scheduled_date?->format('d/m/Y') ?? '-' }}</dd></div>
                </dl>
            </section>

            {{-- Riwayat waktu --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Riwayat</h2>
                </div>
                <dl class="frm-detail">
                    <div><dt>Dibuat</dt><dd class="frm-num">{{ $deliveryOrder->created_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
                    @if ($deliveryOrder->dispatched_at)
                        <div><dt>Dikirim</dt><dd class="frm-num">{{ $deliveryOrder->dispatched_at->format('d/m/Y H:i') }}</dd></div>
                    @endif
                    @if ($deliveryOrder->delivered_at)
                        <div><dt>Terkirim</dt><dd class="frm-num">{{ $deliveryOrder->delivered_at->format('d/m/Y H:i') }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Barang --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Barang</h2>
                    <span class="frm-count">{{ $deliveryOrder->items->count() }} produk</span>
                </div>
                @if ($deliveryOrder->items->count())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="is-num">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deliveryOrder->items as $line)
                                    <tr>
                                        <td>
                                            <span class="frm-line frm-name">{{ $line->product->name ?? '-' }}</span>
                                            <span class="frm-line">{{ $line->product->sku ?? '-' }}</span>
                                        </td>
                                        <td data-label="Quantity" class="is-num"><span class="frm-num is-strong">{{ number_format($line->quantity, 2) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="frm-empty">
                        <p class="frm-empty-title">Belum ada barang</p>
                        <p class="frm-empty-text">DO ini tidak memiliki item.</p>
                    </div>
                @endif
            </section>

            {{-- Tindakan --}}
            @can('operations.manage')
                @if ($primary)
                    <section class="panel">
                        <div class="panel-head">
                            <h2 class="panel-title">Tindakan</h2>
                        </div>
                        @if ($hint)
                            <div class="frm-panel-body">
                                <div class="frm-alert is-info" role="note">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    <span class="frm-alert-text">{{ $hint }}</span>
                                </div>
                            </div>
                        @endif
                        <div class="frm-panel-foot is-split">
                            {{-- Batalkan hanya untuk DO yang belum dikirim. --}}
                            @if ($deliveryOrder->isDraft())
                                <form action="{{ route('admin.operations.delivery-orders.cancel', $deliveryOrder) }}" method="POST"
                                      onsubmit="return confirm('Batalkan dokumen ini?')">
                                    @csrf
                                    <button type="submit" class="adm-btn adm-btn-ghost is-danger adm-btn-sm">Batalkan</button>
                                </form>
                            @else
                                <span></span>
                            @endif

                            <form action="{{ route($primary['route'], $deliveryOrder) }}" method="POST"
                                  onsubmit="return confirm(@js($primary['confirm']))">
                                @csrf
                                <button type="submit" data-action="{{ $mode }}" class="adm-btn adm-btn-primary adm-btn-sm">{{ $primary['label'] }}</button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan
        </div>
    </div>
</x-admin-layout>