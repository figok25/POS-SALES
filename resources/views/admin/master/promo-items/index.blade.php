@php
    $reopen = $errors->any() ? old('_mode') : null;      // 'create' | 'edit' | null
    $editId = $reopen === 'edit' ? old('_edit_id') : null;
    $formUrl = $editId
        ? route('admin.master.promo-items.update', $editId)
        : route('admin.master.promo-items.store');

    $hasSearch = filled($search);
    $total = method_exists($items, 'total') ? $items->total() : $items->count();
@endphp

<x-admin-layout>
    <div class="frm-page">

        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Item Promosi / POSM</h1>
                <p class="frm-sub">Daftar item yang dicentang Sales (Ada / Tidak ada) saat check-in kunjungan. Daftar ini dipakai bersama oleh semua Depo.</p>
            </div>
            <button type="button" class="adm-btn adm-btn-primary" data-modal-create>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Tambah item
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

        <div class="panel">
            {{-- Pencarian --}}
            <form method="GET" class="frm-toolbar" role="search">
                <div class="frm-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari item..." autocomplete="off" aria-label="Cari item">
                </div>
                <div class="frm-toolbar-actions">
                    <button type="submit" class="adm-btn adm-btn-ghost adm-btn-sm">Cari</button>
                    @if ($hasSearch)
                        <a href="{{ route('admin.master.promo-items.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                    @endif
                </div>
                <span class="frm-count">
                    @if ($hasSearch)
                        {{ $total }} hasil untuk &ldquo;{{ $search }}&rdquo;
                    @else
                        {{ $total }} item
                    @endif
                </span>
            </form>

            @if ($items->isEmpty())
                <div class="frm-empty">
                    @if ($hasSearch)
                        <p class="frm-empty-title">Item tidak ditemukan</p>
                        <p class="frm-empty-text">Coba kata kunci lain atau hapus pencarian.</p>
                        <a href="{{ route('admin.master.promo-items.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                    @else
                        <p class="frm-empty-title">Belum ada item</p>
                        <p class="frm-empty-text">Tambahkan item pertama agar muncul di form check-in Sales.</p>
                        <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-modal-create>Tambah item</button>
                    @endif
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Nama Item</th>
                                <th class="is-num">Urutan</th>
                                <th>Status</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><div class="frm-name">{{ $item->name }}</div></td>
                                    <td class="is-num" data-label="Urutan">{{ $item->sort_order }}</td>
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
                                                data-url="{{ route('admin.master.promo-items.update', $item) }}"
                                                data-id="{{ $item->getRouteKey() }}"
                                                data-name="{{ $item->name }}"
                                                data-sort="{{ $item->sort_order }}"
                                                data-active="{{ $item->is_active ? 1 : 0 }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
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
    <dialog class="frm-modal" id="promoModal" aria-labelledby="promoModalTitle" data-reopen="{{ $reopen }}">
        <form method="POST" action="{{ $formUrl }}" class="frm-modal-form" id="promoForm" data-store-url="{{ route('admin.master.promo-items.store') }}">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled($reopen !== 'edit')>
            <input type="hidden" name="_mode" value="{{ $reopen ?? 'create' }}">
            <input type="hidden" name="_edit_id" value="{{ $editId }}">

            <div class="frm-modal-head">
                <div>
                    <h2 class="frm-modal-title" id="promoModalTitle">{{ $reopen === 'edit' ? 'Edit item' : 'Tambah item' }}</h2>
                    <p class="frm-modal-desc" id="promoModalDesc">{{ $reopen === 'edit' ? 'Perbarui item promosi/POSM.' : 'Isi item promosi/POSM baru.' }}</p>
                </div>
                <button type="button" class="frm-icon-btn frm-modal-close" aria-label="Tutup" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="frm-modal-body">
                <div class="frm-grid">
                    <div class="frm-field">
                        <label class="frm-label" for="promoName">Nama item <span class="frm-req">*</span></label>
                        <input id="promoName" type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                            class="frm-input @error('name') is-invalid @enderror" @error('name') aria-invalid="true" @enderror>
                        @error('name') <p class="frm-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="frm-field">
                        <label class="frm-label" for="promoSort">Urutan</label>
                        <input id="promoSort" type="number" name="sort_order" value="{{ old('sort_order') }}" min="0" max="9999"
                            class="frm-input @error('sort_order') is-invalid @enderror" @error('sort_order') aria-invalid="true" @enderror>
                        @error('sort_order') <p class="frm-error">{{ $message }}</p> @enderror
                        <p class="frm-hint">Angka kecil tampil lebih atas. Kosongkan untuk ditaruh di paling bawah.</p>
                    </div>

                    <div class="frm-field is-full">
                        <input type="hidden" name="is_active" value="0">
                        <label class="frm-switch">
                            <input type="checkbox" name="is_active" value="1" @checked($reopen ? old('is_active') : true)>
                            <span class="frm-switch-track" aria-hidden="true"></span>
                            <span class="frm-switch-text">Aktif</span>
                        </label>
                        <p class="frm-hint">Item nonaktif tidak muncul di form check-in, tetapi jawaban di kunjungan lama tetap tersimpan.</p>
                    </div>
                </div>
            </div>

            <div class="frm-modal-foot">
                <button type="button" class="adm-btn adm-btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="adm-btn adm-btn-primary" data-submit>Simpan</button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            var modal = document.getElementById('promoModal');
            var form = document.getElementById('promoForm');

            function clearErrors() {
                form.querySelectorAll('.frm-error').forEach(function (el) { el.remove(); });
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                    el.removeAttribute('aria-invalid');
                });
            }

            function setMode(mode, url, editId) {
                var edit = mode === 'edit';
                form.action = url;
                form.elements['_mode'].value = mode;
                form.elements['_edit_id'].value = editId || '';
                form.querySelector('input[name="_method"]').disabled = !edit;
                document.getElementById('promoModalTitle').textContent = edit ? 'Edit item' : 'Tambah item';
                document.getElementById('promoModalDesc').textContent = edit ? 'Perbarui item promosi/POSM.' : 'Isi item promosi/POSM baru.';
            }

            function openModal() {
                modal.showModal();
                form.elements['name'].focus();
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-modal-create], [data-modal-edit], [data-modal-close], [data-alert-close]');
                if (!btn) return;

                if (btn.hasAttribute('data-modal-create')) {
                    clearErrors();
                    form.elements['name'].value = '';
                    form.elements['sort_order'].value = '';
                    form.querySelector('input[type="checkbox"][name="is_active"]').checked = true;
                    setMode('create', form.dataset.storeUrl);
                    openModal();
                } else if (btn.hasAttribute('data-modal-edit')) {
                    clearErrors();
                    form.elements['name'].value = btn.dataset.name || '';
                    form.elements['sort_order'].value = btn.dataset.sort || '';
                    form.querySelector('input[type="checkbox"][name="is_active"]').checked = btn.dataset.active === '1';
                    setMode('edit', btn.dataset.url, btn.dataset.id);
                    openModal();
                } else if (btn.hasAttribute('data-modal-close')) {
                    btn.closest('dialog').close();
                } else if (btn.hasAttribute('data-alert-close')) {
                    btn.closest('[data-alert]').remove();
                }
            });

            // Cegah kirim ganda
            form.addEventListener('submit', function () {
                var b = form.querySelector('[data-submit]');
                b.disabled = true;
                b.textContent = 'Memproses…';
            });
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                var b = form.querySelector('[data-submit]');
                b.disabled = false;
                b.textContent = 'Simpan';
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
