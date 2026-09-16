<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="page-title mb-4">Edit Company</h1>
        <form method="POST" action="{{ route('admin.master.companies.update', $item) }}" class="panel panel-body">
            @csrf
            @method('PUT')

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
                <label class="form-label">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $item->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror">
                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="text" name="email" value="{{ old('email', $item->email ?? '') }}" class="form-control @error('email') is-invalid @enderror">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">NPWP</label>
                <input type="text" name="npwp" value="{{ old('npwp', $item->npwp ?? '') }}" class="form-control @error('npwp') is-invalid @enderror">
                @error('npwp') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Aktif
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.master.companies.index') }}" class="btn btn-outline">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>