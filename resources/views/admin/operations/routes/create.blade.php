<x-admin-layout>
    <div class="frm-page is-narrow">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Tambah Route</h1>
                <p class="frm-sub">Pelanggan dan jadwal kunjungan ditambahkan setelah rute disimpan.</p>
            </div>
            <a href="{{ route('admin.operations.routes.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">Route belum bisa disimpan. Periksa isian yang ditandai merah.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.operations.routes.store') }}" class="panel">
            @csrf

            <div class="panel-head">
                <h2 class="panel-title">Data Master Rute</h2>
            </div>

            <div class="frm-panel-body">
                <div class="frm-grid">
                    <div class="frm-field">
                        <label class="frm-label" for="code">Kode Rute <span class="frm-req">*</span></label>
                        <input id="code" type="text" name="code" value="{{ old('code') }}" @class(['frm-input', 'is-invalid' => $errors->has('code')])>
                        @error('code') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="name">Nama Rute <span class="frm-req">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" @class(['frm-input', 'is-invalid' => $errors->has('name')])>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="route_type">Jenis Rute <span class="frm-opt">(opsional)</span></label>
                        <input id="route_type" type="text" name="route_type" list="route-type-options" value="{{ old('route_type') }}"
                               placeholder="mis. Reguler, Canvassing..." @class(['frm-input', 'is-invalid' => $errors->has('route_type')])>
                        <datalist id="route-type-options">
                            @foreach ($routeTypes as $type)
                                <option value="{{ $type }}">
                            @endforeach
                        </datalist>
                        @error('route_type') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="sales_id">Salesman <span class="frm-opt">(opsional)</span></label>
                        <select id="sales_id" name="sales_id" @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('sales_id')])>
                            <option value="">Tidak ditentukan</option>
                            @foreach ($salesList as $sales)
                                <option value="{{ $sales->id }}" @selected(old('sales_id') == $sales->id)>{{ $sales->name }}</option>
                            @endforeach
                        </select>
                        @error('sales_id') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="area">Area <span class="frm-opt">(opsional)</span></label>
                        <textarea id="area" name="area" rows="2" @class(['frm-input', 'is-area', 'is-invalid' => $errors->has('area')])>{{ old('area') }}</textarea>
                        @error('area') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="description">Keterangan <span class="frm-opt">(opsional)</span></label>
                        <textarea id="description" name="description" rows="2" @class(['frm-input', 'is-area', 'is-invalid' => $errors->has('description')])>{{ old('description') }}</textarea>
                        @error('description') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-switch">
                            <input type="checkbox" name="is_active" value="1" @checked($errors->any() ? old('is_active') : true)>
                            <span class="frm-switch-track"></span>
                            <span class="frm-switch-text">Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="frm-panel-foot">
                <a href="{{ route('admin.operations.routes.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>