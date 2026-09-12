<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Edit Employee</h1>
        <form method="POST" action="{{ route('admin.master.employees.update', $item) }}" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')
            
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
            <label class="block text-sm font-medium mb-1">Jabatan</label>
            <input type="text" name="position" value="{{ old('position', $item->position ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('position') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Telepon</label>
            <input type="text" name="phone" value="{{ old('phone', $item->phone ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('phone') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Alamat</label>
            <textarea name="address" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('address', $item->address ?? '') }}</textarea>
            @error('address') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.employees.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
