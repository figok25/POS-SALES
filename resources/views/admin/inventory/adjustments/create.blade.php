<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Buat Draft Stock Adjustment</h1>

        <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" class="bg-white p-4 rounded shadow">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Product *</label>
                <select name="product_id" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">-- Pilih Product --</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                    @endforeach
                </select>
                @error('product_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Tipe Lokasi *</label>
                <select id="location_type" name="location_type" class="w-full border rounded px-3 py-2 text-sm" onchange="toggleLocation()">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="warehouse" @selected(old('location_type') === 'warehouse')>Warehouse</option>
                    <option value="sales" @selected(old('location_type') === 'sales')>Sales</option>
                </select>
                @error('location_type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4" id="warehouse_field" style="display:none">
                <label class="block text-sm font-medium mb-1">Warehouse *</label>
                <select name="location_id_warehouse" class="w-full border rounded px-3 py-2 text-sm" onchange="syncLocationId(this.value)">
                    <option value="">-- Pilih Warehouse --</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4" id="sales_field" style="display:none">
                <label class="block text-sm font-medium mb-1">Sales *</label>
                <select name="location_id_sales" class="w-full border rounded px-3 py-2 text-sm" onchange="syncLocationId(this.value)">
                    <option value="">-- Pilih Sales --</option>
                    @foreach ($salesList as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" id="location_id" name="location_id" value="{{ old('location_id') }}">
            @error('location_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Tipe Adjustment *</label>
                <select name="type" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="in" @selected(old('type') === 'in')>Stok Masuk (+)</option>
                    <option value="out" @selected(old('type') === 'out')>Stok Keluar (-)</option>
                </select>
                @error('type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Quantity *</label>
                <input type="number" step="0.01" name="quantity" value="{{ old('quantity') }}" class="w-full border rounded px-3 py-2 text-sm">
                @error('quantity') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Alasan</label>
                <textarea name="reason" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <p class="text-xs text-gray-500 mb-4">Dokumen akan tersimpan sebagai Draft. Stok baru berubah setelah di-Apply pada halaman daftar.</p>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.inventory.adjustments.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan Draft</button>
            </div>
        </form>
    </div>

    <script>
        function toggleLocation() {
            const type = document.getElementById('location_type').value;
            document.getElementById('warehouse_field').style.display = type === 'warehouse' ? 'block' : 'none';
            document.getElementById('sales_field').style.display = type === 'sales' ? 'block' : 'none';
            document.getElementById('location_id').value = '';
        }
        function syncLocationId(value) {
            document.getElementById('location_id').value = value;
        }
        toggleLocation();
    </script>
</x-admin-layout>
