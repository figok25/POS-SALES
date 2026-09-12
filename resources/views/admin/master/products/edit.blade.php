<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Edit Product</h1>
        <form method="POST" action="{{ route('admin.master.products.update', $item) }}" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')
            
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">SKU *</label>
            <input type="text" name="sku" value="{{ old('sku', $item->sku ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('sku') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nama *</label>
            <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Category *</label>
            <select name="category_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">-- Pilih Category --</option>
                @foreach ($categorys as $opt)
                    <option value="{{ $opt->id }}" @selected(old('category_id', $item->category_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Unit *</label>
            <select name="unit_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">-- Pilih Unit --</option>
                @foreach ($units as $opt)
                    <option value="{{ $opt->id }}" @selected(old('unit_id', $item->unit_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
            @error('unit_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Deskripsi</label>
            <textarea name="description" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('description', $item->description ?? '') }}</textarea>
            @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.products.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
