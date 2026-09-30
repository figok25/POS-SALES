<x-admin-layout>
    <x-slot name="header">Roles</x-slot>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-2">Roles</h1>
        <p class="text-sm text-gray-500 mb-4">
            Sistem ini hanya mengenal 2 role tetap (admin &amp; sales) -- keduanya menentukan grup menu yang bisa diakses.
            Yang bisa diatur di sini adalah permission detail per role (mis. siapa yang boleh approve Settlement, dsb).
        </p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Role</th>
                        <th class="px-3 py-2 text-left">Jumlah User</th>
                        <th class="px-3 py-2 text-left">Jumlah Permission</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr class="border-b">
                            <td class="px-3 py-2 font-medium capitalize">{{ $role->name }}</td>
                            <td class="px-3 py-2">{{ $role->users_count }}</td>
                            <td class="px-3 py-2">{{ $role->permissions_count }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.system.roles.edit', $role) }}" class="text-blue-600 hover:underline">Atur Permission</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
