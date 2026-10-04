@php
    $reopen = $errors->any() ? old('_mode') : null;      // 'create' | 'edit' | null
    $editId = $reopen === 'edit' ? old('_edit_id') : null;
    $formUrl = $editId
        ? route('admin.master.companies.update', $editId)
        : route('admin.master.companies.store');

    $hasSearch = filled($search);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Company</h1>
                <p class="frm-sub">Kelola data master company.</p>
            </div>
            @if (auth()->user()->isSuperAdmin())
                <button type="button" class="adm-btn adm-btn-primary" data-modal-create>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah company
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

        <div class="panel">
            {{-- Pencarian --}}
            <form method="GET" class="frm-toolbar" role="search">
                <div class="frm-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari company..." autocomplete="off" aria-label="Cari company">
                </div>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                    @if ($hasSearch)
                        <a href="{{ route('admin.master.companies.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">
                    @if ($hasSearch)
                        {{ $total }} hasil untuk “{{ $search }}”
                    @else
                        {{ $total }} company
                    @endif
                </span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
                    </div>
                    @if ($hasSearch)
                        <p class="frm-empty-title">Company tidak ditemukan</p>
                        <p class="frm-empty-text">Coba kata kunci lain atau hapus pencarian.</p>
                        <a href="{{ route('admin.master.companies.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                    @else
                        <p class="frm-empty-title">Belum ada company</p>
                        @if (auth()->user()->isSuperAdmin())
                            <p class="frm-empty-text">Tambahkan company pertama untuk memulai.</p>
                            <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-modal-create>Tambah company</button>
                        @endif
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kontak</th>
                                <th>NPWP</th>
                                <th>Status</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><span class="frm-code">{{ $item->code }}</span></td>
                                    <td>
                                        <div class="frm-name">{{ $item->name }}</div>
                                        @if ($item->address)
                                            <p class="frm-meta frm-clamp" title="{{ $item->address }}">{{ $item->address }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->phone || $item->email)
                                            @if ($item->phone)<span class="frm-line">{{ $item->phone }}</span>@endif
                                            @if ($item->email)<span class="frm-line">{{ $item->email }}</span>@endif
                                        @else
                                            <span class="frm-dash">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->npwp)
                                            {{ $item->npwp }}
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
                                        @if (auth()->user()->isSuperAdmin())
                                            <div class="frm-actions">
                                                <button type="button" class="frm-icon-btn" title="Edit" aria-label="Edit {{ $item->name }}"
                                                    data-modal-edit
                                                    data-url="{{ route('admin.master.companies.update', $item) }}"
                                                    data-id="{{ $item->getRouteKey() }}"
                                                    data-code="{{ $item->code }}"
                                                    data-name="{{ $item->name }}"
                                                    data-address="{{ $item->address }}"
                                                    data-phone="{{ $item->phone }}"
                                                    data-email="{{ $item->email }}"
                                                    data-npwp="{{ $item->npwp }}"
                                                    data-active="{{ $item->is_active ? 1 : 0 }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                </button>
                                                <button type="button" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $item->name }}"
                                                    data-modal-delete
                                                    data-url="{{ route('admin.master.companies.destroy', $item) }}"
                                                    data-name="{{ $item->name }}">
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

    {{-- ====================== Modal: tambah / edit ====================== --}}
    <dialog class="frm-modal" id="companyModal" aria-labelledby="companyModalTitle" data-reopen="{{ $reopen }}">
        <form method="POST" action="{{ $formUrl }}" class="frm-modal-form" id="companyForm" data-store-url="{{ route('admin.master.companies.store') }}">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled($reopen !== 'edit')>
            <input type="hidden" name="_mode" value="{{ $reopen ?? 'create' }}">
            <input type="hidden" name="_edit_id" value="{{ $editId }}">

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="companyModalTitle">{{ $reopen === 'edit' ? 'Edit company' : 'Tambah company' }}</h2>
                    <p class="frm-modal-desc" id="companyModalDesc">{{ $reopen === 'edit' ? 'Perbarui data company.' : 'Isi data company baru.' }}</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                <div class="frm-grid">
                    <div class="frm-field">
                        <label class="frm-label" for="cmpCode">Kode <span class="frm-req">*</span></label>
                        <input id="cmpCode" type="text" name="code" value="{{ old('code') }}" required
                            class="frm-input @error('code') is-invalid @enderror" @error('code') aria-invalid="true" @enderror>
                        @error('code') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="cmpPhone">Telepon</label>
                        <input id="cmpPhone" type="text" name="phone" value="{{ old('phone') }}" inputmode="tel"
                            class="frm-input @error('phone') is-invalid @enderror" @error('phone') aria-invalid="true" @enderror>
                        @error('phone') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="cmpName">Nama <span class="frm-req">*</span></label>
                        <input id="cmpName" type="text" name="name" value="{{ old('name') }}" required
                            class="frm-input @error('name') is-invalid @enderror" @error('name') aria-invalid="true" @enderror>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="cmpEmail">Email</label>
                        <input id="cmpEmail" type="email" name="email" value="{{ old('email') }}"
                            class="frm-input @error('email') is-invalid @enderror" @error('email') aria-invalid="true" @enderror>
                        @error('email') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="cmpNpwp">NPWP</label>
                        <input id="cmpNpwp" type="text" name="npwp" value="{{ old('npwp') }}"
                            class="frm-input @error('npwp') is-invalid @enderror" @error('npwp') aria-invalid="true" @enderror>
                        @error('npwp') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="cmpAddress">Alamat</label>
                        <textarea id="cmpAddress" name="address" rows="3"
                            class="frm-input is-area @error('address') is-invalid @enderror" @error('address') aria-invalid="true" @enderror>{{ old('address') }}</textarea>
                        @error('address') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-switch">
                            <input type="checkbox" name="is_active" value="1" @checked($reopen ? old('is_active') : true)>
                            <span class="frm-switch-track" aria-hidden="true"></span>
                            <span class="frm-switch-text">Aktif</span>
                        </label>
                        <p class="frm-hint">Nonaktifkan untuk menandai company yang sudah tidak dipakai.</p>
                    </div>
                </div>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-primary" data-submit>Simpan</button>
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
                <h2 class="frm-modal-title" id="deleteModalTitle">Hapus company?</h2>
                <p class="frm-confirm-text"><strong data-delete-name></strong> akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.</p>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-danger" data-submit>Hapus</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            var modal = document.getElementById('companyModal');
            var form = document.getElementById('companyForm');
            var delModal = document.getElementById('deleteModal');
            var delForm = document.getElementById('deleteForm');
            var fields = ['code', 'name', 'address', 'phone', 'email', 'npwp'];

            function clearErrors() {
                form.querySelectorAll('.frm-error').forEach(function (el) { el.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            function fill(data) {
                fields.forEach(function (name) { form.elements[name].value = data[name] || ''; });
                form.elements['is_active'].checked = data.active === undefined ? true : data.active === '1';
            }

            function setMode(mode, url, editId) {
                var edit = mode === 'edit';
                form.action = url;
                form.elements['_mode'].value = mode;
                form.elements['_edit_id'].value = editId || '';
                form.elements['_method'].disabled = !edit;
                document.getElementById('companyModalTitle').textContent = edit ? 'Edit company' : 'Tambah company';
                document.getElementById('companyModalDesc').textContent = edit ? 'Perbarui data company.' : 'Isi data company baru.';
            }

            function openModal() {
                modal.showModal();
                form.elements['code'].focus();
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-modal-create], [data-modal-edit], [data-modal-delete], [data-modal-close], [data-alert-close]');
                if (!btn) return;

                if (btn.hasAttribute('data-modal-create')) {
                    clearErrors();
                    fill({});
                    setMode('create', form.dataset.storeUrl);
                    openModal();
                } else if (btn.hasAttribute('data-modal-edit')) {
                    clearErrors();
                    fill(btn.dataset);
                    setMode('edit', btn.dataset.url, btn.dataset.id);
                    openModal();
                } else if (btn.hasAttribute('data-modal-delete')) {
                    delForm.action = btn.dataset.url;
                    delModal.querySelector('[data-delete-name]').textContent = btn.dataset.name;
                    delModal.showModal();
                } else if (btn.hasAttribute('data-modal-close')) {
                    btn.closest('dialog').close();
                } else if (btn.hasAttribute('data-alert-close')) {
                    btn.closest('[data-alert]').remove();
                }
            });

            // Konfirmasi hapus boleh ditutup lewat klik di luar; form input tidak (supaya isian tidak hilang tanpa sengaja).
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
                form.querySelector('[data-submit]').textContent = 'Simpan';
                delForm.querySelector('[data-submit]').disabled = false;
                delForm.querySelector('[data-submit]').textContent = 'Hapus';
            });

            // Buka lagi modal jika validasi server gagal
            if (modal.dataset.reopen) {
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