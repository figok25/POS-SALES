<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <h1 class="text-xl font-semibold mb-4">Buat Sales Task</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $dayNames = [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => "Jum'at",
                6 => 'Sabtu',
                7 => 'Minggu',
            ];

            $dayName = $dayNames[$dayOfWeekIso] ?? '-';
        @endphp

        {{-- Otomasi Sales Task Berdasarkan Rute Harian: tanggal dipilih DULU
             (form GET terpisah, auto-reload), baru daftar BKB/Sales yang
             muncul di bawah disaring sesuai Rute Kanvas hari itu. --}}

        <form method="GET" action="{{ route('admin.sales-tasks.create') }}" class="bg-white p-4 rounded shadow mb-4">
            <label class="block text-sm font-medium mb-1">Tanggal Tugas</label>

            <div class="flex items-center gap-2">
                <input
                    type="date"
                    name="task_date"
                    value="{{ $taskDate->toDateString() }}"
                    onchange="this.form.submit()"
                    class="border rounded px-3 py-2 text-sm"
                >

                <span class="text-xs text-gray-500">
                    ({{ $dayName }})
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Daftar Sales di bawah ini otomatis disaring: hanya Sales yang punya
                <strong>Rute Kanvas</strong> terjadwal pada hari ini yang ditampilkan.
                @if ($skippedCount > 0)
                    <span class="text-yellow-700">({{ $skippedCount }} BKB Applied lain disembunyikan karena Sales-nya belum punya Rute Kanvas hari ini.)</span>
                @endif
            </p>
        </form>

        @if ($assignableBkbs->isEmpty())
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Tidak ada BKB Distribusi Applied yang Sales-nya punya Rute Kanvas untuk tanggal ini.
                @if ($skippedCount > 0)
                    Ada {{ $skippedCount }} BKB Applied tersedia, tapi Sales-nya belum diatur Rute Kanvas-nya untuk hari ini --
                    atur dulu di menu <a href="{{ route('admin.sales.visit-plans.index') }}" class="underline">Visit Plan (Rute Kanvas)</a>.
                @else
                    Buat/Apply BKB Distribusi terlebih dahulu di menu Distribusi &rarr; BKB Distribusi.
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('admin.sales-tasks.store') }}" class="bg-white p-4 rounded shadow">
            @csrf
            <input type="hidden" name="task_date" value="{{ $taskDate->toDateString() }}">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">BKB Distribusi (sumber stock) *</label>
                <select name="bkb_distribusi_id" id="bkb_distribusi_id" class="w-full border rounded px-3 py-2 text-sm" @disabled($assignableBkbs->isEmpty())>
                    <option value="">-- Pilih BKB Distribusi --</option>
                    @foreach ($assignableBkbs as $bkb)
                        <option value="{{ $bkb->id }}" @selected(old('bkb_distribusi_id', $selectedBkbId) == $bkb->id)>
                            {{ $bkb->code }} &mdash; Sales: {{ $bkb->sales->name ?? '-' }} &mdash; Warehouse: {{ $bkb->warehouse->name ?? '-' }} &mdash; {{ $routePreviewByBkb[$bkb->id]->count() }} toko
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Sales Task hanya menugaskan BKB yang sudah Apply (stock sudah pindah ke Sales Stock). Item &amp; quantity mengikuti BKB, tidak bisa diubah di sini.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Branch *</label>
                <select name="branch_id" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">-- Pilih Branch --</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="mb-2">
                <label class="block text-sm font-medium">Stock yang Dibawa (dari BKB terpilih)</label>
            </div>
            <table class="w-full text-sm mb-4">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-2 py-2 text-left">Product</th>
                        <th class="px-2 py-2 text-right w-32">Quantity</th>
                    </tr>
                </thead>
                <tbody id="bkb-items-body">
                    <tr><td colspan="2" class="px-2 py-4 text-center text-gray-400">Pilih BKB Distribusi di atas untuk melihat item.</td></tr>
                </tbody>
            </table>

            {{-- Otomasi Sales Task Berdasarkan Rute Harian: TIDAK ADA LAGI
                 input manual "+ Tambah Customer". Daftar toko di bawah ini
                 murni preview read-only, ditarik otomatis dari Rute Kanvas
                 (SalesVisitPlan) Sales terpilih pada tanggal tugas di atas. --}}
            <div class="mb-2">
                <label class="block text-sm font-medium">Rute Kunjungan Hari Ini (otomatis dari Rute Kanvas)</label>
                <p class="text-xs text-gray-500">Urutan ini otomatis mengikuti Rute Kanvas Sales terpilih. Untuk mengubah urutan/isi rute, edit lewat menu Visit Plan -- bukan di sini.</p>
            </div>
            <table class="w-full text-sm mb-4" id="plan-table">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-2 py-2 w-10 text-left">#</th>
                        <th class="px-2 py-2 text-left">Customer</th>
                    </tr>
                </thead>
                <tbody id="plan-body">
                    <tr><td colspan="2" class="px-2 py-4 text-center text-gray-400">Pilih BKB Distribusi di atas untuk melihat rute.</td></tr>
                </tbody>
            </table>

            <p class="text-xs text-gray-500 mb-4">Dokumen standar (Surat Jalan, Barang Keluar, Daftar Stock) otomatis dibuat mengacu ke BKB ini dan dirilis ke Sales saat Task di-Apply.</p>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.sales-tasks.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700" @disabled($assignableBkbs->isEmpty())>Simpan Draft</button>
            </div>
        </form>
    </div>

    <script>
        @php
            $bkbsForJs = $assignableBkbs->mapWithKeys(function ($b) {
                return [
                    $b->id => $b->items->map(function ($i) {
                        return [
                            'label' => ($i->product->name ?? '-') . ' (' . ($i->product->sku ?? '-') . ')',
                            'quantity' => number_format((float) $i->quantity, 2),
                        ];
                    })->values()->all(),
                ];
            })->all();

            $routesForJs = $routePreviewByBkb->map(fn ($stops) => $stops->values()->all())->all();
        @endphp

        const bkbs = @json($bkbsForJs);
        const routes = @json($routesForJs);

        function renderBkbItems() {
            const select = document.getElementById('bkb_distribusi_id');
            const tbody = document.getElementById('bkb-items-body');
            const items = bkbs[select.value];

            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="px-2 py-4 text-center text-gray-400">Pilih BKB Distribusi di atas untuk melihat item.</td></tr>';
                return;
            }

            tbody.innerHTML = items.map(i => `
                <tr class="border-b">
                    <td class="px-2 py-2">${i.label}</td>
                    <td class="px-2 py-2 text-right">${i.quantity}</td>
                </tr>
            `).join('');
        }

        function renderRoutePreview() {
            const select = document.getElementById('bkb_distribusi_id');
            const tbody = document.getElementById('plan-body');
            const stops = routes[select.value];

            if (!stops || stops.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="px-2 py-4 text-center text-gray-400">Pilih BKB Distribusi di atas untuk melihat rute.</td></tr>';
                return;
            }

            tbody.innerHTML = stops.map((name, i) => `
                <tr class="border-b">
                    <td class="px-2 py-2">${i + 1}</td>
                    <td class="px-2 py-2">${name}</td>
                </tr>
            `).join('');
        }

        document.getElementById('bkb_distribusi_id')?.addEventListener('change', () => {
            renderBkbItems();
            renderRoutePreview();
        });
        renderBkbItems();
        renderRoutePreview();
    </script>
</x-admin-layout>
