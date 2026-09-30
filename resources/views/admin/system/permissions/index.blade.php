<x-admin-layout>
    <x-slot name="header">Permissions</x-slot>
    <div class="p-6 max-w-4xl">
        <div class="flex items-center justify-between mb-1">
            <h1 class="text-xl font-semibold">Permissions</h1>
            <a href="{{ route('admin.system.roles.index') }}" class="text-sm text-blue-600 hover:underline">Atur per Role &rarr;</a>
        </div>
        <p class="text-sm text-gray-500 mb-4">
            Daftar seluruh permission yang dikenali sistem (dipakai langsung di kode lewat middleware <code>permission:xxx</code> pada setiap route). Halaman ini read-only — untuk mengubah permission yang dimiliki suatu role, buka <a href="{{ route('admin.system.roles.index') }}" class="text-blue-600 hover:underline">System &gt; Roles</a>.
        </p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded shadow overflow-hidden">
            @foreach ($permissions as $group => $items)
                <div class="border-b last:border-b-0">
                    <div class="px-4 py-2 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {{ str_replace('-', ' ', $group) }}
                    </div>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($items as $permission)
                                <tr class="border-t first:border-t-0">
                                    <td class="px-4 py-2 font-mono text-gray-800">{{ $permission->name }}</td>
                                    <td class="px-4 py-2 text-right">
                                        @forelse ($permission->roles as $role)
                                            <span class="inline-block ml-1 px-2 py-0.5 rounded text-xs {{ $role->name === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700' }}">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-gray-400 text-xs">Tidak dimiliki role manapun</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</x-admin-layout>
