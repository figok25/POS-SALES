<x-admin-layout>
    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Assign / Reassign Customer</h1>
                <p class="frm-sub">Tentukan Sales penanggung jawab customer dan lihat riwayat perubahannya.</p>
            </div>
            <a href="{{ route('admin.sales.customer-assignments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        <div class="frm-stack">
            {{-- Ringkasan customer --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi Customer</h2>
                </div>
                <dl class="frm-detail">
                    <div>
                        <dt>Kode</dt>
                        <dd><span class="frm-code">{{ $customer->code }}</span></dd>
                    </div>
                    <div>
                        <dt>Nama Customer</dt>
                        <dd>{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt>Sales Saat Ini</dt>
                        <dd>
                            @if ($customer->sales)
                                {{ $customer->sales->name }}
                            @else
                                <span class="frm-status is-warn">Belum ada</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- Form assign --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Ubah Assignment</h2>
                </div>

                <form method="POST" action="{{ route('admin.sales.customer-assignments.update', $customer) }}">
                    @csrf
                    @method('PUT')

                    <div class="frm-panel-body">
                        <div class="frm-field">
                            <label for="sales_id" class="frm-label">Assign ke Sales</label>
                            <select id="sales_id" name="sales_id" class="frm-input is-select @error('sales_id') is-invalid @enderror">
                                <option value="">-- Tidak ada (lepas dari Sales saat ini) --</option>
                                @foreach ($saless as $sales)
                                    <option value="{{ $sales->id }}" @selected((string) old('sales_id', $customer->sales_id) === (string) $sales->id)>{{ $sales->name }}</option>
                                @endforeach
                            </select>
                            @error('sales_id')<p class="frm-error">{{ $message }}</p>@enderror
                            <p class="frm-hint">Pilih "Tidak ada" untuk melepas customer dari Sales saat ini.</p>
                        </div>

                        <div class="frm-field">
                            <label for="reason" class="frm-label">Alasan <span class="frm-opt">(opsional)</span></label>
                            <input type="text" id="reason" name="reason" value="{{ old('reason') }}" maxlength="255"
                                   placeholder="mis. Sales lama resign, area dipindah, dst."
                                   class="frm-input @error('reason') is-invalid @enderror">
                            @error('reason')<p class="frm-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="frm-panel-foot">
                        <a href="{{ route('admin.sales.customer-assignments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan</button>
                    </div>
                </form>
            </section>

            {{-- Riwayat --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Riwayat Assignment</h2>
                    <span class="frm-count">{{ $history->count() }} catatan</span>
                </div>

                @if ($history->count())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    <th>Sales</th>
                                    <th>Ditugaskan</th>
                                    <th>Berakhir</th>
                                    <th>Oleh</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $row)
                                    <tr @class(['is-current' => $row->isCurrent()])>
                                        <td><span class="frm-name">{{ $row->sales->name ?? '-' }}</span></td>
                                        <td data-label="Ditugaskan" class="frm-nowrap">{{ $row->assigned_at->format('d M Y H:i') }}</td>
                                        <td data-label="Berakhir" class="frm-nowrap">
                                            @if ($row->unassigned_at)
                                                {{ $row->unassigned_at->format('d M Y H:i') }}
                                            @else
                                                <span class="frm-status is-on">Saat ini</span>
                                            @endif
                                        </td>
                                        <td data-label="Oleh">{{ $row->assignedBy->name ?? 'Sistem' }}</td>
                                        <td data-label="Alasan">
                                            @if ($row->reason)
                                                <span class="frm-clamp" title="{{ $row->reason }}">{{ $row->reason }}</span>
                                            @else
                                                <span class="frm-dash">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="frm-empty">
                        <div class="frm-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                        <p class="frm-empty-title">Belum ada riwayat</p>
                        <p class="frm-empty-text">Perubahan assignment customer ini akan tercatat di sini.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>