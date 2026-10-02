<x-admin-layout>
    @php $outstanding = $invoice->outstanding(); @endphp

    <div class="frm-page is-narrow">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Catat Payment</h1>
                <p class="frm-sub">Catat pembayaran untuk invoice {{ $invoice->code }}.</p>
            </div>
            <a href="{{ route('admin.sales.invoices.show', $invoice) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error is-block" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <div class="frm-alert-text">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="frm-stack">
            {{-- Ringkasan invoice --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Ringkasan Invoice</h2>
                </div>
                <dl class="frm-detail">
                    <div><dt>Invoice</dt><dd><span class="frm-code">{{ $invoice->code }}</span></dd></div>
                    <div><dt>Customer</dt><dd>{{ $invoice->customer->name ?? '-' }}</dd></div>
                    <div><dt>Grand Total</dt><dd class="frm-num">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</dd></div>
                    <div class="is-total"><dt>Outstanding</dt><dd class="frm-num">Rp {{ number_format($outstanding, 0, ',', '.') }}</dd></div>
                </dl>
            </section>

            {{-- Form payment --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Detail Payment</h2>
                </div>

                <form method="POST" action="{{ route('admin.finance.payments.store', $invoice) }}">
                    @csrf

                    <div class="frm-panel-body">
                        <div class="frm-grid">
                            <div class="frm-field">
                                <label for="amount" class="frm-label">Jumlah (Rp) <span class="frm-req">*</span></label>
                                <input type="number" id="amount" name="amount" required step="0.01" min="0.01" max="{{ $outstanding }}" inputmode="decimal"
                                       value="{{ old('amount', $outstanding) }}"
                                       class="frm-input @error('amount') is-invalid @enderror">
                                @error('amount')<p class="frm-error">{{ $message }}</p>@enderror
                                <p class="frm-hint">Maksimal Rp {{ number_format($outstanding, 0, ',', '.') }} (sisa outstanding).</p>
                            </div>

                            <div class="frm-field">
                                <label for="method" class="frm-label">Metode <span class="frm-req">*</span></label>
                                <select id="method" name="method" required class="frm-input is-select @error('method') is-invalid @enderror">
                                    <option value="cash" @selected(old('method', 'cash') === 'cash')>Cash</option>
                                    <option value="transfer" @selected(old('method') === 'transfer')>Transfer</option>
                                    <option value="other" @selected(old('method') === 'other')>Lainnya</option>
                                </select>
                                @error('method')<p class="frm-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="frm-field">
                                <label for="paid_at" class="frm-label">Tanggal Bayar <span class="frm-req">*</span></label>
                                <input type="date" id="paid_at" name="paid_at" required value="{{ old('paid_at', now()->toDateString()) }}"
                                       class="frm-input @error('paid_at') is-invalid @enderror">
                                @error('paid_at')<p class="frm-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="frm-field">
                                <label for="reference_no" class="frm-label">No. Referensi <span class="frm-opt">(opsional)</span></label>
                                <input type="text" id="reference_no" name="reference_no" value="{{ old('reference_no') }}"
                                       class="frm-input @error('reference_no') is-invalid @enderror">
                                @error('reference_no')<p class="frm-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="frm-field is-full">
                                <label for="notes" class="frm-label">Catatan <span class="frm-opt">(opsional)</span></label>
                                <input type="text" id="notes" name="notes" value="{{ old('notes') }}"
                                       class="frm-input @error('notes') is-invalid @enderror">
                                @error('notes')<p class="frm-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="frm-panel-foot">
                        <a href="{{ route('admin.sales.invoices.show', $invoice) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan Payment</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-admin-layout>