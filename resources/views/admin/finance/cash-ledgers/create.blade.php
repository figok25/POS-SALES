<x-admin-layout>
    <div class="frm-page is-narrow">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Tambah Catatan Income/Expense</h1>
                <p class="frm-sub">Catat pemasukan atau pengeluaran kas baru.</p>
            </div>
            <a href="{{ route('admin.finance.cash-ledgers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">Catatan belum tersimpan. Periksa kembali isian di bawah.</span>
            </div>
        @endif

        <section class="panel">
            <div class="panel-head">
                <h2 class="panel-title">Catatan Baru</h2>
            </div>

            <form method="POST" action="{{ route('admin.finance.cash-ledgers.store') }}">
                @csrf

                <div class="frm-panel-body">
                    <div class="frm-grid">
                        <div class="frm-field is-full">
                            @if (auth()->user()->isSuperAdmin())
                                <label for="branch_id" class="frm-label">Depo <span class="frm-req">*</span></label>
                                <select id="branch_id" name="branch_id" required class="frm-input is-select @error('branch_id') is-invalid @enderror">
                                    <option value="">Pilih Depo</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" @selected((string) old('branch_id', $branchContext->isAll() ? '' : $branchContext->branchId()) === (string) $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{-- Admin: Depo terkunci ke akunnya; server mengabaikan nilai dari form. --}}
                                <label class="frm-label">Depo</label>
                                <p class="frm-hint" style="margin:0">{{ auth()->user()->branch->name ?? '-' }}</p>
                            @endif
                            @error('branch_id')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="frm-field">
                            <label for="type" class="frm-label">Tipe <span class="frm-req">*</span></label>
                            <select id="type" name="type" required class="frm-input is-select @error('type') is-invalid @enderror">
                                <option value="income" @selected(old('type', 'expense') === 'income')>Income</option>
                                <option value="expense" @selected(old('type', 'expense') === 'expense')>Expense</option>
                            </select>
                            @error('type')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="frm-field">
                            <label for="date" class="frm-label">Tanggal <span class="frm-req">*</span></label>
                            <input type="date" id="date" name="date" required value="{{ old('date', now()->toDateString()) }}"
                                   class="frm-input @error('date') is-invalid @enderror">
                            @error('date')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="frm-field">
                            <label for="category" class="frm-label">Kategori <span class="frm-req">*</span></label>
                            <input type="text" id="category" name="category" required value="{{ old('category') }}"
                                   placeholder="mis. ATK, Listrik, Sewa"
                                   class="frm-input @error('category') is-invalid @enderror">
                            @error('category')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="frm-field">
                            <label for="amount" class="frm-label">Jumlah (Rp) <span class="frm-req">*</span></label>
                            <input type="number" id="amount" name="amount" required step="0.01" min="0.01" inputmode="decimal"
                                   value="{{ old('amount') }}"
                                   class="frm-input @error('amount') is-invalid @enderror">
                            @error('amount')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="frm-field is-full">
                            <label for="description" class="frm-label">Keterangan <span class="frm-opt">(opsional)</span></label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}"
                                   class="frm-input @error('description') is-invalid @enderror">
                            @error('description')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="frm-panel-foot">
                    <a href="{{ route('admin.finance.cash-ledgers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                    <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan</button>
                </div>
            </form>
        </section>
    </div>
</x-admin-layout>