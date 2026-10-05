<x-admin-layout>
    @php
        $canManage = \Illuminate\Support\Facades\Gate::allows('operations.manage');
        $dayCols = \App\Models\RouteCustomer::DAY_COLUMNS;
        $weekCols = \App\Models\RouteCustomer::WEEK_COLUMNS;
        $visitCols = $dayCols + $weekCols;               // kolom => label, urut: hari lalu minggu
        $tableCols = 2 + count($visitCols) + 1;          // kode, nama, kolom kunjungan, aksi
    @endphp

    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Edit Route</h1>
                <p class="frm-sub">
                    <span class="frm-code">{{ $item->code }}</span>
                    <span class="frm-name">{{ $item->name }}</span>
                </p>
            </div>

            <div class="frm-head-actions">
                <a href="{{ route('admin.operations.routes.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Kembali
                </a>
                <a href="{{ route('admin.operations.routes.customers.export', $item) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                    Download Data Rute
                </a>
                @if ($canManage)
                    <label class="adm-btn adm-btn-ghost adm-btn-sm is-file">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                        Upload Data Rute
                        <input type="file" id="import-file-input" class="frm-file-input" accept=".csv,.txt">
                    </label>
                @endif
            </div>
        </div>

        {{-- Form tersembunyi: Upload Data Rute (file dipilih lewat tombol di header, lalu disubmit otomatis)
             dan Hapus Route (dipicu dari footer panel Data Master). --}}
        @if ($canManage)
            <form id="import-form" action="{{ route('admin.operations.routes.customers.import', $item) }}" method="POST" enctype="multipart/form-data" hidden>
                @csrf
            </form>
            <form id="route-delete-form" action="{{ route('admin.operations.routes.destroy', $item) }}" method="POST" hidden
                  onsubmit="return confirm('Hapus route ini beserta seluruh data pelanggannya?')">
                @csrf
                @method('DELETE')
            </form>
        @endif

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
        @if (session('importWarnings'))
            <div class="frm-alert is-warn is-block" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                <div class="frm-alert-text">
                    <p class="frm-alert-title">Baris yang dilewati saat upload:</p>
                    <ul>
                        @foreach (session('importWarnings') as $w)
                            <li>{{ $w }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="frm-alert is-error is-block" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <div class="frm-alert-text">
                    <ul>
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="frm-stack">
            {{-- ===================== MASTER RUTE ===================== --}}
            <form id="route-form" method="POST" action="{{ route('admin.operations.routes.update', $item) }}" class="panel">
                @csrf
                @method('PUT')

                <div class="panel-head">
                    <h2 class="panel-title">Data Master Rute</h2>
                </div>

                <div class="frm-panel-body">
                    <div class="frm-grid">
                        <div class="frm-field">
                            <label class="frm-label" for="code">Kode Rute <span class="frm-req">*</span></label>
                            <input id="code" type="text" name="code" value="{{ old('code', $item->code) }}" @class(['frm-input', 'is-invalid' => $errors->has('code')])>
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="name">Nama Rute <span class="frm-req">*</span></label>
                            <input id="name" type="text" name="name" value="{{ old('name', $item->name) }}" @class(['frm-input', 'is-invalid' => $errors->has('name')])>
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="route_type">Jenis Rute <span class="frm-opt">(opsional)</span></label>
                            <input id="route_type" type="text" name="route_type" list="route-type-options" value="{{ old('route_type', $item->route_type) }}"
                                   placeholder="mis. Reguler, Canvassing..." @class(['frm-input', 'is-invalid' => $errors->has('route_type')])>
                            <datalist id="route-type-options">
                                @foreach ($routeTypes as $type)
                                    <option value="{{ $type }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="sales_id">Salesman <span class="frm-opt">(opsional)</span></label>
                            <select id="sales_id" name="sales_id" @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('sales_id')])>
                                <option value="">Tidak ditentukan</option>
                                @foreach ($salesList as $sales)
                                    <option value="{{ $sales->id }}" @selected(old('sales_id', $item->sales_id) == $sales->id)>{{ $sales->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="area">Area <span class="frm-opt">(opsional)</span></label>
                            <textarea id="area" name="area" rows="2" @class(['frm-input', 'is-area', 'is-invalid' => $errors->has('area')])>{{ old('area', $item->area) }}</textarea>
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="description">Keterangan <span class="frm-opt">(opsional)</span></label>
                            <textarea id="description" name="description" rows="2" @class(['frm-input', 'is-area', 'is-invalid' => $errors->has('description')])>{{ old('description', $item->description) }}</textarea>
                        </div>

                        <div class="frm-field is-full">
                            <label class="frm-switch">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))>
                                <span class="frm-switch-track"></span>
                                <span class="frm-switch-text">Aktif</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="frm-panel-foot is-split">
                    @if ($canManage)
                        <button type="submit" form="route-delete-form" class="adm-btn adm-btn-ghost is-danger adm-btn-sm">Hapus Route</button>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan</button>
                </div>
            </form>

            {{-- ===================== TAMBAH PELANGGAN ===================== --}}
            @if ($canManage)
                <form method="POST" action="{{ route('admin.operations.routes.customers.store', $item) }}" class="panel">
                    @csrf

                    <div class="panel-head">
                        <h2 class="panel-title">Tambah Pelanggan ke Rute</h2>
                    </div>

                    <div class="frm-panel-body">
                        <div class="frm-field">
                            <label class="frm-label" for="customer_id">Pelanggan <span class="frm-req">*</span></label>
                            <select id="customer_id" name="customer_id" required class="frm-input is-select">
                                <option value="">Pilih pelanggan</option>
                                @foreach ($availableCustomers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->code }} - {{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="frm-field">
                            <span class="frm-label">Hari Kunjungan</span>
                            <div class="frm-seg is-wrap">
                                @foreach ($dayCols as $col => $label)
                                    <label>
                                        <input type="checkbox" name="{{ $col }}" value="1">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="frm-field">
                            <span class="frm-label">Minggu Kunjungan</span>
                            <div class="frm-seg is-wrap">
                                @foreach ($weekCols as $col => $label)
                                    <label>
                                        <input type="checkbox" name="{{ $col }}" value="1">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @if ($availableCustomers->isEmpty())
                            <div class="frm-alert is-info" role="note">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                <span class="frm-alert-text">Semua pelanggan aktif sudah tergabung pada rute ini, atau belum ada pelanggan aktif.</span>
                            </div>
                        @endif
                    </div>

                    <div class="frm-panel-foot">
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm" @disabled($availableCustomers->isEmpty())>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                            Tambah
                        </button>
                    </div>
                </form>
            @endif

            {{-- Form kosong per-baris untuk update & hapus pelanggan (dihubungkan via atribut form="..." pada input/tombol di dalam tabel). --}}
            @if ($canManage)
                @foreach ($routeCustomers as $rc)
                    <form id="rc-update-{{ $rc->id }}" method="POST" action="{{ route('admin.operations.routes.customers.update', [$item, $rc]) }}" hidden>
                        @csrf
                        @method('PUT')
                    </form>
                    <form id="rc-delete-{{ $rc->id }}" method="POST" action="{{ route('admin.operations.routes.customers.destroy', [$item, $rc]) }}" hidden>
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif

            {{-- ===================== TABEL DETAIL PELANGGAN ===================== --}}
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Pelanggan pada Rute Ini</h2>
                        <p class="frm-meta">{{ $routeCustomers->count() }} pelanggan</p>
                    </div>
                    <button type="button" id="reset-filter-btn" class="adm-btn adm-btn-ghost adm-btn-sm">Reset Filter</button>
                </div>

                <div class="frm-items-wrap is-flush">
                    <table class="frm-items-table is-wide" id="route-customer-table">
                        <thead>
                            <tr>
                                <th>Kode Pelanggan</th>
                                <th>Nama Pelanggan</th>
                                @foreach ($visitCols as $col => $label)
                                    <th class="is-center">{{ $label }}</th>
                                @endforeach
                                <th class="is-end">Aksi</th>
                            </tr>
                            {{-- Baris filter per kolom --}}
                            <tr id="filter-row">
                                <th>
                                    <input type="text" data-filter="code" placeholder="Cari kode..." aria-label="Filter kode pelanggan" class="frm-input is-mini">
                                </th>
                                <th>
                                    <input type="text" data-filter="name" placeholder="Cari nama..." aria-label="Filter nama pelanggan" class="frm-input is-mini">
                                </th>
                                @foreach ($visitCols as $col => $label)
                                    <th>
                                        <select data-filter="{{ $col }}" aria-label="Filter {{ $label }}" class="frm-input is-select is-mini">
                                            <option value="">Semua</option>
                                            <option value="1">Ya</option>
                                            <option value="0">Tidak</option>
                                        </select>
                                    </th>
                                @endforeach
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($routeCustomers as $rc)
                                <tr class="route-customer-row"
                                    data-code="{{ strtolower($rc->customer->code) }}"
                                    data-name="{{ strtolower($rc->customer->name) }}"
                                    @foreach (array_keys($visitCols) as $col) data-{{ $col }}="{{ $rc->{$col} ? 1 : 0 }}" @endforeach>
                                    <td><span class="frm-code">{{ $rc->customer->code }}</span></td>
                                    <td><span class="frm-name">{{ $rc->customer->name }}</span></td>
                                    @foreach (array_keys($visitCols) as $col)
                                        <td class="is-center">
                                            <input type="checkbox" class="frm-check" form="rc-update-{{ $rc->id }}" name="{{ $col }}" value="1"
                                                   aria-label="{{ $visitCols[$col] }} - {{ $rc->customer->name }}"
                                                   @checked($rc->{$col}) @disabled(! $canManage)>
                                        </td>
                                    @endforeach
                                    <td class="is-end">
                                        @if ($canManage)
                                            <div class="frm-actions">
                                                <button type="submit" form="rc-update-{{ $rc->id }}" class="frm-icon-btn" title="Simpan perubahan" aria-label="Simpan {{ $rc->customer->name }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                </button>
                                                <button type="submit" form="rc-delete-{{ $rc->id }}" class="frm-icon-btn is-danger" title="Keluarkan dari rute" aria-label="Keluarkan {{ $rc->customer->name }} dari rute"
                                                        onclick="return confirm('Keluarkan pelanggan ini dari rute?')">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr id="empty-row"><td colspan="{{ $tableCols }}" class="frm-items-empty">Belum ada pelanggan pada rute ini.</td></tr>
                            @endforelse
                            <tr id="no-match-row" hidden>
                                <td colspan="{{ $tableCols }}" class="frm-items-empty">Tidak ada pelanggan yang cocok dengan filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Upload Data Rute: klik tombol di header membuka file picker,
        // begitu file dipilih langsung submit form import.
        (function () {
            const fileInput = document.getElementById('import-file-input');
            const importForm = document.getElementById('import-form');
            if (!fileInput || !importForm) return;

            fileInput.addEventListener('change', function () {
                if (!this.files || this.files.length === 0) return;
                // Pindahkan langsung elemen file input (beserta file yang
                // sudah dipilih user) ke dalam form import, lalu submit.
                this.name = 'file';
                importForm.appendChild(this);
                importForm.submit();
            });
        })();

        // Filtering per kolom + penanda baris yang diubah pada tabel detail pelanggan.
        (function () {
            const table = document.getElementById('route-customer-table');
            if (!table) return;

            const filterInputs = table.querySelectorAll('#filter-row [data-filter]');
            const rows = table.querySelectorAll('.route-customer-row');
            const noMatchRow = document.getElementById('no-match-row');
            const resetBtn = document.getElementById('reset-filter-btn');

            function applyFilters() {
                const filters = {};
                filterInputs.forEach(el => {
                    const key = el.dataset.filter;
                    const val = el.value.trim().toLowerCase();
                    if (val !== '') filters[key] = val;
                });

                let visibleCount = 0;

                rows.forEach(row => {
                    let match = true;
                    for (const key in filters) {
                        // Catatan: data-visit_mon -> dataset.visit_mon (underscore
                        // TIDAK di-camelCase oleh browser, beda dari data-visit-mon).
                        const rowVal = (row.dataset[key] || '').toLowerCase();
                        if (key === 'code' || key === 'name') {
                            if (!rowVal.includes(filters[key])) { match = false; break; }
                        } else {
                            if (rowVal !== filters[key]) { match = false; break; }
                        }
                    }
                    row.style.display = match ? '' : 'none';
                    if (match) visibleCount++;
                });

                if (noMatchRow) {
                    const hasFilters = Object.keys(filters).length > 0;
                    noMatchRow.hidden = !(hasFilters && visibleCount === 0);
                }
            }

            filterInputs.forEach(el => {
                el.addEventListener('input', applyFilters);
                el.addEventListener('change', applyFilters);
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    filterInputs.forEach(el => { el.value = ''; });
                    applyFilters();
                });
            }

            // Tandai baris yang sudah diubah tapi belum disimpan (tiap baris punya tombol simpan sendiri).
            table.addEventListener('change', function (e) {
                const box = e.target;
                if (box.matches('input[type="checkbox"][form]')) {
                    const row = box.closest('tr');
                    if (row) row.classList.add('is-dirty');
                }
            });
        })();
    </script>
</x-admin-layout>