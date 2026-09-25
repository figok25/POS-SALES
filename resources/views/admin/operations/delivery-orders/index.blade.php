<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Delivery Order</h1>
            @can('operations.manage')
                <a href="{{ route('admin.operations.delivery-orders.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Buat Draft</a>
            @endcan
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-2">
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="dispatched" @selected($status === 'dispatched')>Dispatched</option>
                <option value="delivered" @selected($status === 'delivered')>Delivered</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
            </select>
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Filter</button>
        </form>

        <form id="bulk-dispatch-form" method="POST" action="{{ route('admin.operations.delivery-orders.bulk-dispatch') }}">
            @csrf
            <input type="hidden" name="select_all_draft" id="select-all-draft-flag" value="0">
            @can('operations.manage')
                <div class="bg-white rounded shadow p-4 mb-4 flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Vehicle</label>
                        <select name="vehicle_id" class="border rounded px-2 py-1.5 text-sm">
                            <option value="">- pilih -</option>
                            @foreach ($vehicles ?? [] as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Driver</label>
                        <select name="driver_id" class="border rounded px-2 py-1.5 text-sm">
                            <option value="">- pilih -</option>
                            @foreach ($drivers ?? [] as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Jadwal</label>
                        <input type="date" name="scheduled_date" class="border rounded px-2 py-1.5 text-sm">
                    </div>
                    <button type="submit" id="bulk-dispatch-submit" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">
                        Apply &amp; Dispatch yang Dicentang
                    </button>
                    @if ($draftCount > 0)
                        <label class="flex items-center gap-2 text-sm ml-2 bg-yellow-50 border border-yellow-200 px-3 py-2 rounded">
                            <input type="checkbox" id="select-all-draft-everywhere">
                            Pilih SEMUA {{ $draftCount }} Draft DO (termasuk yang tidak tampil di halaman ini)
                        </label>
                    @endif
                    <span id="select-all-draft-note" class="hidden text-xs text-yellow-800">Seluruh Draft DO akan diproses saat tombol Apply &amp; Dispatch ditekan, terlepas dari centang di tabel.</span>
                </div>
            @endcan

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        @can('operations.manage')
                            <th class="px-3 py-2"><input type="checkbox" id="check-all"></th>
                        @endcan
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Vehicle</th>
                        <th class="px-3 py-2 text-left">Driver</th>
                        <th class="px-3 py-2 text-left">Jadwal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            @can('operations.manage')
                                <td class="px-3 py-2">
                                    @if ($item->status === 'draft')
                                        <input type="checkbox" name="delivery_order_ids[]" value="{{ $item->id }}" class="do-checkbox">
                                    @endif
                                </td>
                            @endcan
                            <td class="px-3 py-2 font-mono">{{ $item->code }}</td>
                            <td class="px-3 py-2">{{ $item->salesTransaction->customer->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->vehicle->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->driver->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $item->scheduled_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @php
                                    $badge = [
                                        'draft' => 'text-yellow-700 bg-yellow-100',
                                        'dispatched' => 'text-blue-700 bg-blue-100',
                                        'delivered' => 'text-green-700 bg-green-100',
                                        'cancelled' => 'text-gray-600 bg-gray-100',
                                    ][$item->status] ?? 'text-gray-600 bg-gray-100';
                                @endphp
                                <span class="{{ $badge }} px-2 py-0.5 rounded text-xs capitalize">{{ $item->status }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.operations.delivery-orders.show', $item) }}" class="text-blue-700 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </form>

        <div class="mt-4">{{ $items->links() }}</div>

        <script>
            (function () {
                const checkAll = document.getElementById('check-all');
                const rowCheckboxes = () => document.querySelectorAll('.do-checkbox');
                const selectAllEverywhere = document.getElementById('select-all-draft-everywhere');
                const selectAllFlag = document.getElementById('select-all-draft-flag');
                const selectAllNote = document.getElementById('select-all-draft-note');
                const form = document.getElementById('bulk-dispatch-form');

                function syncHeaderCheckbox() {
                    if (!checkAll) return;
                    const boxes = Array.from(rowCheckboxes());
                    const checkedCount = boxes.filter(cb => cb.checked).length;
                    checkAll.checked = boxes.length > 0 && checkedCount === boxes.length;
                    checkAll.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
                }

                if (checkAll) {
                    checkAll.addEventListener('change', function () {
                        rowCheckboxes().forEach(cb => { cb.checked = this.checked; });
                        syncHeaderCheckbox();
                    });
                }

                document.addEventListener('change', function (e) {
                    if (e.target.classList && e.target.classList.contains('do-checkbox')) {
                        syncHeaderCheckbox();
                    }
                });

                if (selectAllEverywhere) {
                    selectAllEverywhere.addEventListener('change', function () {
                        selectAllFlag.value = this.checked ? '1' : '0';
                        if (selectAllNote) selectAllNote.classList.toggle('hidden', !this.checked);

                        rowCheckboxes().forEach(cb => {
                            cb.checked = this.checked;
                            cb.disabled = this.checked;
                        });
                        if (checkAll) {
                            checkAll.checked = this.checked;
                            checkAll.disabled = this.checked;
                            checkAll.indeterminate = false;
                        }
                    });
                }

                if (form) {
                    form.addEventListener('submit', function (e) {
                        const allEverywhere = selectAllFlag && selectAllFlag.value === '1';
                        const anyChecked = Array.from(rowCheckboxes()).some(cb => cb.checked);

                        if (!allEverywhere && !anyChecked) {
                            e.preventDefault();
                            alert('Pilih minimal 1 Draft DO dulu, atau centang "Pilih SEMUA Draft DO".');
                        }
                    });
                }
            })();
        </script>
    </div>
</x-admin-layout>
