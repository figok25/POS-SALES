<x-admin-layout>
    <x-slot name="header">Atur Permission - {{ ucfirst($role->name) }}</x-slot>

    <div class="frm-page" style="max-width: 860px;">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Atur Permission: <span style="text-transform: capitalize;">{{ $role->name }}</span></h1>
                <p class="frm-sub">Centang permission yang boleh dimiliki role ini. Dipakai middleware <code>permission:xxx</code> di tiap route modul bisnis.</p>
            </div>
            <a href="{{ route('admin.system.roles.index') }}" class="adm-btn adm-btn-ghost">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="frm-alert" role="alert" style="color: var(--adm-danger); background: var(--adm-danger-bg);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                <span class="frm-alert-text">Terjadi kesalahan, periksa kembali isian Anda.</span>
            </div>
        @endif

        @if ($role->name === 'admin')
            <div class="frm-alert" role="status" style="color: var(--adm-danger); background: var(--adm-danger-bg); margin-bottom: 16px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                <span class="frm-alert-text">Hati-hati: kalau Anda sendiri login sebagai Admin, melepas centang <code>system.manage</code> akan mengunci Anda dari halaman ini.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.system.roles.update', $role) }}" class="panel" style="padding: 20px 24px;">
            @csrf
            @method('PUT')

            @foreach ($permissions as $group => $groupItems)
                <div class="frm-section" style="{{ $loop->first ? 'margin-top: 0; padding-top: 0; border-top: 0;' : '' }}">
                    <p class="frm-section-title" style="text-transform: capitalize;">{{ str_replace('-', ' ', $group) }}</p>
                    <div class="frm-grid" style="margin-top: 10px;">
                        @foreach ($groupItems as $permission)
                            <label class="frm-switch" style="gap: 10px;">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, $assigned))>
                                <span class="frm-switch-track" aria-hidden="true"></span>
                                <span class="frm-switch-text" style="font-weight: 600; font-size: 13.5px;">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="frm-modal-foot" style="margin: 20px -24px -20px; border-radius: 0 0 16px 16px;">
                <a href="{{ route('admin.system.roles.index') }}" class="adm-btn adm-btn-ghost">Batal</a>
                <button type="submit" class="adm-btn adm-btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-admin-layout>
