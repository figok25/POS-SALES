@php
    $reopen = $errors->any() ? old('_mode') : null;      // 'create' | 'edit' | null
    $editId = $reopen === 'edit' ? old('_edit_id') : null;
    $formUrl = $editId
        ? route('admin.system.users.update', $editId)
        : route('admin.system.users.store');

    $hasSearch = filled($search);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <x-slot name="header">Users</x-slot>

    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Users</h1>
                <p class="frm-sub">Akun login Admin &amp; Sales.</p>
            </div>
            <button type="button" class="adm-btn adm-btn-primary" data-modal-create>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Tambah User
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
            <div class="frm-alert" role="alert" data-alert style="color: var(--adm-danger); background: var(--adm-danger-bg);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                <span class="frm-alert-text">{{ session('error') }}</span>
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
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama/email..." autocomplete="off" aria-label="Cari user">
                </div>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                    @if ($hasSearch)
                        <a href="{{ route('admin.system.users.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">
                    @if ($hasSearch)
                        {{ $total }} hasil untuk &ldquo;{{ $search }}&rdquo;
                    @else
                        {{ $total }} user
                    @endif
                </span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    <div class="frm-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    @if ($hasSearch)
                        <p class="frm-empty-title">User tidak ditemukan</p>
                        <p class="frm-empty-text">Coba kata kunci lain atau hapus pencarian.</p>
                        <a href="{{ route('admin.system.users.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                    @else
                        <p class="frm-empty-title">Belum ada user</p>
                        <p class="frm-empty-text">Tambahkan akun login pertama untuk memulai.</p>
                        <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-modal-create>Tambah User</button>
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Depo</th>
                                <th>Terhubung ke Sales</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php $role = $item->roles->pluck('name')->first(); @endphp
                                <tr>
                                    <td><div class="frm-name">{{ $item->name }}</div></td>
                                    <td>{{ $item->email }}</td>
                                    <td>
                                        @if ($role)
                                            <span class="frm-status {{ $role === 'super_admin' ? 'is-on' : ($role === 'admin' ? 'is-on' : 'is-off') }}">{{ $role === 'super_admin' ? 'Super Admin' : ucfirst($role) }}</span>
                                        @else
                                            <span class="frm-dash">Belum ada role</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->branch->name ?? ($role === 'super_admin' ? 'Semua Depo' : '—') }}</td>
                                    <td>{{ $linkedSalesByUserId[$item->id] ?? '—' }}</td>
                                    <td class="is-end">
                                        <div class="frm-actions">
                                            <button type="button" class="frm-icon-btn" title="Edit" aria-label="Edit {{ $item->name }}"
                                                data-modal-edit
                                                data-url="{{ route('admin.system.users.update', $item) }}"
                                                data-id="{{ $item->getRouteKey() }}"
                                                data-name="{{ $item->name }}"
                                                data-email="{{ $item->email }}"
                                                data-role="{{ $role }}"
                                                data-branch-id="{{ $item->branch_id }}"
                                                data-linked-sales="{{ $linkedSalesByUserId[$item->id] ?? '' }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            </button>
                                            @if ($item->id !== auth()->id())
                                                <button type="button" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $item->name }}"
                                                    data-modal-delete
                                                    data-url="{{ route('admin.system.users.destroy', $item) }}"
                                                    data-name="{{ $item->name }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                </button>
                                            @endif
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
    <dialog class="frm-modal" id="userModal" aria-labelledby="userModalTitle" data-reopen="{{ $reopen }}">
        <form method="POST" action="{{ $formUrl }}" class="frm-modal-form" id="userForm" data-store-url="{{ route('admin.system.users.store') }}">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled($reopen !== 'edit')>
            <input type="hidden" name="_mode" value="{{ $reopen ?? 'create' }}">
            <input type="hidden" name="_edit_id" value="{{ $editId }}">
            <input type="hidden" name="_linked_sales" value="{{ $reopen === 'edit' ? old('_linked_sales') : '' }}">

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="userModalTitle">{{ $reopen === 'edit' ? 'Edit User' : 'Tambah User' }}</h2>
                    <p class="frm-modal-desc" id="userModalDesc">{{ $reopen === 'edit' ? 'Perbarui data user.' : 'Buat akun login baru.' }}</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                <p class="frm-hint" id="userLinkedHint" hidden style="margin-bottom: 14px;"></p>

                <div class="frm-grid">
                    <div class="frm-field is-full">
                        <label class="frm-label" for="usrName">Nama <span class="frm-req">*</span></label>
                        <input id="usrName" type="text" name="name" value="{{ old('name') }}" required
                            class="frm-input @error('name') is-invalid @enderror" @error('name') aria-invalid="true" @enderror>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="usrEmail">Email <span class="frm-req">*</span></label>
                        <input id="usrEmail" type="email" name="email" value="{{ old('email') }}" required
                            class="frm-input @error('email') is-invalid @enderror" @error('email') aria-invalid="true" @enderror>
                        @error('email') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="usrRole">Role <span class="frm-req">*</span></label>
                        <select id="usrRole" name="role" required class="frm-input is-select @error('role') is-invalid @enderror">
                            <option value="super_admin" @selected(old('role') === 'super_admin')>Super Admin</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            <option value="sales" @selected(old('role') === 'sales')>Sales</option>
                        </select>
                        @error('role') <p class="frm-error">{{ $message }}</p> @enderror
                        <p class="frm-hint">Untuk akun Sales yang terhubung ke data Sales tertentu (dipakai tracking &amp; rute), buat lewat Master Data &gt; Sales -- form itu sekalian membuat akun ini. Buat di sini hanya untuk akun umum (mis. Admin tambahan).</p>
                    </div>

                    <div class="frm-field is-full" id="usrBranchField">
                        <label class="frm-label" for="usrBranch">Depo / Branch <span class="frm-req">*</span></label>
                        <select id="usrBranch" name="branch_id" class="frm-input is-select @error('branch_id') is-invalid @enderror">
                            <option value="">- Pilih Depo -</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" @selected((string) old('branch_id') === (string) $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <p class="frm-error">{{ $message }}</p> @enderror
                        <p class="frm-hint">Admin/Sales wajib terikat satu Depo. Super Admin bersifat global, field ini otomatis dikosongkan &amp; dikunci untuk role tsb.</p>
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="usrPassword"><span id="usrPasswordLabel">Password</span> <span class="frm-req" id="usrPasswordReq">*</span></label>
                        <input id="usrPassword" type="password" name="password"
                            class="frm-input @error('password') is-invalid @enderror" @error('password') aria-invalid="true" @enderror>
                        @error('password') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="usrPasswordConfirm">Konfirmasi Password</label>
                        <input id="usrPasswordConfirm" type="password" name="password_confirmation" class="frm-input">
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
                <h2 class="frm-modal-title" id="deleteModalTitle">Hapus user?</h2>
                <p class="frm-confirm-text"><strong data-delete-name></strong> akan dihapus. Akun ini tidak bisa login lagi setelahnya.</p>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-danger" data-submit>Hapus</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            var modal = document.getElementById('userModal');
            var form = document.getElementById('userForm');
            var delModal = document.getElementById('deleteModal');
            var delForm = document.getElementById('deleteForm');
            var linkedHint = document.getElementById('userLinkedHint');
            var roleSelect = document.getElementById('usrRole');
            var branchField = document.getElementById('usrBranchField');
            var branchSelect = document.getElementById('usrBranch');
            var pwLabel = document.getElementById('usrPasswordLabel');
            var pwReq = document.getElementById('usrPasswordReq');
            var pwInput = document.getElementById('usrPassword');

            function clearErrors() {
                form.querySelectorAll('.frm-error').forEach(function (el) { el.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            // Super Admin global -> field Depo disembunyikan & dikosongkan
            // (submit kosong, cocok dengan rule 'prohibited' di UserRequest).
            // Admin/Sales -> field Depo wajib tampil & diisi.
            function syncBranchField() {
                var isSuperAdmin = roleSelect.value === 'super_admin';
                branchField.style.display = isSuperAdmin ? 'none' : '';
                branchSelect.required = !isSuperAdmin;
                branchSelect.disabled = isSuperAdmin;
                if (isSuperAdmin) branchSelect.value = '';
            }
            roleSelect.addEventListener('change', syncBranchField);

            function setLinked(salesName) {
                var hiddenRole = form.querySelector('input[name="role"][type="hidden"]');
                if (hiddenRole) hiddenRole.remove();
                form.elements['_linked_sales'].value = salesName || '';

                if (salesName) {
                    roleSelect.value = 'sales';
                    roleSelect.disabled = true;
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'role';
                    hidden.value = 'sales';
                    form.appendChild(hidden);

                    linkedHint.hidden = false;
                    linkedHint.textContent = 'User ini terhubung ke akun Sales "' + salesName + '". Role terkunci ke Sales -- untuk melepas, kelola dari Master Data > Sales.';
                } else {
                    roleSelect.disabled = false;
                    linkedHint.hidden = true;
                    linkedHint.textContent = '';
                }
            }

            function setPasswordMode(edit) {
                pwInput.required = !edit;
                pwReq.style.display = edit ? 'none' : '';
                pwLabel.textContent = edit ? 'Password Baru' : 'Password';
                pwInput.value = '';
                document.getElementById('usrPasswordConfirm').value = '';
            }

            function fill(data) {
                form.elements['name'].value = data.name || '';
                form.elements['email'].value = data.email || '';
                roleSelect.value = data.role || 'admin';
                branchSelect.value = data.branchId || '';
                syncBranchField();
                setLinked(data.linkedSales || '');
            }

            function setMode(mode, url, editId) {
                var edit = mode === 'edit';
                form.action = url;
                form.elements['_mode'].value = mode;
                form.elements['_edit_id'].value = editId || '';
                form.elements['_method'].disabled = !edit;
                document.getElementById('userModalTitle').textContent = edit ? 'Edit User' : 'Tambah User';
                document.getElementById('userModalDesc').textContent = edit ? 'Perbarui data user.' : 'Buat akun login baru.';
                setPasswordMode(edit);
                syncBranchField();
            }

            function openModal() {
                modal.showModal();
                form.elements['name'].focus();
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

            var downOnBackdrop = false;
            delModal.addEventListener('mousedown', function (e) { downOnBackdrop = e.target === delModal; });
            delModal.addEventListener('click', function (e) {
                if (downOnBackdrop && e.target === delModal) delModal.close();
            });

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
                setMode(modal.dataset.reopen, form.action);
                if (modal.dataset.reopen === 'edit') {
                    setLinked(@json(old('_linked_sales') ?? ''));
                }
                modal.showModal();
                var bad = form.querySelector('.is-invalid');
                if (bad) bad.focus();
            }

            var alertEls = document.querySelectorAll('[data-alert]');
            alertEls.forEach(function (alertEl) {
                setTimeout(function () {
                    alertEl.classList.add('is-leaving');
                    setTimeout(function () { alertEl.remove(); }, 300);
                }, 6000);
            });
        })();
    </script>
</x-admin-layout>
