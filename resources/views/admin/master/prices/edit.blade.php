<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Edit Price</h1>
        <form method="POST" action="{{ route('admin.master.prices.update', $item) }}" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')
            
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Product *</label>
            <select name="product_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">-- Pilih Product --</option>
                @foreach ($products as $opt)
                    <option value="{{ $opt->id }}" @selected(old('product_id', $item->product_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
            @error('product_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nama Harga (mis. Harga Umum) *</label>
            <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Jumlah *</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount', $item->amount ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('amount') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.prices.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
