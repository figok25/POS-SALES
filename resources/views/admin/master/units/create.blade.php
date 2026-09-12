<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Tambah Unit</h1>
        <form method="POST" action="{{ route('admin.master.units.store') }}" class="bg-white p-4 rounded shadow">
            @csrf
            @php($item = null)
            
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Simbol *</label>
            <input type="text" name="symbol" value="{{ old('symbol', $item->symbol ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('symbol') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nama *</label>
            <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.units.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
