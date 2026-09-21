<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <h1 class="text-xl font-semibold mb-4">Buat Sales Task</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif

        @if ($assignableBkbs->isEmpty())
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Belum ada BKB Distribusi berstatus <strong>Applied</strong> yang tersedia untuk ditugaskan.
                Buat/Apply BKB Distribusi terlebih dahulu di menu Distribusi &rarr; BKB Distribusi.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.sales-tasks.store') }}" class="bg-white p-4 rounded shadow">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">BKB Distribusi (sumber stock) *</label>
                <select name="bkb_distribusi_id" id="bkb_distribusi_id" class="w-full border rounded px-3 py-2 text-sm" @disabled($assignableBkbs->isEmpty())>
                    <option value="">-- Pilih BKB Distribusi yang sudah Applied --</option>
                    @foreach ($assignableBkbs as $bkb)
                        <option value="{{ $bkb->id }}" @selected(old('bkb_distribusi_id', $selectedBkbId) == $bkb->id)>
                            {{ $bkb->code }} &mdash; Sales: {{ $bkb->sales->name ?? '-' }} &mdash; Warehouse: {{ $bkb->warehouse->name ?? '-' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Sales Task hanya menugaskan BKB yang sudah Apply (stock sudah pindah ke Sales Stock). Item &amp; quantity mengikuti BKB, tidak bisa diubah di sini.</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Branch *</label>
                    <select name="branch_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Pilih Branch --</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal Tugas *</label>
                    <input type="date" name="task_date" value="{{ old('task_date', now()->toDateString()) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
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

            <p class="text-xs text-gray-500 mb-4">Dokumen standar (Surat Jalan, Barang Keluar, Daftar Stock) otomatis dibuat mengacu ke BKB ini dan dirilis ke Sales saat Task di-Apply.</p>

            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium">Visit Plan / Urutan Kunjungan Hari Ini (opsional)</label>
                <button type="button" onclick="addPlanRow()" class="text-sm bg-gray-200 px-2 py-1 rounded">+ Tambah Customer</button>
            </div>
            <p class="text-xs text-gray-500 mb-2">Kalau diisi, urutan baris di bawah ini (atas ke bawah) menjadi urutan kunjungan resmi yang dipakai "Today's Route" Sales App. Kalau dikosongkan, urutan akan dihitung otomatis (jarak terdekat dari lokasi Sales).</p>

            <table class="w-full text-sm mb-4" id="plan-table">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-2 py-2 w-10 text-left">#</th>
                        <th class="px-2 py-2 text-left">Customer</th>
                        <th class="px-2 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="plan-body"></tbody>
            </table>

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

            $customersForJs = $customers->map(function ($c) {
                return [
                    'id' => $c->id,
                    'label' => $c->name . ' (' . $c->code . ')',
                ];
            })->values()->all();
        @endphp

        const bkbs = @json($bkbsForJs);
        const customers = @json($customersForJs);
        let planIndex = 0;

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

        document.getElementById('bkb_distribusi_id')?.addEventListener('change', renderBkbItems);
        renderBkbItems();

        function customerOptions(selected = '') {
            let html = '<option value="">-- Pilih Customer --</option>';
            customers.forEach(c => {
                html += `<option value="${c.id}" ${String(c.id) === String(selected) ? 'selected' : ''}>${c.label}</option>`;
            });
            return html;
        }

        function renumberPlanRows() {
            document.querySelectorAll('#plan-body tr').forEach((tr, i) => {
                tr.querySelector('.plan-seq').textContent = i + 1;
            });
        }

        function addPlanRow() {
            const tbody = document.getElementById('plan-body');
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-2 plan-seq"></td>
                <td class="px-2 py-2">
                    <select name="visit_plan[${planIndex}][customer_id]" class="w-full border rounded px-2 py-1.5 text-sm">
                        ${customerOptions()}
                    </select>
                </td>
                <td class="px-2 py-2 text-center">
                    <button type="button" onclick="this.closest('tr').remove(); renumberPlanRows();" class="text-red-600">&times;</button>
                </td>
            `;
            tbody.appendChild(tr);
            planIndex++;
            renumberPlanRows();
        }
    </script>
</x-admin-layout>
