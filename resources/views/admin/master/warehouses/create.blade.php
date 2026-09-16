<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="page-title mb-4">Tambah Warehouse</h1>
        <form method="POST" action="{{ route('admin.master.warehouses.store') }}" class="panel panel-body">
            @csrf
            @php($item = null)

            <div class="form-group">
                <label class="form-label">Branch *</label>
                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                    <option value="">-- Pilih Branch --</option>
                    @foreach ($branchs as $opt)
                        <option value="{{ $opt->id }}" @selected(old('branch_id', $item->branch_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Kode *</label>
                <input type="text" name="code" value="{{ old('code', $item->code ?? '') }}" class="form-control @error('code') is-invalid @enderror">
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $item->address ?? '') }}</textarea>
                @error('address') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" checked> Aktif
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.master.warehouses.index') }}" class="btn btn-outline">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>