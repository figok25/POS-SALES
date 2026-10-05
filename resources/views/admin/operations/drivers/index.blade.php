<x-admin-layout>
    @php
        $canManage = \Illuminate\Support\Facades\Gate::allows('operations.manage');
        $isSearching = filled($search);
    @endphp

    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Driver</h1>
            <p class="frm-sub">Driver adalah Employee yang ditandai sebagai Driver. Data karyawan dikelola di Master Data &gt; Employee.</p>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert="auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" data-alert-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="frm-alert is-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <span class="frm-alert-text">{{ session('error') }}</span>
        </div>
    @endif

    <section class="panel">
        {{-- Pencarian --}}
        <form method="GET" class="frm-toolbar">
            <div class="frm-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama/kode..." aria-label="Cari karyawan">
            </div>
            <div class="frm-toolbar-actions">
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Cari</button>
                @if ($isSearching)
                    <a href="{{ route('admin.operations.drivers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
                @endif
            </div>
            <span class="frm-count">{{ number_format($items->total(), 0, ',', '.') }} karyawan</span>
        </form>

        @if ($items->count())
            <div class="frm-table-wrap">
                <table class="frm-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Telepon</th>
                            <th>Status Driver</th>
                            @if ($canManage)
                                <th class="is-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><span class="frm-code">{{ $item->code }}</span></td>
                                <td><span class="frm-name">{{ $item->name }}</span></td>
                                <td data-label="Jabatan">{{ $item->position ?: '-' }}</td>
                                <td data-label="Telepon" class="frm-nowrap">{{ $item->phone ?: '-' }}</td>
                                <td class="frm-cell-status">
                                    @if ($item->is_driver)
                                        <span class="frm-status is-on">Driver</span>
                                    @else
                                        <span class="frm-status is-off">Bukan Driver</span>
                                    @endif
                                </td>
                                @if ($canManage)
                                    <td class="is-end">
                                        <form action="{{ route('admin.operations.drivers.toggle', $item) }}" method="POST"
                                              @if ($item->is_driver) onsubmit="return confirm(@js('Hapus status Driver dari '.$item->name.'?'))" @endif>
                                            @csrf
                                            <button type="submit" @class(['adm-btn', 'adm-btn-ghost', 'adm-btn-sm', 'is-danger' => $item->is_driver])>
                                                {{ $item->is_driver ? 'Hapus status Driver' : 'Jadikan Driver' }}
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="frm-pager">{{ $items->withQueryString()->links() }}</div>
            @endif
        @else
            <div class="frm-empty">
                <div class="frm-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="frm-empty-title">{{ $isSearching ? 'Tidak ada hasil' : 'Belum ada data karyawan' }}</p>
                <p class="frm-empty-text">{{ $isSearching ? 'Tidak ada karyawan yang cocok dengan pencarian ini.' : 'Tambahkan karyawan di Master Data > Employee, lalu tandai sebagai Driver di sini.' }}</p>
                @if ($isSearching)
                    <a href="{{ route('admin.operations.drivers.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset pencarian</a>
                @endif
            </div>
        @endif
    </section>

    <script>
        (function () {
            // Notifikasi: tombol tutup + hilang sendiri
            document.querySelectorAll('[data-alert]').forEach(function (el) {
                function dismiss() {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                }
                var close = el.querySelector('[data-alert-close]');
                if (close) close.addEventListener('click', dismiss);
                if (el.getAttribute('data-alert') === 'auto') setTimeout(dismiss, 6000);
            });
        })();
    </script>
</x-admin-layout>