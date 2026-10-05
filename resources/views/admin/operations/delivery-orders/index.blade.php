<x-admin-layout>
    @php
        $canManage = \Illuminate\Support\Facades\Gate::allows('operations.manage');
        $isFiltered = filled($status);

        // Tab status: [nilai query, label]
        $tabs = [
            '' => 'Semua',
            'draft' => 'Menunggu',
            'dispatched' => 'Dalam Pengiriman',
            'delivered' => 'Terkirim',
            'cancelled' => 'Batal',
        ];

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
        @if ($canManage)
            <a href="{{ route('admin.operations.delivery-orders.create') }}" class="adm-btn adm-btn-primary adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                DO Manual
            </a>
        @endif
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert="auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" data-alert-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="frm-alert is-error" role="alert" data-alert>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">{{ session('error') }}</span>
            <button type="button" class="frm-alert-close" data-alert-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif
    @if ($errors->any())
        <div class="frm-alert is-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="panel">
        {{-- Tab status --}}
        <nav class="frm-tabs" aria-label="Filter status">
            @foreach ($tabs as $value => $label)
                @php $active = ($status ?? '') === (string) $value; @endphp
                <a href="{{ route('admin.operations.delivery-orders.index', $value === '' ? [] : ['status' => $value]) }}"
                   class="frm-tab {{ $active ? 'is-active' : '' }}"
                   @if ($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} DO</span>
        </nav>

        <form id="bulk-form" method="POST"
              action="{{ route('admin.operations.delivery-orders.bulk-complete') }}"
              data-complete-url="{{ route('admin.operations.delivery-orders.bulk-complete') }}"
              data-dispatch-url="{{ route('admin.operations.delivery-orders.bulk-dispatch') }}">
            @csrf
            <input type="hidden" name="select_all_draft" id="select-all-flag" value="0">

            @if ($canManage)
                {{-- Panel aksi massal: baru tampil kalau ada DO yang dicentang. --}}
                <div id="bulk-bar" hidden>
                    <div class="frm-bulkbar">
                        @if ($openCount > 0)
                            <label class="frm-bulkbar-check">
                                <input type="checkbox" id="select-all-everywhere">
                                Pilih semua {{ number_format($openCount, 0, ',', '.') }} DO yang belum Terkirim (termasuk di halaman lain)
                            </label>
                        @endif
                        <span class="frm-bulkbar-info"><span id="bulk-count">0</span> DO dipilih</span>
                    </div>

                    <div class="frm-panel-body">
                        <div class="frm-grid">
                            <div class="frm-field">
                                <label class="frm-label" for="bulk-vehicle">Kendaraan <span class="frm-opt">(opsional)</span></label>
                                <select id="bulk-vehicle" name="vehicle_id" class="frm-input is-select">
                                    <option value="">Tanpa kendaraan</option>
                                    @foreach ($vehicles ?? [] as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="bulk-driver">Driver <span class="frm-opt">(opsional)</span></label>
                                <select id="bulk-driver" name="driver_id" class="frm-input is-select">
                                    <option value="">Tanpa driver</option>
                                    @foreach ($drivers ?? [] as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="bulk-route">Rute <span class="frm-opt">(opsional)</span></label>
                                <select id="bulk-route" name="route_id" class="frm-input is-select">
                                    <option value="">Tanpa rute</option>
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

                        <div id="bulk-hint-direct">
                            <div class="frm-alert is-info" role="note">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                <span class="frm-alert-text">Kendaraan/Driver kosong = barang diserahkan langsung oleh Sales ke toko. Tombol: <strong>Selesai</strong>.</span>
                            </div>
                        </div>
                        <div id="bulk-hint-send" hidden>
                            <div class="frm-alert is-info" role="note">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                <span class="frm-alert-text">Kendaraan/Driver diisi = barang diantar terpisah. Tombol: <strong>Kirim</strong>; setelah barang sampai, tandai <strong>Terkirim</strong>.</span>
                            </div>
                        </div>
                    </div>

                    <div class="frm-panel-foot is-split">
                        <button type="button" id="bulk-clear" class="adm-btn adm-btn-ghost adm-btn-sm">Batal Pilih</button>
                        <button type="submit" id="bulk-submit" class="adm-btn adm-btn-primary adm-btn-sm">Selesai</button>
                    </div>
                </div>
            @endif

            @if ($items->count())
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                @if ($canManage)
                                    <th class="frm-cell-check">
                                        <input type="checkbox" id="check-all" class="frm-check" aria-label="Pilih semua di halaman ini">
                                    </th>
                                @endif
                                <th>Kode</th>
                                <th>Customer</th>
                                <th>Pengiriman</th>
                                <th>Jadwal</th>
                                <th>Status</th>
                                @if ($canManage)
                                    <th class="is-end">Aksi</th>
                                @endif
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
                                <tr @class(['has-check' => $canManage && $mode])>
                                    @if ($canManage)
                                        <td class="frm-cell-check">
                                            @if ($mode)
                                                <input type="checkbox" name="delivery_order_ids[]" value="{{ $item->id }}" class="frm-check do-checkbox" aria-label="Pilih {{ $item->code }}">
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <a href="{{ route('admin.operations.delivery-orders.show', $item) }}" class="frm-code">{{ $item->code }}</a>
                                    </td>
                                    <td data-label="Customer"><span class="frm-name">{{ $item->salesTransaction->customer->name ?? '-' }}</span></td>
                                    <td data-label="Pengiriman">
                                        @if ($item->isSeparateDelivery())
                                            <span class="frm-line">{{ $item->vehicle->name ?? '-' }}</span>
                                            @if ($item->driver)
                                                <span class="frm-line">{{ $item->driver->name }}</span>
                                            @endif
                                        @else
                                            <span class="frm-dash">Serah langsung</span>
                                        @endif
                                    </td>
                                    <td data-label="Jadwal" class="frm-nowrap">{{ $item->scheduled_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="frm-cell-status">
                                        <span class="frm-status {{ $item->statusTone() }}">{{ $item->statusLabel() }}</span>
                                    </td>
                                    @if ($canManage)
                                        <td class="is-end">
                                            @if ($action)
                                                <button type="submit" data-row-action data-action="{{ $mode }}"
                                                        formaction="{{ route($action['route'], $item) }}"
                                                        onclick="return confirm(@js($action['confirm']))"
                                                        class="adm-btn adm-btn-primary adm-btn-sm">{{ $action['label'] }}</button>
                                            @endif
                                        </td>
                                    @endif
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
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    </div>
                    <p class="frm-empty-title">{{ $isFiltered ? 'Tidak ada hasil' : 'Belum ada Delivery Order' }}</p>
                    <p class="frm-empty-text">{{ $isFiltered ? 'Tidak ada DO dengan status ini.' : 'DO muncul otomatis setiap ada transaksi penjualan yang selesai.' }}</p>
                    @if ($isFiltered)
                        <a href="{{ route('admin.operations.delivery-orders.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Lihat semua DO</a>
                    @endif
                </div>
            @endif
        </form>
    </section>

    <script>
        (function () {
            // Notifikasi: tombol tutup + hilang sendiri (jalan juga untuk user tanpa hak kelola)
            document.querySelectorAll('[data-alert]').forEach(function (el) {
                function dismiss() {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                }
                var close = el.querySelector('[data-alert-close]');
                if (close) close.addEventListener('click', dismiss);
                if (el.getAttribute('data-alert') === 'auto') setTimeout(dismiss, 6000);
            });

            var form = document.getElementById('bulk-form');
            var bar = document.getElementById('bulk-bar');
            if (!form || !bar) return;

            var checkAll = document.getElementById('check-all');
            var allFlag = document.getElementById('select-all-flag');
            var allEverywhere = document.getElementById('select-all-everywhere');
            var countEl = document.getElementById('bulk-count');
            var submitBtn = document.getElementById('bulk-submit');
            var clearBtn = document.getElementById('bulk-clear');
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

                bar.hidden = !(checked > 0 || everywhere);
                countEl.textContent = everywhere ? ('Semua ' + openCount) : checked;

                var m = mode();
                submitBtn.textContent = m === 'dispatch' ? 'Kirim' : 'Selesai';
                if (m === 'dispatch') {
                    submitBtn.setAttribute('formaction', form.dataset.dispatchUrl);
                } else {
                    submitBtn.removeAttribute('formaction');
                }
                hintDirect.hidden = m === 'dispatch';
                hintSend.hidden = m !== 'dispatch';

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

            // Batal Pilih: kosongkan centang dan isian panel
            clearBtn.addEventListener('click', function () {
                allFlag.value = '0';
                if (allEverywhere) allEverywhere.checked = false;
                rows().forEach(function (cb) { cb.checked = false; cb.disabled = false; });
                if (checkAll) { checkAll.checked = false; checkAll.disabled = false; checkAll.indeterminate = false; }
                bar.querySelectorAll('select, input[type="date"]').forEach(function (el) { el.value = ''; });
                refresh();
            });

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
        })();
    </script>
</x-admin-layout>