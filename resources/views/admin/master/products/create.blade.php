<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="page-title mb-4">Tambah Product</h1>
        <form method="POST" action="{{ route('admin.master.products.store') }}" class="panel panel-body">
            @csrf
            @php($item = null)

            <div class="form-group">
                <label class="form-label">SKU *</label>
                <input type="text" name="sku" value="{{ old('sku', $item->sku ?? '') }}" class="form-control @error('sku') is-invalid @enderror">
                @error('sku') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama *</label>
                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Category *</label>
                <select name="category_id" class="form-control @error('category_id') is-invalid @enderror">
                    <option value="">-- Pilih Category --</option>
                    @foreach ($categorys as $opt)
                        <option value="{{ $opt->id }}" @selected(old('category_id', $item->category_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Unit *</label>
                <select name="unit_id" class="form-control @error('unit_id') is-invalid @enderror">
                    <option value="">-- Pilih Unit --</option>
                    @foreach ($units as $opt)
                        <option value="{{ $opt->id }}" @selected(old('unit_id', $item->unit_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
                @error('unit_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description ?? '') }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" checked> Aktif
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.master.products.index') }}" class="btn btn-outline">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>