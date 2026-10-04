<x-admin-layout>
    @php
        $isFiltered = filled($status);

        // Satu tombol per baris, sesuai status:
        //  Menunggu + tanpa kendaraan/driver -> Selesai  (serah langsung ke toko)
        //  Menunggu + ada kendaraan/driver   -> Kirim    (diantar terpisah)
        //  Dalam Pengiriman                  -> Terkirim
        $rowActions = [
            'complete' => ['label' => 'Selesai', 'route' => 'admin.operations.delivery-orders.complete', 'confirm' => 'Tandai DO ini selesai (barang sudah diserahkan ke toko)?'],
            'dispatch' => ['label' => 'Kirim', 'route' => 'admin.operations.delivery-orders.dispatch', 'confirm' => 'Kirim DO ini sekarang?'],
            'deliver' => ['label' => 'Terkirim', 'route' => 'admin.operations.delivery-orders.deliver', 'confirm' => 'Tandai barang sudah sampai di toko?'],
        ];
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Delivery Order</h1>
            <p class="frm-sub">
                Catatan penyerahan barang ke toko. DO dibuat otomatis setiap transaksi selesai.
                Barang yang diserahkan langsung oleh Sales cukup klik <strong>Selesai</strong>;
                isi Kendaraan/Driver hanya bila barang diantar terpisah.
            </p>
        </div>
        @can('operations.manage')
            <a href="{{ route('admin.operations.delivery-orders.create') }}" class="adm-btn adm-btn-ghost adm-btn-sm">+ DO Manual</a>
        @endcan
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert="auto">
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
    @if ($errors->any())
        <div class="frm-alert is-error" role="alert">
            <span class="frm-alert-text">{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="panel">
        {{-- Filter --}}
        <form method="GET" class="frm-toolbar">
            <select name="status" class="frm-input is-select is-filter" aria-label="Filter status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="draft" @selected($status === 'draft')>Menunggu</option>
                <option value="dispatched" @selected($status === 'dispatched')>Dalam Pengiriman</option>
                <option value="delivered" @selected($status === 'delivered')>Terkirim</option>
                <option value="cancelled" @selected($status === 'cancelled')>Batal</option>
            </select>

            <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Filter</button></noscript>

            @if ($isFiltered)
                <a href="{{ route('admin.operations.delivery-orders.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
            @endif

            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} DO</span>
        </form>

        <form id="bulk-form" method="POST"
              action="{{ route('admin.operations.delivery-orders.bulk-complete') }}"
              data-complete-url="{{ route('admin.operations.delivery-orders.bulk-complete') }}"
              data-dispatch-url="{{ route('admin.operations.delivery-orders.bulk-dispatch') }}">
            @csrf
            <input type="hidden" name="select_all_draft" id="select-all-flag" value="0">

            @can('operations.manage')
                {{-- Panel aksi massal: baru tampil kalau ada DO yang dicentang. --}}
                <div id="bulk-bar" class="frm-panel-body" style="display:none; border-bottom:1px solid var(--adm-border, #e5e7eb)">
                    <p style="margin:0 0 .75rem"><strong id="bulk-count">0</strong> DO dipilih.</p>

                    <div class="frm-grid">
                        <div class="frm-field">
                            <label class="frm-label" for="bulk-vehicle">Kendaraan <span class="frm-opt">(opsional)</span></label>
                            <select id="bulk-vehicle" name="vehicle_id" class="frm-input is-select">
                                <option value="">-</option>
                                @foreach ($vehicles ?? [] as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="bulk-driver">Driver <span class="frm-opt">(opsional)</span></label>
                            <select id="bulk-driver" name="driver_id" class="frm-input is-select">
                                <option value="">-</option>
                                @foreach ($drivers ?? [] as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="bulk-route">Rute <span class="frm-opt">(opsional)</span></label>
                            <select id="bulk-route" name="route_id" class="frm-input is-select">
                                <option value="">-</option>
                                @foreach ($routes ?? [] as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="frm-field">
                            <label class="frm-label" for="bulk-date">Jadwal <span class="frm-opt">(opsional)</span></label>
                            <input id="bulk-date" type="date" name="scheduled_date" class="frm-input">
                        </div>
                    </div>

                    <p class="frm-hint" id="bulk-hint-direct">Kendaraan/Driver kosong = barang diserahkan langsung oleh Sales ke toko. Tombol: <strong>Selesai</strong>.</p>
                    <p class="frm-hint" id="bulk-hint-send" style="display:none">Kendaraan/Driver diisi = barang diantar terpisah. Tombol: <strong>Kirim</strong>; setelah barang sampai, tandai <strong>Terkirim</strong>.</p>

                    @if ($openCount > 0)
                        <label class="frm-hint" style="display:flex; gap:.5rem; align-items:center; margin:.5rem 0">
                            <input type="checkbox" id="select-all-everywhere">
                            Pilih SEMUA {{ $openCount }} DO yang belum Terkirim (termasuk yang tidak tampil di halaman ini)
                        </label>
                    @endif

                    <button type="submit" id="bulk-submit" class="adm-btn adm-btn-primary adm-btn-sm">Selesai</button>
                </div>
            @endcan

            @if ($items->count())
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                @can('operations.manage')
                                    <th style="width:2rem"><input type="checkbox" id="check-all" aria-label="Pilih semua di halaman ini"></th>
                                @endcan
                                <th>Kode</th>
                                <th>Customer</th>
                                <th>Pengiriman</th>
                                <th>Jadwal</th>
                                <th>Status</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $mode = $item->isDraft()
                                        ? ($item->isSeparateDelivery() ? 'dispatch' : 'complete')
                                        : ($item->isDispatched() ? 'deliver' : null);
                                    $action = $mode ? $rowActions[$mode] : null;
                                @endphp
                                <tr>
                                    @can('operations.manage')
                                        <td>
                                            @if ($mode)
                                                <input type="checkbox" name="delivery_order_ids[]" value="{{ $item->id }}" class="do-checkbox" aria-label="Pilih {{ $item->code }}">
                                            @endif
                                        </td>
                                    @endcan
                                    <td><a href="{{ route('admin.operations.delivery-orders.show', $item) }}" class="frm-code">{{ $item->code }}</a></td>
                                    <td data-label="Customer"><span class="frm-name">{{ $item->salesTransaction->customer->name ?? '-' }}</span></td>
                                    <td data-label="Pengiriman">
                                        @if ($item->isSeparateDelivery())
                                            {{ collect([$item->vehicle->name ?? null, $item->driver->name ?? null])->filter()->implode(' / ') }}
                                        @else
                                            <span class="frm-dash">Serah langsung</span>
                                        @endif
                                    </td>
                                    <td data-label="Jadwal" class="frm-nowrap">{{ $item->scheduled_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="frm-cell-status">
                                        <span class="frm-status {{ $item->statusTone() }}">{{ $item->statusLabel() }}</span>
                                    </td>
                                    <td class="is-end">
                                        @can('operations.manage')
                                            @if ($action)
                                                <button type="submit" data-row-action data-action="{{ $mode }}"
                                                        formaction="{{ route($action['route'], $item) }}"
                                                        onclick="return confirm(@js($action['confirm']))"
                                                        class="adm-btn adm-btn-primary adm-btn-sm">{{ $action['label'] }}</button>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
                @endif
            @else
                <div class="frm-empty">
                    <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada Delivery Order' }}</p>
                    <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada DO dengan status ini.' : 'DO muncul otomatis setiap ada transaksi penjualan yang selesai.' }}</p>
                </div>
            @endif
        </form>
    </section>

    <script>
        (function () {
            var form = document.getElementById('bulk-form');
            var bar = document.getElementById('bulk-bar');
            if (!form || !bar) return;

            var checkAll = document.getElementById('check-all');
            var allFlag = document.getElementById('select-all-flag');
            var allEverywhere = document.getElementById('select-all-everywhere');
            var countEl = document.getElementById('bulk-count');
            var submitBtn = document.getElementById('bulk-submit');
            var vehicle = document.getElementById('bulk-vehicle');
            var driver = document.getElementById('bulk-driver');
            var hintDirect = document.getElementById('bulk-hint-direct');
            var hintSend = document.getElementById('bulk-hint-send');
            var openCount = {{ (int) $openCount }};

            function rows() { return Array.prototype.slice.call(document.querySelectorAll('.do-checkbox')); }

            // Kendaraan/Driver diisi -> diantar terpisah (Kirim). Kosong -> serah langsung (Selesai).
            function mode() { return (vehicle.value || driver.value) ? 'dispatch' : 'complete'; }

            function refresh() {
                var boxes = rows();
                var checked = boxes.filter(function (cb) { return cb.checked; }).length;
                var everywhere = allFlag.value === '1';

                bar.style.display = (checked > 0 || everywhere) ? '' : 'none';
                countEl.textContent = everywhere ? ('Semua (' + openCount + ')') : checked;

                var m = mode();
                submitBtn.textContent = m === 'dispatch' ? 'Kirim' : 'Selesai';
                if (m === 'dispatch') {
                    submitBtn.setAttribute('formaction', form.dataset.dispatchUrl);
                } else {
                    submitBtn.removeAttribute('formaction');
                }
                hintDirect.style.display = m === 'dispatch' ? 'none' : '';
                hintSend.style.display = m === 'dispatch' ? '' : 'none';

                if (checkAll && !checkAll.disabled) {
                    checkAll.checked = boxes.length > 0 && checked === boxes.length;
                    checkAll.indeterminate = checked > 0 && checked < boxes.length;
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    rows().forEach(function (cb) { cb.checked = checkAll.checked; });
                    refresh();
                });
            }

            document.addEventListener('change', function (e) {
                if (e.target.classList && e.target.classList.contains('do-checkbox')) refresh();
            });

            [vehicle, driver].forEach(function (el) { el.addEventListener('change', refresh); });

            if (allEverywhere) {
                allEverywhere.addEventListener('change', function () {
                    allFlag.value = allEverywhere.checked ? '1' : '0';
                    rows().forEach(function (cb) { cb.checked = allEverywhere.checked; cb.disabled = allEverywhere.checked; });
                    if (checkAll) { checkAll.checked = allEverywhere.checked; checkAll.disabled = allEverywhere.checked; checkAll.indeterminate = false; }
                    refresh();
                });
            }

            form.addEventListener('submit', function (e) {
                // Tombol per baris tidak butuh centang tabel.
                if (e.submitter && e.submitter.hasAttribute('data-row-action')) return;

                var everywhere = allFlag.value === '1';
                var anyChecked = rows().some(function (cb) { return cb.checked; });

                if (!everywhere && !anyChecked) {
                    e.preventDefault();
                    alert('Pilih minimal 1 DO dulu.');
                    return;
                }
                if (mode() === 'complete' && !confirm('Tandai DO terpilih selesai (barang sudah diserahkan ke toko)? Tidak bisa dibatalkan.')) {
                    e.preventDefault();
                }
            });

            refresh();

            // Pesan sukses hilang sendiri
            document.querySelectorAll('[data-alert="auto"]').forEach(function (el) {
                setTimeout(function () { el.remove(); }, 6000);
            });
        })();
    </script>
</x-admin-layout>
