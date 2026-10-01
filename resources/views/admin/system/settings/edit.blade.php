<x-admin-layout>
    <x-slot name="header">Settings</x-slot>

    <div class="frm-page" style="max-width: 640px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Settings</h1>
                <p class="frm-sub">Pengaturan tampilan umum aplikasi (nama &amp; logo). Berlaku untuk semua user, admin maupun sales.</p>
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
            <form method="POST" action="{{ route('admin.system.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="frm-modal-body" style="padding: 22px 22px 6px;">
                    <div class="frm-grid">
                        <div class="frm-field is-full">
                            <label class="frm-label" for="setAppName">Nama Aplikasi <span class="frm-req">*</span></label>
                            <input id="setAppName" type="text" name="app_name" value="{{ old('app_name', $setting->app_name) }}"
                                class="frm-input @error('app_name') is-invalid @enderror" @error('app_name') aria-invalid="true" @enderror>
                            @error('app_name') <p class="frm-error">{{ $message }}</p> @enderror
                            <p class="frm-hint">Tampil di judul tab browser, dan sebagai teks cadangan di sidebar kalau logo belum diunggah.</p>
                        </div>

                        <div class="frm-field is-full frm-section">
                            <label class="frm-label">Logo</label>

                            @if ($setting->logo_path)
                                <div style="display:flex; align-items:center; gap:14px; margin: 4px 0 14px;">
                                    <img src="{{ $setting->logoUrl() }}" alt="Logo saat ini"
                                         style="height:46px; max-width:220px; object-fit:contain; border:1px solid var(--adm-line); border-radius:10px; padding:4px; background: var(--adm-fill);">
                                    <label class="frm-switch">
                                        <input type="checkbox" name="remove_logo" value="1">
                                        <span class="frm-switch-track" aria-hidden="true"></span>
                                        <span class="frm-switch-text" style="color: var(--adm-danger);">Hapus logo saat ini</span>
                                    </label>
                                </div>
                            @else
                                <p class="frm-hint" style="margin: 4px 0 12px;">Belum ada logo — sidebar menampilkan placeholder dari Nama Aplikasi.</p>
                            @endif

                            <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                class="frm-input @error('logo') is-invalid @enderror" style="padding: 8px 12px; height: auto;">
                            @error('logo') <p class="frm-error">{{ $message }}</p> @enderror
                            <p class="frm-hint">PNG/JPG/SVG/WEBP, maks 2MB. Rasio landscape/lebar lebih cocok untuk sidebar (tinggi maks &asymp;42px).</p>
                        </div>
                    </div>
                </div>

                <div class="frm-modal-foot">
                    <button type="submit" class="adm-btn adm-btn-primary" data-submit>Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var alertEl = document.querySelector('[data-alert]');
            if (!alertEl) return;
            var closeBtn = alertEl.querySelector('[data-alert-close]');
            if (closeBtn) closeBtn.addEventListener('click', function () { alertEl.remove(); });
            setTimeout(function () {
                alertEl.classList.add('is-leaving');
                setTimeout(function () { alertEl.remove(); }, 300);
            }, 6000);
        })();
    </script>
</x-admin-layout>
