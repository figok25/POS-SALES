<x-admin-layout>
    <x-slot name="header">Edit User</x-slot>
    <div class="p-6 max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Edit User</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif

        @if ($linkedSales)
            <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded text-sm">
                User ini terhubung ke akun Sales <strong>{{ $linkedSales->name }}</strong>.
                Nama/email/password bisa diubah di sini, tapi role tetap terkunci ke "sales"
                -- untuk melepas keterkaitannya, kelola lewat <a href="{{ route('admin.master.sales.edit', $linkedSales) }}" class="underline">Master Data &gt; Sales</a>.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.system.users.update', $item) }}" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Nama *</label>
                <input type="text" name="name" value="{{ old('name', $item->name) }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $item->email) }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Role *</label>
                <select name="role" class="w-full border rounded px-3 py-2 text-sm" @disabled($linkedSales)>
                    <option value="admin" @selected(old('role', $item->roles->pluck('name')->first()) === 'admin')>Admin</option>
                    <option value="sales" @selected(old('role', $item->roles->pluck('name')->first()) === 'sales')>Sales</option>
                </select>
                @if ($linkedSales)
                    {{-- select disabled tidak ikut terkirim -- kirim ulang value-nya lewat hidden input --}}
                    <input type="hidden" name="role" value="sales">
                @endif
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Password Baru</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">Kosongkan kalau tidak ingin mengubah password.</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2 text-sm">
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.system.users.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
