<x-admin-layout>
    <div class="frm-page" style="max-width: 760px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Check-in Bantuan untuk Sales</h1>
                <p class="frm-sub">Untuk kunjungan yang sudah dilakukan Sales tetapi belum ter-check-in (mis. lupa atau GPS bermasalah). Tidak ada batas radius 150 m. Kunjungan tercatat sebagai milik <strong>Sales yang dipilih</strong> pada waktu kunjungan sebenarnya, dengan penanda <em>Check-in by {{ auth()->user()->isSuperAdmin() ? 'Superadmin' : 'Admin' }}</em>. Check-in asli Sales tidak diubah.</p>
            </div>
            <a href="{{ route('admin.sales.visits.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        <div class="panel" style="margin-bottom:16px;">
            <form method="GET" action="{{ route('admin.sales.visits.create-for-sales') }}" style="padding:16px 22px;display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
                <div class="frm-field" style="margin:0;min-width:240px">
                    <label class="frm-label" for="pickSales">Sales <span class="frm-req">*</span></label>
                    <select id="pickSales" name="sales_id" class="frm-input is-select" onchange="this.form.submit()">
                        <option value="">- Pilih Sales -</option>
                        @foreach ($salesList as $s)
                            <option value="{{ $s->id }}" @selected($sales && $sales->id === $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button class="adm-btn adm-btn-ghost">Pilih</button></noscript>
            </form>
        </div>

        @if ($sales)
            <div class="panel">
                <form method="POST" action="{{ route('admin.sales.visits.store-for-sales') }}">
                    @csrf
                    <input type="hidden" name="sales_id" value="{{ $sales->id }}">
                    <div class="frm-modal-body" style="padding: 22px;">
                        <div class="frm-grid">
                            <div class="frm-field">
                                <label class="frm-label" for="vfCustomer">Outlet / Customer <span class="frm-req">*</span></label>
                                <select id="vfCustomer" name="customer_id" class="frm-input is-select" required>
                                    <option value="">- Pilih Outlet -</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" @selected((int) old('customer_id') === (int) $c->id)>{{ $c->name }} ({{ $c->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="vfCondition">Kondisi Outlet <span class="frm-req">*</span></label>
                                <select id="vfCondition" name="condition" class="frm-input is-select" required>
                                    @foreach (\App\Models\Visit::CONDITIONS as $cond)
                                        <option value="{{ $cond }}" @selected(old('condition', 'normal') === $cond)>{{ \App\Models\Visit::conditionLabel($cond) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="vfDate">Tanggal Kunjungan (sebenarnya) <span class="frm-req">*</span></label>
                                <input id="vfDate" type="date" name="visit_date" required max="{{ now()->toDateString() }}" value="{{ old('visit_date', now()->toDateString()) }}" class="frm-input">
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="vfTime">Jam Kunjungan <span class="frm-req">*</span></label>
                                <input id="vfTime" type="time" name="visit_time" required value="{{ old('visit_time', now()->format('H:i')) }}" class="frm-input">
                            </div>
                            <div class="frm-field" style="grid-column:1/-1">
                                <label class="frm-label" for="vfReason">Catatan / Alasan Check-in Bantuan <span class="frm-req">*</span></label>
                                <textarea id="vfReason" name="reason" rows="3" required maxlength="500" class="frm-input" placeholder="mis. Sales lupa check-in, GPS error">{{ old('reason') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="frm-modal-foot">
                        <a href="{{ route('admin.sales.visits.index') }}" class="adm-btn adm-btn-ghost">Batal</a>
                        <button type="submit" class="adm-btn adm-btn-primary">Simpan Check-in Bantuan</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-admin-layout>
