<x-admin-layout>
    <x-slot name="header">Tambah User</x-slot>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Tambah User</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.system.users.store') }}" class="bg-white p-4 rounded shadow">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Nama *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Role *</label>
                <select name="role" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    <option value="sales" @selected(old('role') === 'sales')>Sales</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Untuk akun Sales yang terhubung ke data Sales tertentu (bisa dipakai untuk tracking &amp; rute), buat lewat menu Master Data &gt; Sales -- form itu sekalian membuat akun ini. Buat di sini hanya untuk akun umum (mis. Admin tambahan).</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Password *</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Konfirmasi Password *</label>
                <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2 text-sm">
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.system.users.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
