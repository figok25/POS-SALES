@php
    $canManage = auth()->user()?->can('inventory.manage');
    $canBulk = $canManage && $draftCount > 0;
    $hasFilter = filled($status);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
    $batchStats = $batchStats ?? collect();

    // Buka lagi modal "Buat draft" hanya jika galat berasal dari form tersebut
    // (bukan dari Apply Massal, dsb.).
    $reopen = collect($errors->keys())->contains(
        fn ($k) => in_array($k, ['mode', 'location_type', 'location_id', 'type', 'reason'], true) || str_starts_with($k, 'items')
    );

    // Baris produk dari old input + galat per baris, dikirim ke JS supaya
    // markup baris hanya ada di satu tempat (<template>).
    $oldLines = array_values(old('items', []));
    $lineErrors = [];
    foreach ($errors->getMessages() as $key => $messages) {
        if (preg_match('/^items\.(\d+)\./', $key, $m)) {
            $lineErrors[(int) $m[1]][] = $messages[0];
        }
    }
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Stock Adjustment</h1>
                <p class="frm-sub">Koreksi stok manual: satu produk atau paket berisi banyak produk. Draft → Apply (stok baru berubah setelah di-Apply).</p>
            </div>
            @if ($canManage)
                <button type="button" class="adm-btn adm-btn-primary" data-modal-create>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Buat draft
                </button>
            @endif
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status" data-alert>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                <span class="frm-alert-text">{{ session('status') }}</span>
                <button type="button" class="frm-alert-close" aria-label="Tutup pesan" data-alert-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="frm-alert is-error" role="alert">
                <span class="frm-alert-text">{{ session('error') }}</span>
            </div>
        @endif

        @if (session('bulkApplyFailures'))
            <div class="frm-alert is-error" role="alert">
                <div class="frm-alert-text">
                    <strong>Beberapa draft gagal di-Apply:</strong>
                    <ul class="frm-alert-list">
                        @foreach (session('bulkApplyFailures') as $failure)
                            <li>{{ $failure }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @error('item_ids')
            <div class="frm-alert is-error" role="alert">
                <span class="frm-alert-text">{{ $message }}</span>
            </div>
        @enderror

        <div class="panel">
            {{-- Filter --}}
            <form method="GET" class="frm-toolbar" role="search">
                <select name="status" class="frm-input is-select is-filter" aria-label="Filter status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="applied" @selected($status === 'applied')>Applied</option>
                    <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                </select>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Filter</button>
                    @if ($hasFilter)
                        <a href="{{ route('admin.inventory.adjustments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">{{ $total }} dokumen</span>
            </form>

            {{-- Aksi massal: Apply banyak draft sekaligus (campuran draft tunggal & paket) --}}
            @if ($canBulk && $items->isNotEmpty())
                <div class="frm-bulkbar">
                    <label class="frm-bulkbar-check">
                        <input type="checkbox" id="selectAllDraft">
                        <span>Pilih SEMUA {{ $draftCount }} draft (termasuk yang tidak tampil di halaman ini)</span>
                    </label>
                    <span class="frm-bulkbar-info" id="bulkInfo">0 dipilih</span>
                    <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" id="bulkApplyBtn" disabled>Apply yang dipilih</button>
                </div>
            @endif

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    @if ($hasFilter)
                        <p class="frm-empty-title">Tidak ada dokumen untuk status ini</p>
                        <p class="frm-empty-text">Coba pilih status lain atau hapus filter.</p>
                        <a href="{{ route('admin.inventory.adjustments.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset filter</a>
                    @else
                        <p class="frm-empty-title">Belum ada Stock Adjustment</p>
                        <p class="frm-empty-text">Buat draft pertama untuk mengoreksi stok secara manual.</p>
                        @if ($canManage)
                            <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-modal-create>Buat draft</button>
                        @endif
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('admin.inventory.adjustments.bulk-apply') }}" id="bulkApplyForm">
                    @csrf
                    <input type="hidden" name="select_all_draft" id="selectAllFlag" value="0">

                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    @if ($canBulk)
                                        <th class="is-check"><input type="checkbox" id="checkAllRows" aria-label="Pilih semua draft di halaman ini"></th>
                                    @endif
                                    <th>Product</th>
                                    <th>Lokasi</th>
                                    <th>Tipe</th>
                                    <th class="is-num">Qty</th>
                                    <th>Status</th>
                                    @if ($canManage)
                                        <th class="is-end">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $prevBatch = null; @endphp
                                @foreach ($items as $item)
                                    @php $batch = $item->batch_code; @endphp

                                    {{-- Kepala paket: muncul sekali di awal tiap kelompok baris berkode batch sama --}}
                                    @if ($batch && $batch !== $prevBatch)
                                        @php
                                            $stat = $batchStats[$batch] ?? null;
                                            $bTotal = (int) ($stat->total ?? 0);
                                            $bDrafts = (int) ($stat->drafts ?? 0);
                                        @endphp
                                        <tr class="frm-batch-row">
                                            @if ($canBulk)
                                                <td class="is-check">
                                                    @if ($bDrafts > 0)
                                                        <input type="checkbox" class="adj-batch-check" data-batch="{{ $batch }}" aria-label="Pilih semua draft di paket {{ $batch }}">
                                                    @endif
                                                </td>
                                            @endif
                                            <td colspan="5">
                                                <div class="frm-batch-title">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                                    <span>Paket</span>
                                                    <span class="frm-code">{{ $batch }}</span>
                                                    <span class="frm-batch-meta">
                                                        {{ $bTotal }} produk · {{ $bDrafts }} draft ·
                                                        {{ ucfirst($item->location_type) }}: {{ $item->locationName() }} ·
                                                        {{ $item->type === 'in' ? 'Masuk' : 'Keluar' }}
                                                    </span>
                                                </div>
                                            </td>
                                            @if ($canManage)
                                                <td class="is-end">
                                                    @if ($bDrafts > 0)
                                                        <div class="frm-actions">
                                                            <button type="button" class="frm-icon-btn" title="Apply paket" aria-label="Apply paket {{ $batch }}"
                                                                data-modal-apply
                                                                data-url="{{ route('admin.inventory.adjustments.batch.apply', $batch) }}"
                                                                data-title="Apply paket?"
                                                                data-summary="Paket {{ $batch }} · {{ $bDrafts }} draft">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                            </button>
                                                            <button type="button" class="frm-icon-btn is-danger" title="Hapus draft paket" aria-label="Hapus draft paket {{ $batch }}"
                                                                data-modal-delete
                                                                data-url="{{ route('admin.inventory.adjustments.batch.destroy', $batch) }}"
                                                                data-title="Hapus draft paket?"
                                                                data-text="{{ $bDrafts }} draft di paket {{ $batch }} akan dihapus. Draft yang sudah di-Apply tidak ikut terhapus. Tindakan ini tidak bisa dibatalkan.">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="frm-dash">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endif

                                    <tr @class(['is-child' => (bool) $batch])>
                                        @if ($canBulk)
                                            <td class="is-check">
                                                @if ($item->isDraft())
                                                    <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" class="adj-checkbox" data-batch="{{ $batch }}" aria-label="Pilih draft {{ $item->product->name ?? '' }}">
                                                @endif
                                            </td>
                                        @endif
                                        <td><div class="frm-name">{{ $item->product->name ?? '-' }}</div></td>
                                        <td data-label="Lokasi">{{ ucfirst($item->location_type) }}: {{ $item->locationName() }}</td>
                                        <td data-label="Tipe">
                                            @if ($item->type === 'in')
                                                <span class="frm-status is-on">Masuk</span>
                                            @else
                                                <span class="frm-status is-danger">Keluar</span>
                                            @endif
                                        </td>
                                        <td class="is-num" data-label="Qty"><span class="frm-num">{{ number_format($item->quantity, 2) }}</span></td>
                                        <td class="frm-cell-status">
                                            @if ($item->status === 'draft')
                                                <span class="frm-status is-warn">Draft</span>
                                            @elseif ($item->status === 'applied')
                                                <span class="frm-status is-on">Applied</span>
                                            @else
                                                <span class="frm-status is-off">Cancelled</span>
                                            @endif
                                        </td>
                                        @if ($canManage)
                                            <td class="is-end">
                                                @if ($item->isDraft())
                                                    <div class="frm-actions">
                                                        <button type="button" class="frm-icon-btn" title="Apply" aria-label="Apply dokumen"
                                                            data-modal-apply
                                                            data-url="{{ route('admin.inventory.adjustments.apply', $item) }}"
                                                            data-summary="{{ $item->type === 'in' ? 'Tambah' : 'Kurangi' }} {{ number_format($item->quantity, 2) }} · {{ $item->product->name ?? '-' }} · {{ ucfirst($item->location_type) }}: {{ $item->locationName() }}">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                        </button>
                                                        <button type="button" class="frm-icon-btn is-danger" title="Hapus draft" aria-label="Hapus draft"
                                                            data-modal-delete
                                                            data-url="{{ route('admin.inventory.adjustments.destroy', $item) }}">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="frm-dash">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>

                                    @php $prevBatch = $batch; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>

                @if ($items->hasPages())
                    <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    @if ($canManage)
        {{-- ====================== Modal: buat draft (satu produk / paket) ====================== --}}
        <dialog class="frm-modal" id="adjustmentModal" aria-labelledby="adjustmentModalTitle" data-reopen="{{ $reopen ? 1 : '' }}">
            <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" class="frm-modal-form" id="adjustmentForm">
                @csrf

                <div class="frm-modal-head">
                    <div>
                        <h2 class="frm-modal-title" id="adjustmentModalTitle">Buat draft stock adjustment</h2>
                        <p class="frm-modal-desc">Pilih satu produk saja, atau kelompokkan beberapa produk dalam satu paket.</p>
                    </div>
                    <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-modal-close>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="frm-modal-body">
                    <div class="frm-grid">
                        <div class="frm-field is-full">
                            <span class="frm-label">Jenis input</span>
                            <div class="frm-seg" role="radiogroup" aria-label="Jenis input">
                                <label><input type="radio" name="mode" value="single" @checked(old('mode', 'single') === 'single')><span>Satu produk</span></label>
                                <label><input type="radio" name="mode" value="batch" @checked(old('mode') === 'batch')><span>Paket (banyak produk)</span></label>
                            </div>
                            <p class="frm-hint" id="adjModeHint"></p>
                            @error('mode') <p class="frm-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="adjLocationType">Tipe Lokasi <span class="frm-req">*</span></label>
                            <select id="adjLocationType" name="location_type" required
                                class="frm-input is-select @error('location_type') is-invalid @enderror" @error('location_type') aria-invalid="true" @enderror>
                                <option value="">Pilih tipe lokasi</option>
                                <option value="warehouse" @selected(old('location_type') === 'warehouse')>Warehouse</option>
                                <option value="sales" @selected(old('location_type') === 'sales')>Sales</option>
                            </select>
                            @error('location_type') <p class="frm-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="frm-field">
                            <label class="frm-label" for="adjType">Tipe Adjustment <span class="frm-req">*</span></label>
                            <select id="adjType" name="type" required
                                class="frm-input is-select @error('type') is-invalid @enderror" @error('type') aria-invalid="true" @enderror>
                                <option value="in" @selected(old('type', 'in') === 'in')>Stok Masuk (+)</option>
                                <option value="out" @selected(old('type') === 'out')>Stok Keluar (-)</option>
                            </select>
                            @error('type') <p class="frm-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="frm-field is-full" id="adjWarehouseField" hidden>
                            <label class="frm-label" for="adjWarehouse">Warehouse <span class="frm-req">*</span></label>
                            <select id="adjWarehouse" class="frm-input is-select @error('location_id') is-invalid @enderror">
                                <option value="">Pilih warehouse</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('location_type') === 'warehouse' && old('location_id') == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id') <p class="frm-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="frm-field is-full" id="adjSalesField" hidden>
                            <label class="frm-label" for="adjSales">Sales <span class="frm-req">*</span></label>
                            <select id="adjSales" class="frm-input is-select @error('location_id') is-invalid @enderror">
                                <option value="">Pilih sales</option>
                                @foreach ($salesList as $s)
                                    <option value="{{ $s->id }}" @selected(old('location_type') === 'sales' && old('location_id') == $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id') <p class="frm-error">{{ $message }}</p> @enderror
                        </div>

                        <input type="hidden" id="adjLocationId" name="location_id" value="{{ old('location_id') }}">

                        <div class="frm-field is-full">
                            <span class="frm-label">Produk <span class="frm-req">*</span></span>
                            <div class="frm-lines" id="adjLines"></div>
                            @error('items') <p class="frm-error">{{ $message }}</p> @enderror
                            <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm frm-line-add" data-line-add>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Tambah produk
                            </button>
                        </div>

                        <div class="frm-field is-full">
                            <label class="frm-label" for="adjReason">Alasan</label>
                            <textarea id="adjReason" name="reason" rows="2"
                                class="frm-input is-area @error('reason') is-invalid @enderror" @error('reason') aria-invalid="true" @enderror>{{ old('reason') }}</textarea>
                            @error('reason') <p class="frm-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="frm-modal-foot">
                    <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                    <button type="submit" class="adm-btn adm-btn-primary" data-submit>Simpan draft</button>
                </div>

                {{-- Cetakan satu baris produk (di-clone oleh JS) --}}
                <template id="adjLineTemplate">
                    <div class="adj-prow" data-line>
                        <select data-field="product_id" required class="frm-input is-select" aria-label="Product">
                            <option value="">Pilih product</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                        <input data-field="quantity" type="number" step="0.01" min="0.01" required class="frm-input" placeholder="Qty" aria-label="Quantity">
                        <button type="button" class="frm-icon-btn is-danger" data-line-remove title="Hapus baris" aria-label="Hapus baris">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </form>
        </dialog>

        {{-- ====================== Modal: konfirmasi apply (satu draft / satu paket) ====================== --}}
        <dialog class="frm-modal is-sm" id="applyModal" aria-labelledby="applyModalTitle">
            <form method="POST" class="frm-modal-form" id="applyForm">
                @csrf

                <div class="frm-modal-body frm-confirm">
                    <div class="frm-confirm-icon is-ok">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                    </div>
                    <h2 class="frm-modal-title" id="applyModalTitle">Apply adjustment?</h2>
                    <p class="frm-confirm-text">Stok akan berubah: <strong data-apply-summary></strong>.</p>
                </div>

                <div class="frm-modal-foot">
                    <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                    <button type="submit" class="adm-btn adm-btn-primary" data-submit>Apply</button>
                </div>
            </form>
        </dialog>

        {{-- ====================== Modal: konfirmasi apply massal ====================== --}}
        <dialog class="frm-modal is-sm" id="bulkApplyModal" aria-labelledby="bulkApplyModalTitle">
            <div class="frm-modal-form">
                <div class="frm-modal-body frm-confirm">
                    <div class="frm-confirm-icon is-ok">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                    </div>
                    <h2 class="frm-modal-title" id="bulkApplyModalTitle">Apply <span data-bulk-count>0</span> draft?</h2>
                    <p class="frm-confirm-text">Stok akan berubah untuk setiap draft yang dipilih. Draft yang gagal (mis. stok tidak cukup) dilewati, sedangkan draft lain tetap diproses.</p>
                </div>

                <div class="frm-modal-foot">
                    <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                    <button type="submit" form="bulkApplyForm" class="adm-btn adm-btn-primary" data-submit>Apply semua</button>
                </div>
            </div>
        </dialog>

        {{-- ====================== Modal: konfirmasi hapus (satu draft / satu paket) ====================== --}}
        <dialog class="frm-modal is-sm" id="deleteModal" aria-labelledby="deleteModalTitle">
            <form method="POST" class="frm-modal-form" id="deleteForm">
                @csrf
                @method('DELETE')

                <div class="frm-modal-body frm-confirm">
                    <div class="frm-confirm-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                    </div>
                    <h2 class="frm-modal-title" id="deleteModalTitle">Hapus draft?</h2>
                    <p class="frm-confirm-text" id="deleteModalText">Draft stock adjustment akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.</p>
                </div>

                <div class="frm-modal-foot">
                    <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                    <button type="submit" class="adm-btn adm-btn-danger" data-submit>Hapus</button>
                </div>
            </form>
        </dialog>
    @endif

    <script>
        (function () {
            var modal = document.getElementById('adjustmentModal');
            if (!modal) return; // pengguna tanpa izin inventory.manage: tidak ada modal/aksi

            var form = document.getElementById('adjustmentForm');
            var applyModal = document.getElementById('applyModal');
            var applyForm = document.getElementById('applyForm');
            var applyTitle = document.getElementById('applyModalTitle');
            var bulkModal = document.getElementById('bulkApplyModal');
            var bulkForm = document.getElementById('bulkApplyForm');
            var delModal = document.getElementById('deleteModal');
            var delForm = document.getElementById('deleteForm');
            var delTitle = document.getElementById('deleteModalTitle');
            var delText = document.getElementById('deleteModalText');
            var defaultApplyTitle = applyTitle.textContent;
            var defaultDelTitle = delTitle.textContent;
            var defaultDelText = delText.textContent;

            var typeSelect = document.getElementById('adjLocationType');
            var whField = document.getElementById('adjWarehouseField');
            var whSelect = document.getElementById('adjWarehouse');
            var salesField = document.getElementById('adjSalesField');
            var salesSelect = document.getElementById('adjSales');
            var locationId = document.getElementById('adjLocationId');

            var lines = document.getElementById('adjLines');
            var lineTemplate = document.getElementById('adjLineTemplate');
            var addBtn = form.querySelector('[data-line-add]');
            var modeHint = document.getElementById('adjModeHint');
            var oldLines = @json($oldLines);
            var lineErrors = @json($lineErrors);

            // ---------- Lokasi: tampilkan Warehouse/Sales sesuai Tipe Lokasi ----------
            // keepValue = true dipakai saat modal dibuka ulang setelah validasi gagal.
            function toggleLocation(keepValue) {
                var type = typeSelect.value;
                whField.hidden = type !== 'warehouse';
                salesField.hidden = type !== 'sales';
                whSelect.required = type === 'warehouse';
                salesSelect.required = type === 'sales';
                if (!keepValue) {
                    whSelect.value = '';
                    salesSelect.value = '';
                    locationId.value = '';
                }
            }
            typeSelect.addEventListener('change', function () { toggleLocation(false); });
            whSelect.addEventListener('change', function () { locationId.value = whSelect.value; });
            salesSelect.addEventListener('change', function () { locationId.value = salesSelect.value; });

            // ---------- Jenis input: satu produk / paket ----------
            function currentMode() {
                var checked = form.querySelector('input[name="mode"]:checked');
                return checked ? checked.value : 'single';
            }
            function minRows() { return currentMode() === 'batch' ? 2 : 1; }

            function applyMode() {
                var batch = currentMode() === 'batch';
                addBtn.style.display = batch ? '' : 'none';
                modeHint.textContent = batch
                    ? 'Paket berisi minimal 2 produk. Semua draft-nya berkode batch yang sama dan bisa di-Apply atau dihapus sekaligus.'
                    : 'Satu produk disimpan sebagai draft tunggal, di-Apply sendiri-sendiri.';

                var rows = lines.querySelectorAll('[data-line]');
                if (!batch) {
                    for (var i = rows.length - 1; i >= 1; i--) rows[i].remove();
                } else {
                    while (lines.querySelectorAll('[data-line]').length < 2) addLine();
                }
                renumber();
            }
            form.querySelectorAll('input[name="mode"]').forEach(function (radio) {
                radio.addEventListener('change', applyMode);
            });

            // ---------- Baris produk ----------
            function productSelects() { return Array.from(lines.querySelectorAll('[data-field="product_id"]')); }

            // Produk yang sudah dipilih di baris lain dinonaktifkan (aturan "distinct" di server).
            function syncProductOptions() {
                var chosen = productSelects().map(function (s) { return s.value; }).filter(Boolean);
                productSelects().forEach(function (sel) {
                    Array.from(sel.options).forEach(function (opt) {
                        opt.disabled = opt.value !== '' && opt.value !== sel.value && chosen.indexOf(opt.value) !== -1;
                    });
                });
            }

            // Nama field harus berurutan (items[0], items[1], ...) agar cocok dengan galat dari server.
            function renumber() {
                var rows = lines.querySelectorAll('[data-line]');
                var single = currentMode() === 'single';
                rows.forEach(function (row, i) {
                    row.querySelector('[data-field="product_id"]').name = 'items[' + i + '][product_id]';
                    row.querySelector('[data-field="quantity"]').name = 'items[' + i + '][quantity]';
                    var remove = row.querySelector('[data-line-remove]');
                    remove.disabled = rows.length <= minRows();
                    remove.style.visibility = single ? 'hidden' : 'visible';
                });
                syncProductOptions();
            }

            function addLine(values, errors) {
                var row = lineTemplate.content.firstElementChild.cloneNode(true);
                if (values) {
                    row.querySelector('[data-field="product_id"]').value = values.product_id || '';
                    row.querySelector('[data-field="quantity"]').value = values.quantity || '';
                }
                if (errors && errors.length) {
                    row.querySelectorAll('[data-field]').forEach(function (el) { el.classList.add('is-invalid'); });
                    var msg = document.createElement('p');
                    msg.className = 'frm-error';
                    msg.textContent = errors[0];
                    row.appendChild(msg);
                }
                lines.appendChild(row);
                renumber();
                return row;
            }

            lines.addEventListener('change', function (e) {
                if (e.target.matches('[data-field="product_id"]')) syncProductOptions();
            });

            // ---------- Form ----------
            function clearErrors() {
                form.querySelectorAll('.frm-error').forEach(function (el) { el.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            // Kosongkan form secara eksplisit: form.reset() akan mengembalikan nilai
            // "selected"/"checked" dari old input yang dirender server setelah validasi gagal.
            function clearForm() {
                form.querySelector('input[name="mode"][value="single"]').checked = true;
                form.querySelectorAll('select:not([data-field])').forEach(function (el) { el.selectedIndex = 0; });
                form.querySelector('[name="reason"]').value = '';
                locationId.value = '';
                toggleLocation(false);
                lines.innerHTML = '';
                addLine();
                applyMode();
            }

            // ---------- Apply massal: pilih baris & paket ----------
            var selectAllBox = document.getElementById('selectAllDraft');
            var selectAllFlag = document.getElementById('selectAllFlag');
            var headCheck = document.getElementById('checkAllRows');
            var bulkBtn = document.getElementById('bulkApplyBtn');
            var bulkInfo = document.getElementById('bulkInfo');
            var draftTotal = {{ (int) $draftCount }};

            function rowBoxes() { return Array.from(document.querySelectorAll('.adj-checkbox')); }
            function batchBoxes() { return Array.from(document.querySelectorAll('.adj-batch-check')); }

            function selectedCount() {
                if (selectAllFlag && selectAllFlag.value === '1') return draftTotal;
                return rowBoxes().filter(function (cb) { return cb.checked; }).length;
            }

            // Centang kepala paket mengikuti anak-anaknya (penuh / sebagian / kosong).
            function syncBatchChecks() {
                batchBoxes().forEach(function (bc) {
                    var kids = rowBoxes().filter(function (cb) { return cb.dataset.batch === bc.dataset.batch; });
                    var checked = kids.filter(function (cb) { return cb.checked; }).length;
                    bc.checked = kids.length > 0 && checked === kids.length;
                    bc.indeterminate = checked > 0 && checked < kids.length;
                });
            }

            function updateBulk() {
                if (!bulkBtn) return;
                var n = selectedCount();
                bulkInfo.textContent = n + ' dipilih';
                bulkBtn.disabled = n === 0;

                if (selectAllFlag.value !== '1') {
                    syncBatchChecks();
                    if (headCheck) {
                        var boxes = rowBoxes();
                        var checked = boxes.filter(function (cb) { return cb.checked; }).length;
                        headCheck.checked = boxes.length > 0 && checked === boxes.length;
                        headCheck.indeterminate = checked > 0 && checked < boxes.length;
                    }
                }
            }

            if (headCheck) {
                headCheck.addEventListener('change', function () {
                    rowBoxes().forEach(function (cb) { cb.checked = headCheck.checked; });
                    updateBulk();
                });
            }
            document.addEventListener('change', function (e) {
                var el = e.target;
                if (!el.classList) return;
                if (el.classList.contains('adj-batch-check')) {
                    rowBoxes().forEach(function (cb) {
                        if (cb.dataset.batch === el.dataset.batch) cb.checked = el.checked;
                    });
                    updateBulk();
                } else if (el.classList.contains('adj-checkbox')) {
                    updateBulk();
                }
            });
            if (selectAllBox) {
                selectAllBox.addEventListener('change', function () {
                    var on = selectAllBox.checked;
                    selectAllFlag.value = on ? '1' : '0';
                    // Saat "pilih semua" aktif, server memproses SEMUA draft; centang per baris dikunci.
                    rowBoxes().forEach(function (cb) { cb.checked = on; cb.disabled = on; });
                    batchBoxes().forEach(function (bc) { bc.checked = on; bc.disabled = on; bc.indeterminate = false; });
                    if (headCheck) { headCheck.checked = on; headCheck.disabled = on; headCheck.indeterminate = false; }
                    updateBulk();
                });
            }

            // ---------- Klik: buka/tutup modal ----------
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-modal-create], [data-modal-apply], [data-modal-delete], [data-modal-close], [data-alert-close], [data-line-add], [data-line-remove], #bulkApplyBtn');
                if (!btn) return;

                if (btn.hasAttribute('data-modal-create')) {
                    clearErrors();
                    clearForm();
                    modal.showModal();
                    typeSelect.focus();
                } else if (btn.hasAttribute('data-line-add')) {
                    addLine().querySelector('[data-field="product_id"]').focus();
                } else if (btn.hasAttribute('data-line-remove')) {
                    btn.closest('[data-line]').remove();
                    renumber();
                } else if (btn.hasAttribute('data-modal-apply')) {
                    applyForm.action = btn.dataset.url;
                    applyTitle.textContent = btn.dataset.title || defaultApplyTitle;
                    applyModal.querySelector('[data-apply-summary]').textContent = btn.dataset.summary;
                    applyModal.showModal();
                } else if (btn.id === 'bulkApplyBtn') {
                    if (selectedCount() === 0) return;
                    bulkModal.querySelector('[data-bulk-count]').textContent = selectedCount();
                    bulkModal.showModal();
                } else if (btn.hasAttribute('data-modal-delete')) {
                    delForm.action = btn.dataset.url;
                    delTitle.textContent = btn.dataset.title || defaultDelTitle;
                    delText.textContent = btn.dataset.text || defaultDelText;
                    delModal.showModal();
                } else if (btn.hasAttribute('data-modal-close')) {
                    btn.closest('dialog').close();
                } else if (btn.hasAttribute('data-alert-close')) {
                    btn.closest('[data-alert]').remove();
                }
            });

            // Konfirmasi boleh ditutup lewat klik di luar; form input tidak (supaya isian tidak hilang tanpa sengaja).
            [applyModal, bulkModal, delModal].forEach(function (dlg) {
                var downOnBackdrop = false;
                dlg.addEventListener('mousedown', function (e) { downOnBackdrop = e.target === dlg; });
                dlg.addEventListener('click', function (e) {
                    if (downOnBackdrop && e.target === dlg) dlg.close();
                });
            });

            // Cegah kirim ganda
            var submitPairs = [
                { form: form, btn: form.querySelector('[data-submit]') },
                { form: applyForm, btn: applyForm.querySelector('[data-submit]') },
                { form: delForm, btn: delForm.querySelector('[data-submit]') }
            ];
            if (bulkForm) submitPairs.push({ form: bulkForm, btn: bulkModal.querySelector('[data-submit]') });

            submitPairs.forEach(function (pair) {
                pair.label = pair.btn.textContent;
                pair.form.addEventListener('submit', function () {
                    pair.btn.disabled = true;
                    pair.btn.textContent = 'Memproses…';
                });
            });
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                submitPairs.forEach(function (pair) { pair.btn.disabled = false; pair.btn.textContent = pair.label; });
            });

            // ---------- Inisialisasi ----------
            if (modal.dataset.reopen) {
                // Buka lagi modal setelah validasi server gagal: pulihkan jenis input, baris produk & lokasi.
                toggleLocation(true);
                lines.innerHTML = '';
                if (oldLines.length) {
                    oldLines.forEach(function (values, i) { addLine(values, lineErrors[i]); });
                } else {
                    addLine();
                }
                applyMode();
                modal.showModal();
                var bad = form.querySelector('.is-invalid');
                if (bad) bad.focus();
            } else {
                addLine();
                applyMode();
            }
            updateBulk();

            // Pesan sukses hilang sendiri
            var alertEl = document.querySelector('[data-alert]');
            if (alertEl) {
                setTimeout(function () {
                    alertEl.classList.add('is-leaving');
                    setTimeout(function () { alertEl.remove(); }, 300);
                }, 6000);
            }
        })();
    </script>
</x-admin-layout>
