<x-admin-layout>
    <x-slot name="header">Roles</x-slot>

    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Roles</h1>
                <p class="frm-sub">Sistem ini hanya mengenal 2 role tetap (admin &amp; sales) -- keduanya menentukan grup menu yang bisa diakses. Yang bisa diatur di sini adalah permission detail per role.</p>
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
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Jumlah User</th>
                            <th>Jumlah Permission</th>
                            <th class="is-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td><span class="frm-name" style="text-transform: capitalize;">{{ $role->name }}</span></td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td class="is-end">
                                    <a href="{{ route('admin.system.roles.edit', $role) }}" class="adm-btn adm-btn-ghost adm-btn-sm">Atur Permission</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var alertEls = document.querySelectorAll('[data-alert]');
            alertEls.forEach(function (el) {
                var closeBtn = el.querySelector('[data-alert-close]');
                if (closeBtn) closeBtn.addEventListener('click', function () { el.remove(); });
                setTimeout(function () {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                }, 6000);
            });
        })();
    </script>
</x-admin-layout>
