<x-admin-layout>
    <div class="p-6">
        <div class="page-header">
            <h1 class="page-title">Branch</h1>
            <a href="{{ route('admin.master.branches.create') }}" class="btn btn-primary">+ Tambah Branch</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="GET" class="search-bar">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari Branch..." class="form-control">
            <button class="btn btn-outline">Cari</button>
        </form>

        <div class="table-wrap">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->company->name ?? '-' }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->phone }}</td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-neutral">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right space-x-3">
                                <a href="{{ route('admin.master.branches.edit', $item) }}" class="link-muted">Edit</a>
                                <form action="{{ route('admin.master.branches.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</x-admin-layout>