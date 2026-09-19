<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Edit Customer</h1>
        <form method="POST" action="{{ route('admin.master.customers.update', $item) }}" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')
            
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Sales</label>
            <select name="sales_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">-- Pilih Sales --</option>
                @foreach ($saless as $opt)
                    <option value="{{ $opt->id }}" @selected(old('sales_id', $item->sales_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
            @error('sales_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
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
            <label class="block text-sm font-medium mb-1">Telepon</label>
            <input type="text" name="phone" value="{{ old('phone', $item->phone ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('phone') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">NPWP</label>
            <input type="text" name="npwp" value="{{ old('npwp', $item->npwp ?? '') }}" class="w-full border rounded px-3 py-2 text-sm">
            @error('npwp') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4 grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude', $item->latitude ?? '') }}" placeholder="-8.0768309" class="w-full border rounded px-3 py-2 text-sm">
                @error('latitude') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $item->longitude ?? '') }}" placeholder="111.7016798" class="w-full border rounded px-3 py-2 text-sm">
                @error('longitude') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <p class="col-span-2 text-xs text-gray-500">
                Dipakai Peta Customer di Sales App. Biasanya otomatis terisi saat Admin approve Tagging Toko dari Sales.
                @if ($item->latitude && $item->longitude)
                    <a href="https://maps.google.com/?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" class="text-blue-600 hover:underline">Lihat di Google Maps</a>
                @endif
            </p>
        </div>
            <div class="mb-4">
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active) class="mr-2"> Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.master.customers.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
