<x-admin-layout>
    <x-slot name="header">Atur Permission - {{ ucfirst($role->name) }}</x-slot>
    <div class="p-6 max-w-4xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Atur Permission: <span class="capitalize">{{ $role->name }}</span></h1>
            <a href="{{ route('admin.system.roles.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali</a>
        </div>

        @if ($role->name === auth()->user()->roles->pluck('name')->first() && $role->name === 'admin')
            <div class="mb-4 p-3 bg-yellow-50 text-yellow-800 rounded text-sm">
                Hati-hati: ini role yang sedang Anda pakai. Menghapus centang <code>system.manage</code> akan mengunci Anda sendiri dari halaman ini.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.system.roles.update', $role) }}" class="bg-white p-4 rounded shadow">
            @csrf
            @method('PUT')

            @foreach ($permissions as $group => $items)
                <div class="mb-5 pb-5 border-b last:border-b-0 last:mb-0 last:pb-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">{{ str_replace('-', ' ', $group) }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($items as $permission)
                            <label class="inline-flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                       class="mr-2" @checked(in_array($permission->name, $assigned))>
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('admin.system.roles.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
