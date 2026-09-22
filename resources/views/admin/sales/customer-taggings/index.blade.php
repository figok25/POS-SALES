<x-admin-layout>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Tagging Toko</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="mb-4 flex gap-2 text-sm">
            @php
                $tabParams = fn (string $s) => array_merge(request()->except(['status', 'page']), ['status' => $s]);
            @endphp
            <a href="{{ route('admin.sales.customer-taggings.index', $tabParams('pending')) }}"
               class="px-3 py-1.5 rounded {{ $status === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Pending</a>
            <a href="{{ route('admin.sales.customer-taggings.index', $tabParams('approved')) }}"
               class="px-3 py-1.5 rounded {{ $status === 'approved' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Approved</a>
            <a href="{{ route('admin.sales.customer-taggings.index', $tabParams('rejected')) }}"
               class="px-3 py-1.5 rounded {{ $status === 'rejected' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">Rejected</a>
        </div>

        {{-- Filter: Sales + Rentang Tanggal Tagging --}}
        <form method="GET" class="mb-4 bg-white p-3 rounded shadow flex flex-wrap items-end gap-3 text-sm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div>
                <label class="block text-xs font-medium mb-1">Sales</label>
                <select name="sales_id" class="border rounded px-3 py-2 text-sm min-w-[180px]">
                    <option value="">- Semua Sales -</option>
                    @foreach ($salesList as $sales)
                        <option value="{{ $sales->id }}" @selected((string) $salesId === (string) $sales->id)>{{ $sales->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Filter</button>
            @if ($salesId || $dateFrom || $dateTo)
                <a href="{{ route('admin.sales.customer-taggings.index', ['status' => $status]) }}" class="px-3 py-2 text-sm rounded border">Reset Filter</a>
            @endif
        </form>

        @if ($status === 'pending')
            <form id="bulk-approve-form" method="POST" action="{{ route('admin.sales.customer-taggings.bulk-approve') }}">
                @csrf
                <div class="mb-3 flex items-center gap-3">
                    <button type="submit"
                            id="bulk-approve-btn"
                            disabled
                            onclick="return confirm('Approve semua tagging terpilih? Customer akan langsung dibuat dan masuk Rute Kanvas.')"
                            class="px-3 py-1.5 rounded text-sm bg-green-600 text-white disabled:bg-gray-300 disabled:cursor-not-allowed">
                        Approve Terpilih (<span id="bulk-approve-count">0</span>)
                    </button>
                </div>

                <div class="bg-white rounded shadow overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-3 py-2 text-left w-8"><input type="checkbox" id="select-all-taggings"></th>
                                <th class="px-3 py-2 text-left">Waktu Tagging</th>
                                <th class="px-3 py-2 text-left">Sales</th>
                                <th class="px-3 py-2 text-left">Nama Toko</th>
                                <th class="px-3 py-2 text-left">Telepon</th>
                                <th class="px-3 py-2 text-left">Tipe</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="border-b">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="tagging_ids[]" value="{{ $item->id }}" class="tagging-checkbox">
                                    </td>
                                    <td class="px-3 py-2">{{ $item->tagged_at->format('d M Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $item->name }}</td>
                                    <td class="px-3 py-2">{{ $item->phone ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $item->customer_type ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded text-xs">Pending</span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <a href="{{ route('admin.sales.customer-taggings.show', $item) }}" class="text-blue-600 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <script>
                (function () {
                    const selectAll = document.getElementById('select-all-taggings');
                    const checkboxes = document.querySelectorAll('.tagging-checkbox');
                    const countEl = document.getElementById('bulk-approve-count');
                    const btn = document.getElementById('bulk-approve-btn');

                    function refresh() {
                        const checked = document.querySelectorAll('.tagging-checkbox:checked').length;
                        countEl.textContent = checked;
                        btn.disabled = checked === 0;
                    }

                    selectAll?.addEventListener('change', function () {
                        checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                        refresh();
                    });

                    checkboxes.forEach(cb => cb.addEventListener('change', refresh));
                    refresh();
                })();
            </script>
        @else
            <div class="bg-white rounded shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left">Waktu Tagging</th>
                            <th class="px-3 py-2 text-left">Sales</th>
                            <th class="px-3 py-2 text-left">Nama Toko</th>
                            <th class="px-3 py-2 text-left">Telepon</th>
                            <th class="px-3 py-2 text-left">Tipe</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b">
                                <td class="px-3 py-2">{{ $item->tagged_at->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $item->sales->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $item->name }}</td>
                                <td class="px-3 py-2">{{ $item->phone ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $item->customer_type ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    @if ($item->status === 'approved')
                                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Approved</span>
                                    @else
                                        <span class="text-red-700 bg-red-100 px-2 py-0.5 rounded text-xs">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('admin.sales.customer-taggings.show', $item) }}" class="text-blue-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
