<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="page-title mb-4">Tambah Unit</h1>
        <form method="POST" action="{{ route('admin.master.units.store') }}" class="panel panel-body">
            @csrf
            @php($item = null)

            <div class="form-group">
                <label class="form-label">Simbol *</label>
                <input type="text" name="symbol" value="{{ old('symbol', $item->symbol ?? '') }}" class="form-control @error('symbol') is-invalid @enderror">
                @error('symbol') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" checked> Aktif
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.master.units.index') }}" class="btn btn-outline">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>