<x-admin-layout>
    <div class="frm-page is-narrow">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Buat Draft Delivery Order</h1>
                <p class="frm-sub">Buat DO manual dari transaksi penjualan yang belum memiliki Delivery Order.</p>
            </div>
            <a href="{{ route('admin.operations.delivery-orders.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        {{-- Notifikasi --}}
        @if ($errors->any())
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">Draft belum bisa disimpan. Periksa isian yang ditandai merah.</span>
            </div>
        @endif
        @if (session('error'))
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.operations.delivery-orders.store') }}" class="frm-stack">
            @csrf

            {{-- Transaksi --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Transaksi</h2>
                </div>
                <div class="frm-panel-body">
                    <div class="frm-field">
                        <label class="frm-label" for="sales_transaction_id">Sales Transaction <span class="frm-req">*</span></label>
                        <select id="sales_transaction_id" name="sales_transaction_id" required
                                @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('sales_transaction_id')])>
                            <option value="">Pilih transaksi (belum ada DO)</option>
                            @foreach ($transactions as $t)
                                <option value="{{ $t->id }}" @selected(old('sales_transaction_id') == $t->id)>
                                    {{ $t->code }} - {{ $t->customer->name ?? '-' }} (Rp {{ number_format($t->total, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        @error('sales_transaction_id')
                            <p class="frm-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($transactions->isEmpty())
                        <div class="frm-alert is-warn" role="status">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                            <span class="frm-alert-text">Tidak ada transaksi yang siap dibuatkan Delivery Order.</span>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Pengiriman --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Pengiriman</h2>
                    <span class="frm-count">Semua isian opsional</span>
                </div>
                <div class="frm-panel-body">
                    <div class="frm-grid">
                        <div class="frm-field">
                            <label class="frm-label" for="vehicle_id">Vehicle <span class="frm-opt">(opsional)</span></label>
                            <select id="vehicle_id" name="vehicle_id" @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('vehicle_id')])>
                                <option value="">Tanpa vehicle</option>
                                @foreach ($vehicles as $v)
                                    <option value="{{ $v->id }}" @selected(old('vehicle_id') == $v->id)>{{ $v->name }} ({{ $v->plate_number }})</option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                                <p class="frm-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="driver_id">Driver <span class="frm-opt">(opsional)</span></label>
                            <select id="driver_id" name="driver_id" @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('driver_id')])>
                                <option value="">Tanpa driver</option>
                                @foreach ($drivers as $d)
                                    <option value="{{ $d->id }}" @selected(old('driver_id') == $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            @error('driver_id')
                                <p class="frm-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="route_id">Route <span class="frm-opt">(opsional)</span></label>
                            <select id="route_id" name="route_id" @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('route_id')])>
                                <option value="">Tanpa route</option>
                                @foreach ($routes as $r)
                                    <option value="{{ $r->id }}" @selected(old('route_id') == $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                            @error('route_id')
                                <p class="frm-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="scheduled_date">Tanggal Jadwal <span class="frm-opt">(opsional)</span></label>
                            <input id="scheduled_date" type="date" name="scheduled_date" value="{{ old('scheduled_date') }}"
                                   @class(['frm-input', 'is-invalid' => $errors->has('scheduled_date')])>
                            @error('scheduled_date')
                                <p class="frm-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="frm-field is-full">
                            <label class="frm-label" for="notes">Catatan <span class="frm-opt">(opsional)</span></label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Instruksi atau keterangan tambahan untuk pengiriman"
                                      @class(['frm-input', 'is-area', 'is-invalid' => $errors->has('notes')])>{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="frm-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="frm-alert is-info" role="note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <span class="frm-alert-text">
                            Item pengiriman otomatis mengikuti item pada Sales Transaction terpilih.
                            Kosongkan Vehicle dan Driver bila barang diserahkan langsung oleh Sales ke toko.
                        </span>
                    </div>
                </div>

                <div class="frm-panel-foot">
                    <a href="{{ route('admin.operations.delivery-orders.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm" @disabled($transactions->isEmpty())>Simpan Draft</button>
                </div>
            </section>
        </form>
    </div>
</x-admin-layout>