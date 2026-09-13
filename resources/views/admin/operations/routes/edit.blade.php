<x-admin-layout>
    <div class="p-6 max-w-lg">
        <h1 class="text-xl font-semibold mb-4">Edit Route</h1>
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('admin.operations.routes.update', $item) }}" class="bg-white p-4 rounded shadow">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Kode *</label>
                <input type="text" name="code" value="{{ old('code', $item->code) }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Nama *</label>
                <input type="text" name="name" value="{{ old('name', $item->name) }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Area</label>
                <textarea name="area" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('area', $item->area) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm mb-4">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Aktif
            </label>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.operations.routes.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
