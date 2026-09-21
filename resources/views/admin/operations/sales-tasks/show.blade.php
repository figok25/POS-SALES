<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Sales Task {{ $salesTask->code }}</h1>
            <a href="{{ route('admin.sales-tasks.index') }}" class="text-sm text-blue-700 hover:underline">&larr; Kembali</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded shadow mb-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">BKB Distribusi (sumber stock)</dt><dd>
                    @if ($salesTask->bkbDistribusi)
                        <a href="{{ route('admin.distribution.bkb.show', $salesTask->bkbDistribusi) }}" class="text-blue-700 hover:underline">{{ $salesTask->bkbDistribusi->code }}</a>
                    @else
                        -
                    @endif
                </dd></div>
                <div><dt class="text-gray-500">Sales</dt><dd>{{ $salesTask->sales->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Branch</dt><dd>{{ $salesTask->branch->name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Tanggal Tugas</dt><dd>{{ $salesTask->task_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="capitalize">{{ str_replace('_', ' ', $salesTask->status) }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Catatan</dt><dd>{{ $salesTask->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <div class="px-3 py-2 border-b font-medium text-sm flex items-center justify-between">
                <span>Stock yang Ditugaskan</span>
                <a href="{{ route('admin.sales-tasks.print-stock', $salesTask) }}" target="_blank" class="text-blue-700 hover:underline text-xs font-normal">Cetak Daftar Stock</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Qty Ditugaskan</th>
                        <th class="px-3 py-2 text-right">Qty Diverifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesTask->taskStocks as $line)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $line->product->name ?? '-' }} ({{ $line->product->sku ?? '-' }})</td>
                            <td class="px-3 py-2 text-right">{{ number_format($line->quantity_assigned, 2) }}</td>
                            <td class="px-3 py-2 text-right {{ $line->difference() != 0 ? 'text-red-600 font-semibold' : '' }}">
                                {{ $line->quantity_verified !== null ? number_format($line->quantity_verified, 2) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto mb-4">
            <div class="px-3 py-2 border-b font-medium text-sm">Dokumen Task</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Judul</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-left">Status Download</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesTask->documents as $doc)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $doc->title }}</td>
                            <td class="px-3 py-2">{{ $doc->type }}</td>
                            <td class="px-3 py-2">{{ $doc->downloaded_at ? 'Downloaded' : 'Belum diunduh' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($salesTask->isDraft())
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Apply akan merilis dokumen &amp; stock ini ke Sales App (status berubah ke Document Available).
            </div>
        @endif

        @if ($salesTask->status === \App\Models\SalesTask::STATUS_STOCK_VARIANCE)
            <div class="mb-4 p-3 bg-red-50 text-red-800 rounded text-sm">
                Ada selisih antara Qty Ditugaskan dan Qty Diverifikasi (baris merah di tabel Stock).
                Task ini tertahan dan Sales BELUM BISA mulai bekerja sampai Anda menyetujui selisihnya.
            </div>
        @endif

        <div class="flex gap-2 mb-4">
            @can('sales-task.manage')
                @if ($salesTask->isDraft())
                    <form action="{{ route('admin.sales-tasks.apply', $salesTask) }}" method="POST" onsubmit="return confirm('Apply/Release Task ini ke Sales?')">
                        @csrf
                        <button class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">Apply / Release</button>
                    </form>
                    <form action="{{ route('admin.sales-tasks.cancel', $salesTask) }}" method="POST" onsubmit="return confirm('Batalkan Task ini?')">
                        @csrf
                        <button class="border px-3 py-2 rounded text-sm">Batalkan</button>
                    </form>
                @endif
                @if ($salesTask->status === \App\Models\SalesTask::STATUS_STOCK_VARIANCE)
                    <form action="{{ route('admin.sales-tasks.approve-variance', $salesTask) }}" method="POST" onsubmit="return confirm('Setujui selisih stock ini? Task akan menjadi Ready to Work.')">
                        @csrf
                        <button class="bg-amber-600 text-white px-3 py-2 rounded text-sm hover:bg-amber-700">Setujui Selisih Stock</button>
                    </form>
                @endif
            @endcan
        </div>

        @can('sales-task.manage')
            @if (in_array($salesTask->status, [\App\Models\SalesTask::STATUS_DRAFT, \App\Models\SalesTask::STATUS_DOCUMENT_AVAILABLE], true))
                <div class="bg-white p-4 rounded shadow">
                    <div class="font-medium text-sm mb-2">Edit Penugasan (Ganti Sales)</div>
                    <p class="text-xs text-gray-500 mb-3">Mengganti Sales akan memindahkan Sales Stock BKB ini dari Sales lama ke Sales baru (bukan Apply ulang). Hanya bisa dilakukan sebelum Sales memulai Verifikasi Stock/Start Work.</p>
                    <form action="{{ route('admin.sales-tasks.reassign', $salesTask) }}" method="POST" class="flex gap-2" onsubmit="return confirm('Pindahkan penugasan & Sales Stock ke Sales terpilih?')">
                        @csrf
                        <select name="sales_id" class="border rounded px-3 py-2 text-sm flex-1" required>
                            <option value="">-- Pilih Sales Baru --</option>
                            @foreach ($salesList as $s)
                                @if ($s->id !== $salesTask->sales_id)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="bg-gray-700 text-white px-3 py-2 rounded text-sm hover:bg-gray-800">Pindahkan</button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</x-admin-layout>
