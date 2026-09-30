<x-admin-layout>
    <x-slot name="header">Users</x-slot>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Users</h1>
            <a href="{{ route('admin.system.users.create') }}" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">+ Tambah User</a>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form method="GET" class="mb-4">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama/email..." class="border rounded px-3 py-2 text-sm w-64">
            <button class="bg-gray-200 px-3 py-2 rounded text-sm">Cari</button>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left">Nama</th>
                        <th class="px-3 py-2 text-left">Email</th>
                        <th class="px-3 py-2 text-left">Role</th>
                        <th class="px-3 py-2 text-left">Terhubung ke Sales</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $item->name }}</td>
                            <td class="px-3 py-2">{{ $item->email }}</td>
                            <td class="px-3 py-2">
                                @forelse ($item->roles as $role)
                                    <span class="px-2 py-0.5 rounded text-xs {{ $role->name === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700' }}">{{ $role->name }}</span>
                                @empty
                                    <span class="text-gray-400 text-xs">Belum ada role</span>
                                @endforelse
                            </td>
                            <td class="px-3 py-2 text-gray-500">{{ $linkedSalesByUserId[$item->id] ?? '-' }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <a href="{{ route('admin.system.users.edit', $item) }}" class="text-blue-600 hover:underline">Edit</a>
                                <form action="{{ route('admin.system.users.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus user ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="99" class="px-3 py-6 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>
