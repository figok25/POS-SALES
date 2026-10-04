@php
    $reopen = $errors->any() ? old('_modal') : null;
    $reopenId = $reopen === 'edit' ? old('_edit_id') : null;
    $formAction = $reopenId
        ? route('admin.master.customers.update', $reopenId)
        : route('admin.master.customers.store');

    $hasSearch = filled($search);
@endphp

<x-admin-layout>
    <div class="frm-page">
    {{-- ===== Kepala halaman ===== --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Customer</h1>
            <p class="frm-sub">Kelola data customer beserta sales penanggung jawabnya.</p>
        </div>
        <div class="frm-head-actions">
            <a href="{{ route('admin.master.customers.export', request()->query()) }}" class="adm-btn adm-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                Download laporan
            </a>
            <button type="button" class="adm-btn adm-btn-primary" data-open-create>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Tambah customer
            </button>
        </div>
    </div>

    {{-- ===== Notifikasi ===== --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi" data-alert-close>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    @error('delete')
        <div class="frm-alert is-error" role="alert">
            <span class="frm-alert-text">{{ $message }}</span>
        </div>
    @enderror

    {{-- ===== Daftar ===== --}}
    <div class="panel">
        <form method="GET" action="{{ route('admin.master.customers.index') }}" class="frm-toolbar" role="search">
            <div class="frm-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari customer..." autocomplete="off" aria-label="Cari customer">
            </div>
            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                @if ($hasSearch)
                    <a href="{{ route('admin.master.customers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>
            <span class="frm-count">
                @if ($hasSearch)
                    {{ $items->total() }} hasil untuk “{{ $search }}”
                @else
                    {{ $items->total() }} customer
                @endif
            </span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Sales</th>
                            <th>Telepon</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><span class="frm-code">{{ $item->code }}</span></td>
                                <td>
                                    <a href="{{ route('admin.master.customers.show', $item) }}" class="frm-name">{{ $item->name }}</a>
                                    @if ($item->address)
                                        <p class="frm-meta frm-clamp" title="{{ $item->address }}">{{ $item->address }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->sales)
                                        {{ $item->sales->name }}
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->phone)
                                        {{ $item->phone }}
                                    @else
                                        <span class="frm-dash">—</span>
                                    @endif
                                </td>
                                <td class="frm-cell-status">
                                    @if ($item->is_active)
                                        <span class="frm-status is-on">Aktif</span>
                                    @else
                                        <span class="frm-status is-off">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="is-end">
                                    <div class="frm-actions">
                                        <a href="{{ route('admin.master.customers.show', $item) }}" class="frm-icon-btn" title="Detail" aria-label="Detail {{ $item->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <button type="button" class="frm-icon-btn" title="Edit" aria-label="Edit {{ $item->name }}"
                                            data-edit
                                            data-url="{{ route('admin.master.customers.update', $item) }}"
                                            data-id="{{ $item->getRouteKey() }}"
                                            data-branch="{{ $item->branch_id }}"
                                            data-sales="{{ $item->sales_id }}"
                                            data-code="{{ $item->code }}"
                                            data-name="{{ $item->name }}"
                                            data-address="{{ $item->address }}"
                                            data-phone="{{ $item->phone }}"
                                            data-npwp="{{ $item->npwp }}"
                                            data-latitude="{{ $item->latitude }}"
                                            data-longitude="{{ $item->longitude }}"
                                            data-active="{{ $item->is_active ? 1 : 0 }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                        <button type="button" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $item->name }}"
                                            data-delete
                                            data-url="{{ route('admin.master.customers.destroy', $item) }}"
                                            data-name="{{ $item->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="frm-pager">{{ $items->links() }}</div>
            @endif
        @else
            <div class="frm-empty">
                <div class="frm-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                @if ($hasSearch)
                    <p class="frm-empty-title">Customer tidak ditemukan</p>
                    <p class="frm-empty-text">Coba kata kunci lain atau hapus pencarian.</p>
                    <a href="{{ route('admin.master.customers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                @else
                    <p class="frm-empty-title">Belum ada customer</p>
                    <p class="frm-empty-text">Tambahkan customer pertama untuk memulai.</p>
                    <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-open-create>Tambah customer</button>
                @endif
            </div>
        @endif
    </div>

    </div>

    {{-- ===== Modal tambah / edit ===== --}}
    <dialog class="frm-modal" id="customerModal" aria-labelledby="customerModalTitle" data-reopen="{{ $reopen }}">
        <form method="POST" action="{{ $formAction }}" class="frm-modal-form" id="customerForm">
            @csrf
            <input type="hidden" name="_method" value="PUT" id="customerMethod" @disabled($reopen !== 'edit')>
            <input type="hidden" name="_modal" value="{{ $reopen ?? 'create' }}" id="customerModalMode">
            <input type="hidden" name="_edit_id" value="{{ $reopenId }}" id="customerEditId">

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="customerModalTitle">{{ $reopen === 'edit' ? 'Edit customer' : 'Tambah customer' }}</h2>
                    <p class="frm-modal-desc" id="customerModalDesc">{{ $reopen === 'edit' ? 'Perbarui data customer.' : 'Isi data customer baru.' }}</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                {{-- Error Branch untuk akun yang tidak melihat dropdown Branch
                     (Admin biasa) -- supaya kegagalan simpan tidak diam-diam. --}}
                @if (! \App\Support\BranchContext::current()->isAll())
                    @error('branch_id')
                        <div class="frm-alert is-error" role="alert"><span class="frm-alert-text">{{ $message }}</span></div>
                    @enderror
                @endif

                <div class="frm-grid">
                    @if (\App\Support\BranchContext::current()->isAll())
                        <div class="frm-field is-full">
                            <label class="frm-label" for="f-branch_id">Branch / Depo <span class="frm-req">*</span></label>
                            <select id="f-branch_id" name="branch_id" class="frm-input is-select @error('branch_id') is-invalid @enderror" @error('branch_id') aria-invalid="true" @enderror>
                                <option value="">Pilih branch</option>
                                @foreach ($branchs as $opt)
                                    <option value="{{ $opt->id }}" @selected((string) old('branch_id') === (string) $opt->id)>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <p class="frm-error">{{ $message }}</p> @enderror
                            <p class="frm-hint">Kalau dikosongkan tapi Sales dipilih, Branch mengikuti Sales tersebut.</p>
                        </div>
                    @endif

                    <div class="frm-field is-full">
                        <label class="frm-label" for="f-sales_id">Sales</label>
                        <select id="f-sales_id" name="sales_id" class="frm-input is-select @error('sales_id') is-invalid @enderror" @error('sales_id') aria-invalid="true" @enderror>
                            <option value="">Pilih sales</option>
                            @foreach ($saless as $opt)
                                <option value="{{ $opt->id }}" data-branch="{{ $opt->branch_id }}" @selected((string) old('sales_id') === (string) $opt->id)>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                        @error('sales_id') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="f-code">Kode <span class="frm-req">*</span></label>
                        <input type="text" id="f-code" name="code" value="{{ old('code') }}" class="frm-input @error('code') is-invalid @enderror" @error('code') aria-invalid="true" @enderror required>
                        @error('code') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="f-name">Nama <span class="frm-req">*</span></label>
                        <input type="text" id="f-name" name="name" value="{{ old('name') }}" class="frm-input @error('name') is-invalid @enderror" @error('name') aria-invalid="true" @enderror required>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="f-phone">Telepon</label>
                        <input type="text" id="f-phone" name="phone" value="{{ old('phone') }}" class="frm-input @error('phone') is-invalid @enderror" @error('phone') aria-invalid="true" @enderror inputmode="tel">
                        @error('phone') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="f-npwp">NPWP</label>
                        <input type="text" id="f-npwp" name="npwp" value="{{ old('npwp') }}" class="frm-input @error('npwp') is-invalid @enderror" @error('npwp') aria-invalid="true" @enderror>
                        @error('npwp') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="f-address">Alamat</label>
                        <textarea id="f-address" name="address" rows="3" class="frm-input is-area @error('address') is-invalid @enderror" @error('address') aria-invalid="true" @enderror>{{ old('address') }}</textarea>
                        @error('address') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Koordinat: hanya tampil saat edit (sama seperti form lama). --}}
                    <div class="frm-field is-full" id="geoFields" @if ($reopen !== 'edit') style="display:none" @endif>
                        <div class="frm-grid">
                            <div class="frm-field">
                                <label class="frm-label" for="f-latitude">Latitude</label>
                                <input type="text" id="f-latitude" name="latitude" value="{{ old('latitude') }}" placeholder="-8.0768309" class="frm-input @error('latitude') is-invalid @enderror" @error('latitude') aria-invalid="true" @enderror @disabled($reopen !== 'edit')>
                                @error('latitude') <p class="frm-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="frm-field">
                                <label class="frm-label" for="f-longitude">Longitude</label>
                                <input type="text" id="f-longitude" name="longitude" value="{{ old('longitude') }}" placeholder="111.7016798" class="frm-input @error('longitude') is-invalid @enderror" @error('longitude') aria-invalid="true" @enderror @disabled($reopen !== 'edit')>
                                @error('longitude') <p class="frm-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="frm-hint">
                            Dipakai Peta Customer di Sales App. Biasanya otomatis terisi saat Admin approve Tagging Toko dari Sales.
                            <a href="#" target="_blank" rel="noopener" id="geoMapLink" hidden>Lihat di Google Maps</a>
                        </p>
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-switch">
                            <input type="checkbox" id="f-is_active" name="is_active" value="1" @checked($reopen ? old('is_active') : true)>
                            <span class="frm-switch-track"></span>
                            <span class="frm-switch-text">Aktif</span>
                        </label>
                        <p class="frm-hint">Nonaktifkan untuk customer yang sudah tidak dilayani.</p>
                    </div>
                </div>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-primary" id="customerSubmit">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- ===== Modal konfirmasi hapus ===== --}}
    <dialog class="frm-modal is-sm" id="deleteModal" aria-labelledby="deleteModalTitle">
        <form method="POST" action="" class="frm-modal-form" id="deleteForm">
            @csrf
            @method('DELETE')
            <div class="frm-modal-body frm-confirm">
                <div class="frm-confirm-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                </div>
                <h2 class="frm-modal-title" id="deleteModalTitle">Hapus customer?</h2>
                <p class="frm-confirm-text"><strong id="deleteName"></strong> akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-danger" id="deleteSubmit">Hapus</button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const storeUrl = @js(route('admin.master.customers.store'));

            const modal = document.getElementById('customerModal');
            const form = document.getElementById('customerForm');
            const methodInput = document.getElementById('customerMethod');
            const modeInput = document.getElementById('customerModalMode');
            const editIdInput = document.getElementById('customerEditId');
            const titleEl = document.getElementById('customerModalTitle');
            const submitBtn = document.getElementById('customerSubmit');
            const geoBox = document.getElementById('geoFields');
            const mapLink = document.getElementById('geoMapLink');

            const delModal = document.getElementById('deleteModal');
            const delForm = document.getElementById('deleteForm');
            const delName = document.getElementById('deleteName');
            const delSubmit = document.getElementById('deleteSubmit');

            const field = (n) => form.elements[n];

            function setMode(mode, url) {
                const isEdit = mode === 'edit';
                form.action = url;
                methodInput.disabled = !isEdit;
                modeInput.value = isEdit ? 'edit' : 'create';
                titleEl.textContent = isEdit ? 'Edit customer' : 'Tambah customer';
                document.getElementById('customerModalDesc').textContent = isEdit ? 'Perbarui data customer.' : 'Isi data customer baru.';
                // Koordinat hanya untuk edit; saat tambah tidak ikut terkirim.
                geoBox.style.display = isEdit ? '' : 'none';
                field('latitude').disabled = !isEdit;
                field('longitude').disabled = !isEdit;
                updateMapLink();
            }

            function updateMapLink() {
                const lat = field('latitude').value.trim();
                const lng = field('longitude').value.trim();
                const show = lat !== '' && lng !== '';
                mapLink.hidden = !show;
                if (show) mapLink.href = 'https://maps.google.com/?q=' + encodeURIComponent(lat + ',' + lng);
            }

            function clearErrors() {
                form.querySelectorAll('.is-invalid').forEach((el) => { el.classList.remove('is-invalid'); el.removeAttribute('aria-invalid'); });
                form.querySelectorAll('.frm-error').forEach((el) => el.remove());
            }

            function fill(v) {
                if (field('branch_id')) field('branch_id').value = v.branch ?? '';
                field('sales_id').value = v.sales ?? '';
                field('code').value = v.code ?? '';
                field('name').value = v.name ?? '';
                field('phone').value = v.phone ?? '';
                field('npwp').value = v.npwp ?? '';
                field('address').value = v.address ?? '';
                field('latitude').value = v.latitude ?? '';
                field('longitude').value = v.longitude ?? '';
                field('is_active').checked = String(v.active) === '1';
            }

            function openModal(focusEl) {
                modal.showModal();
                (focusEl || field('sales_id')).focus();
            }

            // --- Tambah ---
            document.querySelectorAll('[data-open-create]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    clearErrors();
                    editIdInput.value = '';
                    fill({ active: '1' });
                    setMode('create', storeUrl);
                    openModal();
                });
            });

            // --- Edit ---
            document.querySelectorAll('[data-edit]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const d = btn.dataset;
                    clearErrors();
                    editIdInput.value = d.id;
                    fill(d);
                    setMode('edit', d.url);
                    openModal();
                });
            });

            // --- Hapus ---
            document.querySelectorAll('[data-delete]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    delForm.action = btn.dataset.url;
                    delName.textContent = btn.dataset.name;
                    delModal.showModal();
                });
            });

            // --- Tutup: tombol, klik backdrop ---
            document.addEventListener('click', (e) => {
                const closer = e.target.closest('[data-close]');
                if (closer) closer.closest('dialog').close();
            });

            [modal, delModal].forEach((dlg) => {
                let downOnBackdrop = false;
                dlg.addEventListener('mousedown', (e) => { downOnBackdrop = e.target === dlg; });
                dlg.addEventListener('click', (e) => {
                    if (downOnBackdrop && e.target === dlg) dlg.close();
                });
            });

            // Dropdown Sales hanya menampilkan Sales di Branch yang dipilih
            // (Super Admin). Sales dari Branch lain disembunyikan.
            const branchSel = field('branch_id');
            const salesSel = field('sales_id');
            function filterSalesByBranch() {
                if (!branchSel) return;
                const b = branchSel.value;
                Array.from(salesSel.options).forEach((opt) => {
                    if (!opt.value) return;
                    const hide = b !== '' && opt.dataset.branch !== '' && opt.dataset.branch !== b;
                    opt.hidden = hide;
                    opt.disabled = hide;
                });
                if (salesSel.selectedOptions[0]?.disabled) salesSel.value = '';
            }
            if (branchSel) branchSel.addEventListener('change', filterSalesByBranch);
            document.querySelectorAll('[data-open-create], [data-edit]').forEach((btn) =>
                btn.addEventListener('click', () => setTimeout(filterSalesByBranch, 0)));
            filterSalesByBranch();

            // Hilangkan tanda error begitu field diubah; perbarui link peta.
            form.addEventListener('input', (e) => {
                const el = e.target;
                if (el.name === 'latitude' || el.name === 'longitude') updateMapLink();
                if (!el.classList.contains('is-invalid')) return;
                el.classList.remove('is-invalid');
                el.removeAttribute('aria-invalid');
                const next = el.nextElementSibling;
                if (next && next.classList.contains('frm-error')) next.remove();
            });

            // Cegah klik ganda saat submit.
            form.addEventListener('submit', () => { submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan…'; });
            delForm.addEventListener('submit', () => { delSubmit.disabled = true; delSubmit.textContent = 'Menghapus…'; });
            window.addEventListener('pageshow', (e) => {
                if (!e.persisted) return;
                submitBtn.disabled = false; submitBtn.textContent = 'Simpan';
                delSubmit.disabled = false; delSubmit.textContent = 'Hapus';
            });

            // Notifikasi: tutup manual / otomatis.
            const alertEl = document.querySelector('[data-alert]');
            if (alertEl) {
                const dismiss = () => {
                    alertEl.classList.add('is-leaving');
                    setTimeout(() => alertEl.remove(), 300);
                };
                alertEl.querySelector('[data-alert-close]')?.addEventListener('click', dismiss);
                setTimeout(dismiss, 5000);
            }

            // Buka lagi modal jika validasi server gagal.
            const reopen = modal.dataset.reopen;
            if (reopen) {
                setMode(reopen, form.action);
                openModal(form.querySelector('.is-invalid'));
            } else {
                // Dari halaman detail: index?q=KODE&edit=ID langsung membuka modal edit.
                const params = new URLSearchParams(location.search);
                const editId = params.get('edit');
                if (editId) {
                    const btn = document.querySelector('[data-edit][data-id="' + CSS.escape(editId) + '"]');
                    params.delete('edit');
                    const qs = params.toString();
                    history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
                    if (btn) btn.click();
                }
            }
        })();
    </script>
</x-admin-layout>