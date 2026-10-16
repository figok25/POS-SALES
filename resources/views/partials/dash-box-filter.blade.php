{{--
    Filter + Export untuk SATU tabel dashboard (Penjualan per Sales / Laporan Global /
    Kunjungan per Sales). Berdiri sendiri: tidak memengaruhi filter utama maupun tabel lain.

    Param: $p (ps|rg|kv), $anchor, $box (hasil resolveBox), $exportBox (sales|report|visits),
           $branches, $boxSalesOptions, $withToggle (opsional).
--}}
@php
    $own = [$p.'_branch', $p.'_from', $p.'_to', $p.'_sales'];
    $isSuperBox = auth()->user()->isSuperAdmin();
    $boxFrom = $box['from']->toDateString();
    $boxTo = $box['to']->toDateString();

    // Export membawa nilai filter box yang SEDANG tampil (termasuk default hari ini).
    $boxExportQuery = [$p.'_from' => $boxFrom, $p.'_to' => $boxTo, $p.'_sales' => (string) ($box['salesId'] ?? '')];
    if ($isSuperBox) {
        $boxExportQuery[$p.'_branch'] = $box['branch'];
    }
    $boxExportQuery = $boxExportQuery + array_filter(request()->except(array_merge($own, ['format', 'page'])), fn ($v) => is_scalar($v) && $v !== '');
@endphp

<form method="GET" action="{{ route('admin.dashboard') }}#{{ $anchor }}" class="frm-toolbar is-fields" data-box-filter>
    {{-- Pertahankan filter utama dan filter tabel lain --}}
    @foreach (request()->except(array_merge($own, ['page', 'format'])) as $k => $v)
        @if (is_scalar($v) && filled($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach

    @if ($isSuperBox)
        <div class="frm-field">
            <label class="frm-label" for="{{ $p }}_branch">Depo</label>
            <select id="{{ $p }}_branch" name="{{ $p }}_branch" class="frm-input is-select is-filter" data-box-branch>
                <option value="all" @selected($box['branch'] === 'all')>Semua Depo</option>
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}" @selected($box['branch'] === (string) $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="frm-field">
        <label class="frm-label" for="{{ $p }}_from">Dari Tanggal</label>
        <input type="date" id="{{ $p }}_from" name="{{ $p }}_from" class="frm-input is-filter" value="{{ $boxFrom }}">
    </div>
    <div class="frm-field">
        <label class="frm-label" for="{{ $p }}_to">Sampai Tanggal</label>
        <input type="date" id="{{ $p }}_to" name="{{ $p }}_to" class="frm-input is-filter" value="{{ $boxTo }}">
    </div>

    <div class="frm-field">
        <label class="frm-label" for="{{ $p }}_sales">Sales</label>
        <select id="{{ $p }}_sales" name="{{ $p }}_sales" class="frm-input is-select is-filter" data-box-sales>
            <option value="">Semua Sales</option>
            @foreach ($boxSalesOptions as $o)
                <option value="{{ $o['id'] }}" data-branch="{{ $o['branch_id'] }}" @selected($box['salesId'] === $o['id'])>{{ $o['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="frm-toolbar-actions" style="align-self:flex-end">
        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan</button>
        <a href="{{ route('admin.dashboard', request()->except(array_merge($own, ['page', 'format']))) }}#{{ $anchor }}" class="adm-btn adm-btn-ghost adm-btn-sm">Reset</a>
        @if (! empty($withToggle))
            <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-perf-toggle-all>Buka semua rincian produk</button>
        @endif

        <details class="dash-export" style="position:relative;display:inline-block">
            <summary class="adm-btn adm-btn-ghost adm-btn-sm" style="list-style:none;cursor:pointer">Export ▾</summary>
            <div style="position:absolute;right:0;top:calc(100% + 4px);z-index:30;min-width:190px;background:#fff;border:1px solid #d1d5db;border-radius:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:.25rem;display:flex;flex-direction:column">
                <a href="{{ route('admin.dashboard.export-box', ['box' => $exportBox] + $boxExportQuery + ['format' => 'xlsx']) }}" class="adm-btn adm-btn-ghost adm-btn-sm" style="justify-content:flex-start">Excel (.xlsx)</a>
                <a href="{{ route('admin.dashboard.export-box', ['box' => $exportBox] + $boxExportQuery + ['format' => 'pdf']) }}" class="adm-btn adm-btn-ghost adm-btn-sm" style="justify-content:flex-start">PDF (.pdf)</a>
            </div>
        </details>
    </div>
</form>

@once
    <script>
        // Ganti Depo pada satu box: Sales di-reset ke "Semua Sales" lalu box dimuat ulang
        // supaya daftar Sales mengikuti Depo yang dipilih.
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-box-filter]').forEach(function (form) {
                var branch = form.querySelector('[data-box-branch]');
                var sales = form.querySelector('[data-box-sales]');
                if (!branch || !sales) return;
                branch.addEventListener('change', function () {
                    sales.value = '';
                    form.submit();
                });
            });
        });
    </script>
@endonce
