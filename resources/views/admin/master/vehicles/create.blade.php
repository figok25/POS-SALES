<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Tambah Vehicle</h1>
        <form method="POST" action="{{ route('admin.master.vehicles.store') }}" class="bg-white p-4 rounded shadow">
            @csrf
            @php($item = null)
            
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Kode *</label>
            <input type="text" name="code" value="{{ old('code', $item->code ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nama Kendaraan *</label>
            <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nomor Polisi *</label>
            <input type="text" name="plate_number" value="{{ old('plate_number', $item->plate_number ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('plate_number') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Tipe</label>
            <input type="text" name="type" value="{{ old('type', $item->type ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.vehicles.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
