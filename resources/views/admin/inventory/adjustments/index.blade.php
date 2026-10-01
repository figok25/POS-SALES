@php
    $reopen = $errors->any();
    $hasFilter = filled($status);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Stock Adjustment</h1>
                <p class="frm-sub">Koreksi stok manual. Draft -> Apply (stok baru berubah setelah di-Apply).</p>
            </div>
            <button type="button" class="adm-btn adm-btn-primary" data-modal-create>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Buat draft
            </button>
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
            <div class="frm-alert" role="alert" style="color: var(--adm-danger); background: var(--adm-danger-bg);">
                <span class="frm-alert-text">{{ session('error') }}</span>
            </div>
        @endif

        <div class="panel">
            {{-- Filter --}}
            <form method="GET" class="frm-toolbar" role="search">
                <select name="status" class="frm-input is-select" style="width:auto; height:40px;" onchange="this.form.submit()">
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
                        <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-modal-create>Buat draft</button>
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Lokasi</th>
                                <th>Tipe</th>
                                <th class="is-end">Qty</th>
                                <th>Status</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="frm-name">{{ $item->product->name ?? '-' }}</td>
                                    <td>{{ ucfirst($item->location_type) }}: {{ $item->locationName() }}</td>
                                    <td>{{ $item->type === 'in' ? 'Masuk' : 'Keluar' }}</td>
                                    <td class="is-end">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="frm-cell-status">
                                        @if ($item->status === 'draft')
                                            <span class="frm-status" style="color:#b4730a; background:#fdf3e0;">Draft</span>
                                        @elseif ($item->status === 'applied')
                                            <span class="frm-status is-on">Applied</span>
                                        @else
                                            <span class="frm-status is-off">Cancelled</span>
                                        @endif
                                    </td>
                                    <td class="is-end">
                                        @if ($item->status === 'draft')
                                            <div class="frm-actions">
                                                <form action="{{ route('admin.inventory.adjustments.apply', $item) }}" method="POST" onsubmit="return confirm('Apply dokumen ini? Stok akan berubah.')">
                                                    @csrf
                                                    <button type="submit" class="frm-icon-btn" title="Apply" aria-label="Apply dokumen">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                    </button>
                                                </form>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- ====================== Modal: buat draft ====================== --}}
    <dialog class="frm-modal" id="adjustmentModal" aria-labelledby="adjustmentModalTitle" data-reopen="{{ $reopen ? 1 : '' }}">
        <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" class="frm-modal-form" id="adjustmentForm">
            @csrf

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="adjustmentModalTitle">Buat draft Stock Adjustment</h2>
                    <p class="frm-modal-desc">Dokumen tersimpan sebagai Draft. Stok baru berubah setelah di-Apply.</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                <div class="frm-grid">
                    <div class="frm-field is-full">
                        <label class="frm-label" for="adjProduct">Product <span class="frm-req">*</span></label>
                        <select id="adjProduct" name="product_id" required
                            class="frm-input is-select @error('product_id') is-invalid @enderror" @error('product_id') aria-invalid="true" @enderror>
                            <option value="">Pilih product</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                        @error('product_id') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="adjLocationType">Tipe Lokasi <span class="frm-req">*</span></label>
                        <select id="adjLocationType" name="location_type" required
                            class="frm-input is-select @error('location_type') is-invalid @enderror" @error('location_type') aria-invalid="true" @enderror
                            onchange="window.__toggleAdjLocation()">
                            <option value="">Pilih tipe</option>
                            <option value="warehouse" @selected(old('location_type') === 'warehouse')>Warehouse</option>
                            <option value="sales" @selected(old('location_type') === 'sales')>Sales</option>
                        </select>
                        @error('location_type') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="adjType">Tipe Adjustment <span class="frm-req">*</span></label>
                        <select id="adjType" name="type" required class="frm-input is-select">
                            <option value="in" @selected(old('type') === 'in')>Stok Masuk (+)</option>
                            <option value="out" @selected(old('type') === 'out')>Stok Keluar (-)</option>
                        </select>
                    </div>

                    <div class="frm-field is-full" id="adjWarehouseField" style="display:none">
                        <label class="frm-label" for="adjWarehouse">Warehouse <span class="frm-req">*</span></label>
                        <select id="adjWarehouse" class="frm-input is-select" onchange="window.__syncAdjLocationId(this.value)">
                            <option value="">Pilih warehouse</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="frm-field is-full" id="adjSalesField" style="display:none">
                        <label class="frm-label" for="adjSales">Sales <span class="frm-req">*</span></label>
                        <select id="adjSales" class="frm-input is-select" onchange="window.__syncAdjLocationId(this.value)">
                            <option value="">Pilih sales</option>
                            @foreach ($salesList as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" id="adjLocationId" name="location_id" value="{{ old('location_id') }}">
                    @error('location_id') <p class="frm-error" style="grid-column: 1 / -1;">{{ $message }}</p> @enderror

                    <div class="frm-field">
                        <label class="frm-label" for="adjQuantity">Quantity <span class="frm-req">*</span></label>
                        <input id="adjQuantity" type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity') }}" required
                            class="frm-input @error('quantity') is-invalid @enderror" @error('quantity') aria-invalid="true" @enderror>
                        @error('quantity') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="adjReason">Alasan</label>
                        <textarea id="adjReason" name="reason" rows="3" class="frm-input is-area">{{ old('reason') }}</textarea>
                        @error('reason') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-primary" data-submit>Simpan draft</button>
            </div>
        </form>
    </dialog>

    {{-- ====================== Modal: konfirmasi hapus ====================== --}}
    <dialog class="frm-modal is-sm" id="deleteModal" aria-labelledby="deleteModalTitle">
        <form method="POST" class="frm-modal-form" id="deleteForm">
            @csrf
            @method('DELETE')

            <div class="frm-modal-body frm-confirm">
                <div class="frm-confirm-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                </div>
                <h2 class="frm-modal-title" id="deleteModalTitle">Hapus draft ini?</h2>
                <p class="frm-confirm-text">Draft Stock Adjustment akan dihapus. Tindakan ini tidak bisa dibatalkan.</p>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-danger" data-submit>Hapus</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            var modal = document.getElementById('adjustmentModal');
            var form = document.getElementById('adjustmentForm');
            var delModal = document.getElementById('deleteModal');
            var delForm = document.getElementById('deleteForm');

            // ---- Toggle Warehouse/Sales mengikuti Tipe Lokasi (sama seperti halaman create lama) ----
            window.__toggleAdjLocation = function () {
                var type = document.getElementById('adjLocationType').value;
                document.getElementById('adjWarehouseField').style.display = type === 'warehouse' ? 'block' : 'none';
                document.getElementById('adjSalesField').style.display = type === 'sales' ? 'block' : 'none';
                document.getElementById('adjLocationId').value = '';
            };
            window.__syncAdjLocationId = function (value) {
                document.getElementById('adjLocationId').value = value;
            };

            function clearErrors() {
                form.querySelectorAll('.frm-error').forEach(function (el) { el.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            function openModal() {
                modal.showModal();
                document.getElementById('adjProduct').focus();
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-modal-create], [data-modal-delete], [data-modal-close], [data-alert-close]');
                if (!btn) return;

                if (btn.hasAttribute('data-modal-create')) {
                    clearErrors();
                    form.reset();
                    window.__toggleAdjLocation();
                    openModal();
                } else if (btn.hasAttribute('data-modal-delete')) {
                    delForm.action = btn.dataset.url;
                    delModal.showModal();
                } else if (btn.hasAttribute('data-modal-close')) {
                    btn.closest('dialog').close();
                } else if (btn.hasAttribute('data-alert-close')) {
                    btn.closest('[data-alert]').remove();
                }
            });

            // Konfirmasi hapus boleh ditutup lewat klik di luar; form input tidak.
            var downOnBackdrop = false;
            delModal.addEventListener('mousedown', function (e) { downOnBackdrop = e.target === delModal; });
            delModal.addEventListener('click', function (e) {
                if (downOnBackdrop && e.target === delModal) delModal.close();
            });

            // Cegah kirim ganda
            [form, delForm].forEach(function (f) {
                f.addEventListener('submit', function () {
                    var b = f.querySelector('[data-submit]');
                    b.disabled = true;
                    b.textContent = 'Memproses…';
                });
            });
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                form.querySelector('[data-submit]').disabled = false;
                form.querySelector('[data-submit]').textContent = 'Simpan draft';
                delForm.querySelector('[data-submit]').disabled = false;
                delForm.querySelector('[data-submit]').textContent = 'Hapus';
            });

            // Buka lagi modal jika validasi server gagal (hanya ada mode create, tidak ada edit)
            if (modal.dataset.reopen) {
                window.__toggleAdjLocation();
                modal.showModal();
                var bad = form.querySelector('.is-invalid');
                if (bad) bad.focus();
            }

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
