@php
    // Saat validasi gagal, Laravel redirect back membawa old input. Dari situ kita
    // tahu modal mana (tambah/edit) yang harus dibuka lagi.
    $reopen = $errors->any() ? old('_modal') : null;
    $reopenId = $reopen === 'edit' ? old('_edit_id') : null;
    $formAction = $reopenId
        ? route('admin.master.vehicles.update', $reopenId)
        : route('admin.master.vehicles.store');
@endphp

<x-admin-layout>
    {{-- ===== Kepala halaman ===== --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Vehicle</h1>
            <p class="frm-sub">Kelola data kendaraan beserta nomor polisinya.</p>
        </div>
        <button type="button" class="adm-btn adm-btn-primary" data-open-create>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Vehicle
        </button>
    </div>

    {{-- ===== Notifikasi ===== --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi" data-alert-close>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- ===== Daftar ===== --}}
    <div class="panel">
        <form method="GET" action="{{ route('admin.master.vehicles.index') }}" class="frm-toolbar" role="search">
            <div class="frm-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari Vehicle..." autocomplete="off" aria-label="Cari Vehicle">
            </div>
            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                @if ($search)
                    <a href="{{ route('admin.master.vehicles.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>
            <span class="frm-count">{{ $items->total() }} data</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Kendaraan</th>
                            <th>Nomor Polisi</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><span class="frm-code">{{ $item->code }}</span></td>
                                <td><span class="frm-name">{{ $item->name }}</span></td>
                                <td>{{ $item->plate_number }}</td>
                                <td>
                                    @if ($item->type)
                                        {{ $item->type }}
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
                                        <button type="button" class="frm-icon-btn" title="Edit" aria-label="Edit {{ $item->name }}"
                                            data-edit
                                            data-url="{{ route('admin.master.vehicles.update', $item) }}"
                                            data-id="{{ $item->id }}"
                                            data-code="{{ $item->code }}"
                                            data-name="{{ $item->name }}"
                                            data-plate_number="{{ $item->plate_number }}"
                                            data-type="{{ $item->type }}"
                                            data-active="{{ $item->is_active ? 1 : 0 }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                        <button type="button" class="frm-icon-btn is-danger" title="Hapus" aria-label="Hapus {{ $item->name }}"
                                            data-delete
                                            data-url="{{ route('admin.master.vehicles.destroy', $item) }}"
                                            data-name="{{ $item->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                @if ($search)
                    <p class="frm-empty-title">Tidak ada hasil</p>
                    <p class="frm-empty-text">Tidak ditemukan Vehicle untuk pencarian “{{ $search }}”.</p>
                    <a href="{{ route('admin.master.vehicles.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                @else
                    <p class="frm-empty-title">Belum ada data</p>
                    <p class="frm-empty-text">Tambahkan Vehicle pertama untuk mulai.</p>
                    <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-open-create>Tambah Vehicle</button>
                @endif
            </div>
        @endif
    </div>

    {{-- ===== Modal tambah / edit ===== --}}
    <dialog class="frm-modal" id="vehicleModal" aria-labelledby="vehicleModalTitle" data-reopen="{{ $reopen }}">
        <form method="POST" action="{{ $formAction }}" class="frm-modal-form" id="vehicleForm">
            @csrf
            <input type="hidden" name="_method" value="PUT" id="vehicleMethod" @disabled($reopen !== 'edit')>
            <input type="hidden" name="_modal" value="{{ $reopen ?? 'create' }}" id="vehicleModalMode">
            <input type="hidden" name="_edit_id" value="{{ $reopenId }}" id="vehicleEditId">

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="vehicleModalTitle">{{ $reopen === 'edit' ? 'Edit Vehicle' : 'Tambah Vehicle' }}</h2>
                    <p class="frm-modal-desc">Kolom bertanda <span class="frm-req">*</span> wajib diisi.</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                <div class="frm-grid">
                    <div class="frm-field">
                        <label class="frm-label" for="f-code">Kode <span class="frm-req">*</span></label>
                        <input type="text" id="f-code" name="code" value="{{ old('code') }}" class="frm-input @error('code') is-invalid @enderror" required>
                        @error('code') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="f-plate_number">Nomor Polisi <span class="frm-req">*</span></label>
                        <input type="text" id="f-plate_number" name="plate_number" value="{{ old('plate_number') }}" class="frm-input @error('plate_number') is-invalid @enderror" required>
                        @error('plate_number') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="f-name">Nama Kendaraan <span class="frm-req">*</span></label>
                        <input type="text" id="f-name" name="name" value="{{ old('name') }}" class="frm-input @error('name') is-invalid @enderror" required>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-label" for="f-type">Tipe</label>
                        <input type="text" id="f-type" name="type" value="{{ old('type') }}" class="frm-input @error('type') is-invalid @enderror">
                        @error('type') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field is-full">
                        <label class="frm-switch">
                            <input type="checkbox" id="f-is_active" name="is_active" value="1" @checked($reopen ? old('is_active') : true)>
                            <span class="frm-switch-track"></span>
                            <span class="frm-switch-text">Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm" id="vehicleSubmit">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- ===== Modal konfirmasi hapus ===== --}}
    <dialog class="frm-modal is-sm" id="deleteModal" aria-labelledby="deleteModalTitle">
        <form method="POST" action="" class="frm-modal-form" id="deleteForm">
            @csrf
            @method('DELETE')
            <div class="frm-confirm">
                <div class="frm-confirm-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="m19 6-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </div>
                <h2 class="frm-modal-title" id="deleteModalTitle">Hapus Vehicle?</h2>
                <p class="frm-confirm-text">Yakin ingin menghapus <strong id="deleteName"></strong>?</p>
            </div>
            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-danger adm-btn-sm" id="deleteSubmit">Hapus</button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const storeUrl = @js(route('admin.master.vehicles.store'));

            const modal = document.getElementById('vehicleModal');
            const form = document.getElementById('vehicleForm');
            const methodInput = document.getElementById('vehicleMethod');
            const modeInput = document.getElementById('vehicleModalMode');
            const editIdInput = document.getElementById('vehicleEditId');
            const titleEl = document.getElementById('vehicleModalTitle');
            const submitBtn = document.getElementById('vehicleSubmit');

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
                titleEl.textContent = isEdit ? 'Edit Vehicle' : 'Tambah Vehicle';
            }

            function clearErrors() {
                form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
                form.querySelectorAll('.frm-error').forEach((el) => el.remove());
            }

            function fill(v) {
                field('code').value = v.code ?? '';
                field('name').value = v.name ?? '';
                field('plate_number').value = v.plate_number ?? '';
                field('type').value = v.type ?? '';
                field('is_active').checked = String(v.active) === '1';
            }

            function openModal(focusEl) {
                modal.showModal();
                (focusEl || field('code')).focus();
            }

            // --- Tambah ---
            document.querySelectorAll('[data-open-create]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    clearErrors();
                    editIdInput.value = '';
                    setMode('create', storeUrl);
                    fill({ active: 1 });
                    openModal();
                });
            });

            // --- Edit ---
            document.querySelectorAll('[data-edit]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const d = btn.dataset;
                    clearErrors();
                    editIdInput.value = d.id;
                    setMode('edit', d.url);
                    fill(d);
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

            // Hilangkan tanda error begitu field diubah.
            form.addEventListener('input', (e) => {
                const el = e.target;
                if (!el.classList.contains('is-invalid')) return;
                el.classList.remove('is-invalid');
                const next = el.nextElementSibling;
                if (next && next.classList.contains('frm-error')) next.remove();
            });

            // Cegah klik ganda saat submit.
            form.addEventListener('submit', () => { submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan…'; });
            delForm.addEventListener('submit', () => { delSubmit.disabled = true; delSubmit.textContent = 'Menghapus…'; });
            window.addEventListener('pageshow', () => {
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
                alertEl.querySelector('[data-alert-close]').addEventListener('click', dismiss);
                setTimeout(dismiss, 5000);
            }

            // Buka lagi modal jika validasi server gagal.
            const reopen = modal.dataset.reopen;
            if (reopen) {
                setMode(reopen, form.action);
                openModal(form.querySelector('.is-invalid'));
            }
        })();
    </script>
</x-admin-layout>