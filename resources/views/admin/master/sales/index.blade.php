@php
    $reopen = $errors->any() ? old('_mode') : null;      // 'create' | 'edit' | null
    $editId = $reopen === 'edit' ? old('_edit_id') : null;
    $formUrl = $editId
        ? route('admin.master.sales.update', $editId)
        : route('admin.master.sales.store');

    $hasSearch = filled($search);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Sales</h1>
                <p class="frm-sub">Kelola data master sales dan akun login Sales App.</p>
            </div>
            <div class="frm-head-actions">
                <a href="{{ route('admin.master.sales.export', request()->query()) }}" class="adm-btn adm-btn-ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                    Download laporan
                </a>
                <button type="button" class="adm-btn adm-btn-primary" data-modal-create>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah sales
                </button>
            </div>
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
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari sales..." autocomplete="off" aria-label="Cari sales">
                </div>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                    @if ($hasSearch)
                        <a href="{{ route('admin.master.sales.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">
                    @if ($hasSearch)
                        {{ $total }} hasil untuk “{{ $search }}”
                    @else
                        {{ $total }} sales
                    @endif
                </span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    @if ($hasSearch)
                        <p class="frm-empty-title">Sales tidak ditemukan</p>
                        <p class="frm-empty-text">Coba kata kunci lain atau hapus pencarian.</p>
                        <a href="{{ route('admin.master.sales.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                    @else
                        <p class="frm-empty-title">Belum ada sales</p>
                        <p class="frm-empty-text">Tambahkan sales pertama untuk memulai.</p>
                        <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-modal-create>Tambah sales</button>
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Branch</th>
                                <th>Telepon</th>
                                <th>Email Login</th>
                                <th>Status</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><span class="frm-code">{{ $item->code }}</span></td>
                                    <td><div class="frm-name">{{ $item->name }}</div></td>
                                    <td>
                                        @if ($item->branch)
                                            {{ $item->branch->name }}
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
                                    <td>
                                        @if ($item->user)
                                            <span class="frm-clamp" title="{{ $item->user->email }}">{{ $item->user->email }}</span>
                                        @else
                                            <span class="frm-dash">Belum ada akun</span>
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
                                            <button type="button" class="frm-icon-btn" title="Edit" aria-label="Edit {{ $item->name }}"
                                                data-modal-edit
                                                data-url="{{ route('admin.master.sales.update', $item) }}"
                                                data-id="{{ $item->getRouteKey() }}"
                                                data-branch-id="{{ $item->branch_id }}"
                                                data-code="{{ $item->code }}"
                                                data-name="{{ $item->name }}"
                                                data-phone="{{ $item->phone }}"
                                                data-email="{{ $item->user->email ?? '' }}"
                                                data-has-user="{{ $item->user ? 1 : 0 }}"
                                                data-active="{{ $item->is_active ? 1 : 0 }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            </button>
                                            <button type="button" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $item->name }}"
                                                data-modal-delete
                                                data-url="{{ route('admin.master.sales.destroy', $item) }}"
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
                    <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- ====================== Modal: tambah / edit ====================== --}}
    <dialog class="frm-modal" id="salesModal" aria-labelledby="salesModalTitle" data-reopen="{{ $reopen }}">
        <form method="POST" action="{{ $formUrl }}" class="frm-modal-form" id="salesForm" data-store-url="{{ route('admin.master.sales.store') }}" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled($reopen !== 'edit')>
            <input type="hidden" name="_mode" value="{{ $reopen ?? 'create' }}">
            <input type="hidden" name="_edit_id" value="{{ $editId }}">
            {{-- Hanya untuk teks bantuan di form (apakah sales ini sudah punya akun) --}}
            <input type="hidden" name="_has_user" value="{{ $reopen === 'edit' ? old('_has_user') : '' }}">

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="salesModalTitle">{{ $reopen === 'edit' ? 'Edit sales' : 'Tambah sales' }}</h2>
                    <p class="frm-modal-desc" id="salesModalDesc">{{ $reopen === 'edit' ? 'Perbarui data sales.' : 'Isi data sales baru.' }}</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                <div class="frm-grid">
                    <div class="frm-field">
                        <label class="frm-label" for="slsBranch">Branch</label>
                        <select id="slsBranch" name="branch_id"
                            class="frm-input is-select @error('branch_id') is-invalid @enderror" @error('branch_id') aria-invalid="true" @enderror>
                            <option value="">Pilih branch</option>
                            @foreach ($branchs as $opt)
                                <option value="{{ $opt->id }}" @selected(old('branch_id') == $opt->id)>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="slsCode">Kode <span class="frm-req">*</span></label>
                        <input id="slsCode" type="text" name="code" value="{{ old('code') }}" required
                            class="frm-input @error('code') is-invalid @enderror" @error('code') aria-invalid="true" @enderror>
                        @error('code') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="slsName">Nama <span class="frm-req">*</span></label>
                        <input id="slsName" type="text" name="name" value="{{ old('name') }}" required
                            class="frm-input @error('name') is-invalid @enderror" @error('name') aria-invalid="true" @enderror>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="slsPhone">Telepon</label>
                        <input id="slsPhone" type="text" name="phone" value="{{ old('phone') }}" inputmode="tel"
                            class="frm-input @error('phone') is-invalid @enderror" @error('phone') aria-invalid="true" @enderror>
                        @error('phone') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Akun login --}}
                    <div class="frm-field is-full frm-section">
                        <p class="frm-section-title">Akun Login (Sales App)</p>
                        <p class="frm-hint" id="slsAccountHint">Opsional saat pembuatan. Isi Email &amp; Password kalau sales ini perlu langsung bisa login. Bisa juga diisi belakangan lewat Edit.</p>
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="slsEmail">Email</label>
                        <input id="slsEmail" type="email" name="email" value="{{ old('email') }}" placeholder="sales1@perusahaan.com" autocomplete="off"
                            class="frm-input @error('email') is-invalid @enderror" @error('email') aria-invalid="true" @enderror>
                        @error('email') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="slsPassword" id="slsPasswordLabel">Password</label>
                        <input id="slsPassword" type="text" name="password" value="{{ old('password') }}" placeholder="Minimal 4 karakter" autocomplete="new-password"
                            class="frm-input @error('password') is-invalid @enderror" @error('password') aria-invalid="true" @enderror>
                        @error('password') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-switch">
                            <input type="checkbox" name="is_active" value="1" @checked($reopen ? old('is_active') : true)>
                            <span class="frm-switch-track" aria-hidden="true"></span>
                            <span class="frm-switch-text">Aktif</span>
                        </label>
                        <p class="frm-hint">Nonaktifkan untuk menandai sales yang sudah tidak bertugas.</p>
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
                <h2 class="frm-modal-title" id="deleteModalTitle">Hapus sales?</h2>
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
            var modal = document.getElementById('salesModal');
            var form = document.getElementById('salesForm');
            var delModal = document.getElementById('deleteModal');
            var delForm = document.getElementById('deleteForm');
            // dataset memakai camelCase: data-branch-id -> branchId
            var fields = [
                { el: 'branch_id', key: 'branchId' },
                { el: 'code', key: 'code' },
                { el: 'name', key: 'name' },
                { el: 'phone', key: 'phone' },
                { el: 'email', key: 'email' }
            ];

            var hintEl = document.getElementById('slsAccountHint');
            var pwLabel = document.getElementById('slsPasswordLabel');
            var pwInput = form.elements['password'];

            function clearErrors() {
                form.querySelectorAll('.frm-error').forEach(function (el) { el.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            function fill(data) {
                fields.forEach(function (f) { form.elements[f.el].value = data[f.key] || ''; });
                form.elements['is_active'].checked = data.active === undefined ? true : data.active === '1';
                pwInput.value = ''; // password tidak pernah diisi ulang
            }

            // Teks bantuan akun login menyesuaikan mode & status akun
            function setAccountHint(edit, hasUser) {
                if (!edit) {
                    hintEl.textContent = 'Opsional saat pembuatan. Isi Email & Password kalau sales ini perlu langsung bisa login. Bisa juga diisi belakangan lewat Edit.';
                    pwLabel.textContent = 'Password';
                    pwInput.placeholder = 'Minimal 4 karakter';
                } else if (hasUser) {
                    hintEl.textContent = 'Sales ini sudah punya akun login. Kosongkan Password kalau tidak ingin menggantinya.';
                    pwLabel.textContent = 'Ganti Password (opsional)';
                    pwInput.placeholder = 'Kosongkan jika tidak diganti';
                } else {
                    hintEl.textContent = 'Sales ini BELUM punya akun login. Isi Email & Password untuk membuatkan sekarang.';
                    pwLabel.textContent = 'Password';
                    pwInput.placeholder = 'Minimal 4 karakter';
                }
            }

            function setMode(mode, url, editId, hasUser) {
                var edit = mode === 'edit';
                form.action = url;
                form.elements['_mode'].value = mode;
                form.elements['_edit_id'].value = editId || '';
                form.elements['_has_user'].value = edit ? (hasUser ? '1' : '0') : '';
                form.elements['_method'].disabled = !edit;
                document.getElementById('salesModalTitle').textContent = edit ? 'Edit sales' : 'Tambah sales';
                document.getElementById('salesModalDesc').textContent = edit ? 'Perbarui data sales.' : 'Isi data sales baru.';
                setAccountHint(edit, hasUser);
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
                    setMode('create', form.dataset.storeUrl, '', false);
                    openModal();
                } else if (btn.hasAttribute('data-modal-edit')) {
                    clearErrors();
                    fill(btn.dataset);
                    setMode('edit', btn.dataset.url, btn.dataset.id, btn.dataset.hasUser === '1');
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
                setAccountHint(modal.dataset.reopen === 'edit', form.elements['_has_user'].value === '1');
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