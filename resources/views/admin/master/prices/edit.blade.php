<x-admin-layout>
    <div class="p-6 max-w-xl">
        <h1 class="page-title mb-4">Edit Price</h1>
        <form method="POST" action="{{ route('admin.master.prices.update', $item) }}" class="panel panel-body">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Product *</label>
                <select name="product_id" class="form-control @error('product_id') is-invalid @enderror">
                    <option value="">-- Pilih Product --</option>
                    @foreach ($products as $opt)
                        <option value="{{ $opt->id }}" @selected(old('product_id', $item->product_id ?? null) == $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
                @error('product_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama Harga (mis. Harga Umum) *</label>
                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah *</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount', $item->amount ?? '') }}" class="form-control @error('amount') is-invalid @enderror">
                @error('amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Aktif
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.master.prices.index') }}" class="btn btn-outline">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>