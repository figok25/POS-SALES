<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h1 class="text-xl font-semibold">Edit Route: {{ $item->code }} - {{ $item->name }}</h1>

            {{-- Toolbar Aksi Utama: Refresh, Simpan, Hapus, Upload Data Rute, Download Data Rute --}}
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="location.reload()" class="bg-gray-200 px-3 py-2 rounded text-sm hover:bg-gray-300">&#8635; Refresh</button>

                <button type="submit" form="route-form" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>

                @can('operations.manage')
                    <form action="{{ route('admin.operations.routes.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus route ini beserta seluruh data pelanggannya?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700">Hapus</button>
                    </form>

                    <label class="bg-gray-200 px-3 py-2 rounded text-sm hover:bg-gray-300 cursor-pointer">
                        Upload Data Rute
                        <input type="file" id="import-file-input" accept=".csv,.txt" class="hidden">
                    </label>
                @endcan

                <a href="{{ route('admin.operations.routes.customers.export', $item) }}" class="bg-gray-200 px-3 py-2 rounded text-sm hover:bg-gray-300">Download Data Rute</a>

                <a href="{{ route('admin.operations.routes.index') }}" class="px-3 py-2 text-sm rounded border">Kembali</a>
            </div>
        </div>

        {{-- Form tersembunyi untuk Upload Data Rute: file dipilih via label toolbar di atas,
             lalu disubmit otomatis lewat form ini. --}}
        @can('operations.manage')
            <form id="import-form" action="{{ route('admin.operations.routes.customers.import', $item) }}" method="POST" enctype="multipart/form-data" class="hidden">
                @csrf
            </form>
        @endcan

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif
        @if (session('importWarnings'))
            <div class="mb-4 p-3 bg-yellow-100 text-yellow-800 rounded text-sm">
                <p class="font-medium mb-1">Baris yang dilewati saat upload:</p>
                <ul class="list-disc pl-5">
                    @foreach (session('importWarnings') as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif

        {{-- ===================== MASTER RUTE ===================== --}}
        <form id="route-form" method="POST" action="{{ route('admin.operations.routes.update', $item) }}" class="bg-white p-4 rounded shadow mb-6">
            @csrf @method('PUT')
            <h2 class="font-semibold mb-3 text-sm text-gray-600 uppercase tracking-wide">Data Master Rute</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Kode Rute *</label>
                    <input type="text" name="code" value="{{ old('code', $item->code) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Rute *</label>
                    <input type="text" name="name" value="{{ old('name', $item->name) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Jenis Rute</label>
                    <input type="text" name="route_type" list="route-type-options" value="{{ old('route_type', $item->route_type) }}" placeholder="mis. Reguler, Canvassing..." class="w-full border rounded px-3 py-2 text-sm">
                    <datalist id="route-type-options">
                        @foreach ($routeTypes as $type)
                            <option value="{{ $type }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Salesman</label>
                    <select name="sales_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">- Tidak ditentukan -</option>
                        @foreach ($salesList as $sales)
                            <option value="{{ $sales->id }}" @selected(old('sales_id', $item->sales_id) == $sales->id)>{{ $sales->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Area</label>
                    <textarea name="area" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('area', $item->area) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Keterangan</label>
                    <textarea name="description" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('description', $item->description) }}</textarea>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm mt-4">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Aktif
            </label>
        </form>

        {{-- ===================== TAMBAH PELANGGAN ===================== --}}
        @can('operations.manage')
            <div class="bg-white p-4 rounded shadow mb-6">
                <h2 class="font-semibold mb-3 text-sm text-gray-600 uppercase tracking-wide">Tambah Pelanggan ke Rute</h2>
                <form method="POST" action="{{ route('admin.operations.routes.customers.store', $item) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-sm font-medium mb-1">Pelanggan</label>
                        <select name="customer_id" required class="w-full border rounded px-3 py-2 text-sm">
                            <option value="">- Pilih pelanggan -</option>
                            @foreach ($availableCustomers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->code }} - {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Hari Kunjungan</label>
                        <div class="flex gap-2 text-xs">
                            @foreach (\App\Models\RouteCustomer::DAY_COLUMNS as $col => $label)
                                <label class="flex items-center gap-1"><input type="checkbox" name="{{ $col }}" value="1"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Minggu Kunjungan</label>
                        <div class="flex gap-2 text-xs">
                            @foreach (\App\Models\RouteCustomer::WEEK_COLUMNS as $col => $label)
                                <label class="flex items-center gap-1"><input type="checkbox" name="{{ $col }}" value="1"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Tambah</button>
                </form>
                @if ($availableCustomers->isEmpty())
                    <p class="text-xs text-gray-500 mt-2">Semua pelanggan aktif sudah tergabung pada rute ini, atau belum ada pelanggan aktif.</p>
                @endif
            </div>
        @endcan

        {{-- Form kosong per-baris untuk update & hapus pelanggan (dihubungkan via atribut form="..." pada input/tombol di dalam tabel). --}}
        @can('operations.manage')
            @foreach ($routeCustomers as $rc)
                <form id="rc-update-{{ $rc->id }}" method="POST" action="{{ route('admin.operations.routes.customers.update', [$item, $rc]) }}" class="hidden">
                    @csrf @method('PUT')
                </form>
                <form id="rc-delete-{{ $rc->id }}" method="POST" action="{{ route('admin.operations.routes.customers.destroy', [$item, $rc]) }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
            @endforeach
        @endcan

        {{-- ===================== TABEL DETAIL PELANGGAN ===================== --}}
        <div class="bg-white rounded shadow overflow-x-auto">
            <div class="p-3 border-b flex items-center justify-between flex-wrap gap-2">
                <h2 class="font-semibold text-sm text-gray-600 uppercase tracking-wide">Data Pelanggan pada Rute Ini</h2>
                <button type="button" id="reset-filter-btn" class="text-xs text-blue-700 hover:underline">Reset Filter</button>
            </div>
            <table class="w-full text-sm" id="route-customer-table">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Kode Pelanggan</th>
                        <th class="px-3 py-2 text-left">Nama Pelanggan</th>
                        @foreach (\App\Models\RouteCustomer::DAY_COLUMNS as $col => $label)
                            <th class="px-2 py-2 text-center">{{ $label }}</th>
                        @endforeach
                        @foreach (\App\Models\RouteCustomer::WEEK_COLUMNS as $col => $label)
                            <th class="px-2 py-2 text-center">{{ $label }}</th>
                        @endforeach
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                    {{-- Baris filter per kolom --}}
                    <tr class="bg-gray-50 border-b" id="filter-row">
                        <th class="px-2 py-1">
                            <input type="text" data-filter="code" placeholder="Cari kode..." class="w-full border rounded px-2 py-1 text-xs font-normal">
                        </th>
                        <th class="px-2 py-1">
                            <input type="text" data-filter="name" placeholder="Cari nama..." class="w-full border rounded px-2 py-1 text-xs font-normal">
                        </th>
                        @foreach (\App\Models\RouteCustomer::DAY_COLUMNS as $col => $label)
                            <th class="px-1 py-1">
                                <select data-filter="{{ $col }}" class="w-full border rounded px-1 py-1 text-xs font-normal">
                                    <option value="">Semua</option>
                                    <option value="1">Ya</option>
                                    <option value="0">Tidak</option>
                                </select>
                            </th>
                        @endforeach
                        @foreach (\App\Models\RouteCustomer::WEEK_COLUMNS as $col => $label)
                            <th class="px-1 py-1">
                                <select data-filter="{{ $col }}" class="w-full border rounded px-1 py-1 text-xs font-normal">
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
                        <tr class="border-b route-customer-row"
                            data-code="{{ strtolower($rc->customer->code) }}"
                            data-name="{{ strtolower($rc->customer->name) }}"
                            @foreach (array_keys(\App\Models\RouteCustomer::DAY_COLUMNS) as $col) data-{{ $col }}="{{ $rc->{$col} ? 1 : 0 }}" @endforeach
                            @foreach (array_keys(\App\Models\RouteCustomer::WEEK_COLUMNS) as $col) data-{{ $col }}="{{ $rc->{$col} ? 1 : 0 }}" @endforeach>
                            <td class="px-3 py-2 font-mono">{{ $rc->customer->code }}</td>
                            <td class="px-3 py-2">{{ $rc->customer->name }}</td>
                            @foreach (array_keys(\App\Models\RouteCustomer::DAY_COLUMNS) as $col)
                                <td class="px-2 py-2 text-center">
                                    <input type="checkbox" form="rc-update-{{ $rc->id }}" name="{{ $col }}" value="1" @checked($rc->{$col}) @disabled(! auth()->user()->can('operations.manage'))>
                                </td>
                            @endforeach
                            @foreach (array_keys(\App\Models\RouteCustomer::WEEK_COLUMNS) as $col)
                                <td class="px-2 py-2 text-center">
                                    <input type="checkbox" form="rc-update-{{ $rc->id }}" name="{{ $col }}" value="1" @checked($rc->{$col}) @disabled(! auth()->user()->can('operations.manage'))>
                                </td>
                            @endforeach
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                @can('operations.manage')
                                    <button type="submit" form="rc-update-{{ $rc->id }}" class="text-blue-700 hover:underline mr-2">Simpan</button>
                                    <button type="submit" form="rc-delete-{{ $rc->id }}" onclick="return confirm('Keluarkan pelanggan ini dari rute?')" class="text-red-600 hover:underline">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row"><td colspan="{{ 2 + count(\App\Models\RouteCustomer::DAY_COLUMNS) + count(\App\Models\RouteCustomer::WEEK_COLUMNS) + 1 }}" class="px-3 py-6 text-center text-gray-500">Belum ada pelanggan pada rute ini.</td></tr>
                    @endforelse
                    <tr id="no-match-row" class="hidden">
                        <td colspan="{{ 2 + count(\App\Models\RouteCustomer::DAY_COLUMNS) + count(\App\Models\RouteCustomer::WEEK_COLUMNS) + 1 }}" class="px-3 py-6 text-center text-gray-500">Tidak ada pelanggan yang cocok dengan filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Upload Data Rute: klik label toolbar membuka file picker,
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

        // Filtering komprehensif per kolom pada tabel detail pelanggan.
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
                    noMatchRow.classList.toggle('hidden', !(hasFilters && visibleCount === 0));
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
        })();
    </script>
</x-admin-layout>
