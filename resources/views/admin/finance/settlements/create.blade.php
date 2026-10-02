<x-admin-layout>
    <div class="frm-page is-narrow">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Buat Draft Settlement</h1>
                <p class="frm-sub">Pilih Sales dan Warehouse tujuan retur untuk memulai settlement.</p>
            </div>
            <a href="{{ route('admin.finance.settlements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">{{ $errors->first() }}</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Draft Baru</h2>
            </div>

            <form method="POST" action="{{ route('admin.finance.settlements.store') }}">
                @csrf

                <div class="frm-panel-body">
                    <div class="frm-grid">
                        <div class="frm-field">
                            <label for="sales_id" class="frm-label">Sales <span class="frm-req">*</span></label>
                            <select id="sales_id" name="sales_id" required class="frm-input is-select @error('sales_id') is-invalid @enderror">
                                @foreach ($saless as $sales)
                                    <option value="{{ $sales->id }}" @selected((string) old('sales_id') === (string) $sales->id)>{{ $sales->name }}</option>
                                @endforeach
                            </select>
                            @error('sales_id')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="frm-field">
                            <label for="warehouse_id" class="frm-label">Warehouse Tujuan Retur <span class="frm-req">*</span></label>
                            <select id="warehouse_id" name="warehouse_id" required class="frm-input is-select @error('warehouse_id') is-invalid @enderror">
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="frm-alert is-info is-block">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <span class="frm-alert-text">
                            Draft akan otomatis menghitung total payment cash yang belum disetor &amp; sisa Sales Stock
                            Sales ini saat ini. Anda bisa mengoreksi qty retur &amp; jumlah setoran di langkah berikutnya
                            sebelum di-Apply.
                        </span>
                    </div>
                </div>

                <div class="frm-panel-foot">
                    <a href="{{ route('admin.finance.settlements.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Buat Draft</button>
                </div>
            </form>
        </section>
    </div>
</x-admin-layout>