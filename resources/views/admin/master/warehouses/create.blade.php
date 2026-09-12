<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Tambah Warehouse</h1>
        <form method="POST" action="{{ route('admin.master.warehouses.store') }}" class="bg-white p-4 rounded shadow">
            @csrf
            @php($item = null)
            
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Branch *</label>
            <select name="branch_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">-- Pilih Branch --</option>
                @foreach ($branchs as $opt)
                    <option value="{{ $opt->id }}" @selected(old('branch_id', $item->branch_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
            @error('branch_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Kode *</label>
            <input type="text" name="code" value="{{ old('code', $item->code ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nama *</label>
            <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Alamat</label>
            <textarea name="address" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('address', $item->address ?? '') }}</textarea>
            @error('address') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.warehouses.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
