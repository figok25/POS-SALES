<x-admin-layout>
    <div class="p-6 max-w-3xl">
        <h1 class="text-xl font-semibold mb-4">Buat Sales Task</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.sales-tasks.store') }}" class="bg-white p-4 rounded shadow">
            @csrf

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Sales *</label>
                    <select name="sales_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Pilih Sales --</option>
                        @foreach ($salesList as $s)
                            <option value="{{ $s->id }}" @selected(old('sales_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
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

            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium">Stock yang Dibawa *</label>
                <button type="button" onclick="addItemRow()" class="text-sm bg-gray-200 px-2 py-1 rounded">+ Tambah Item</button>
            </div>

            <table class="w-full text-sm mb-4" id="items-table">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-2 py-2 text-left">Product</th>
                        <th class="px-2 py-2 text-left w-32">Quantity</th>
                        <th class="px-2 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="items-body"></tbody>
            </table>

            <p class="text-xs text-gray-500 mb-4">Dokumen standar (Surat Jalan, Barang Keluar, Daftar Stock) otomatis dibuat dan dirilis ke Sales saat Task di-Apply.</p>

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
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan Draft</button>
            </div>
        </form>
    </div>

    <script>
        const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->name.' ('.$p->sku.')']));
        const customers = @json($customers->map(fn($c) => ['id' => $c->id, 'label' => $c->name.' ('.$c->code.')']));
        let rowIndex = 0;
        let planIndex = 0;

        function productOptions(selected = '') {
            let html = '<option value="">-- Pilih Product --</option>';
            products.forEach(p => {
                html += `<option value="${p.id}" ${String(p.id) === String(selected) ? 'selected' : ''}>${p.label}</option>`;
            });
            return html;
        }

        function customerOptions(selected = '') {
            let html = '<option value="">-- Pilih Customer --</option>';
            customers.forEach(c => {
                html += `<option value="${c.id}" ${String(c.id) === String(selected) ? 'selected' : ''}>${c.label}</option>`;
            });
            return html;
        }

        function addItemRow() {
            const tbody = document.getElementById('items-body');
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-2">
                    <select name="stocks[${rowIndex}][product_id]" class="w-full border rounded px-2 py-1.5 text-sm" required>
                        ${productOptions()}
                    </select>
                </td>
                <td class="px-2 py-2">
                    <input type="number" step="0.01" min="0.01" name="stocks[${rowIndex}][quantity_assigned]" class="w-full border rounded px-2 py-1.5 text-sm" required>
                </td>
                <td class="px-2 py-2 text-center">
                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-600">&times;</button>
                </td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
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

        addItemRow();
    </script>
</x-admin-layout>
