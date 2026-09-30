<x-admin-layout>
    <x-slot name="header">Settings</x-slot>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-1">Settings</h1>
        <p class="text-sm text-gray-500 mb-4">Pengaturan tampilan umum aplikasi (nama & logo). Berlaku untuk semua user, admin maupun sales.</p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.system.settings.update') }}" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label class="block text-sm font-medium mb-1">Nama Aplikasi *</label>
                <input type="text" name="app_name" value="{{ old('app_name', $setting->app_name) }}" class="w-full border rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">Tampil di judul tab browser dan sebagai teks cadangan kalau logo belum diunggah.</p>
            </div>

            <div class="mb-2">
                <label class="block text-sm font-medium mb-1">Logo</label>
                @if ($setting->logo_path)
                    <div class="flex items-center gap-3 mb-3">
                        <img src="{{ $setting->logoUrl() }}" alt="Logo saat ini" class="h-12 max-w-[200px] object-contain border rounded bg-gray-50 p-1">
                        <label class="inline-flex items-center gap-2 text-xs text-red-600">
                            <input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini
                        </label>
                    </div>
                @else
                    <p class="text-xs text-gray-500 mb-2">Belum ada logo — sidebar menampilkan kotak "LOGO" placeholder.</p>
                @endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="w-full border rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">PNG/JPG/SVG/WEBP, maks 2MB. Rasio landscape/lebar lebih cocok untuk sidebar (tinggi maks ~42px).</p>
            </div>

            <div class="flex justify-end gap-2 pt-3 mt-3 border-t">
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
