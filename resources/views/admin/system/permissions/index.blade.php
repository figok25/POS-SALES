<x-admin-layout>
    <x-slot name="header">Permissions</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Permissions</h1>
                <p class="frm-sub">Seluruh permission yang dikenali sistem, dikelompokkan per modul. Dipakai langsung di kode lewat middleware <code>permission:xxx</code> pada tiap route.</p>
            </div>
            <a href="{{ route('admin.system.roles.index') }}" class="adm-btn adm-btn-ghost">Atur per Role</a>
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

        @forelse ($permissions as $group => $items)
            <div class="panel" style="margin-bottom: 16px;">
                <div class="panel-head">
                    <h2 class="panel-title" style="text-transform: capitalize;">{{ str_replace('-', ' ', $group) }}</h2>
                    <span class="frm-count">{{ $items->count() }} permission</span>
                </div>
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <tbody>
                            @foreach ($items as $permission)
                                <tr>
                                    <td><span class="frm-code">{{ $permission->name }}</span></td>
                                    <td class="is-end">
                                        @forelse ($permission->roles as $role)
                                            <span class="frm-status {{ $role->name === 'admin' ? 'is-on' : 'is-off' }}" style="margin-left: 6px;">{{ $role->name }}</span>
                                        @empty
                                            <span class="frm-dash">Tidak dimiliki role manapun</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="panel">
                <div class="frm-empty">
                    <p class="frm-empty-title">Belum ada permission terdaftar</p>
                </div>
            </div>
        @endforelse
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
