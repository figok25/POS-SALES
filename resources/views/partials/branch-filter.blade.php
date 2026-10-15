{{--
    Filter Depo global untuk Super Admin (semua menu yang datanya per-Depo).
    Hanya tampilan: filter sebenarnya dipaksakan di backend lewat BranchContext
    (query ?branch=). Admin biasa tidak melihat ini dan tidak bisa mengubah Depo.
    Filter lain (cari/tanggal/status) ikut terbawa; halaman kembali ke 1.
--}}
@php
    $bfScopedRoutes = [
        'admin.master.customers.index', 'admin.master.sales.index', 'admin.master.warehouses.index', 'admin.master.buy-get-promos.index',
        'admin.inventory.stock.index', 'admin.inventory.stock.sales', 'admin.inventory.movements.index', 'admin.inventory.adjustments.index',
        'admin.distribution.stock-requests.index', 'admin.distribution.bkb.index', 'admin.distribution.btb.index', 'admin.distribution.branch-transfer.index',
        'admin.depo.transactions.index',
        'admin.sales.customer-assignments.index', 'admin.sales.visit-plans.index', 'admin.sales.route-map.index', 'admin.sales.customer-taggings.index',
        'admin.sales.kpi.index', 'admin.sales.invoices.index',
        'admin.finance.payments.index', 'admin.finance.settlements.index', 'admin.finance.cash-ledgers.index',
        'admin.sales-tasks.index', 'admin.operations.live-monitoring.index', 'admin.operations.delivery-orders.index', 'admin.operations.monitoring.index',
        'admin.reports.index', 'admin.system.audit-log.index',
    ];
    $bfShow = auth()->user()?->isSuperAdmin()
        && request()->isMethod('GET')
        && request()->routeIs($bfScopedRoutes);
@endphp

@if ($bfShow)
    @php
        $bfBranches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $bfCurrent = \App\Support\BranchContext::current()->isAll() ? 'all' : (string) \App\Support\BranchContext::current()->branchId();
    @endphp
    <form method="GET" action="{{ url()->current() }}" class="adm-branch-filter" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.75rem;margin:0 0 16px;padding:12px 16px;border:1px solid var(--adm-border,#e5e9f2);border-radius:14px;background:var(--adm-surface,transparent)">
        @foreach (request()->except(['branch', 'page']) as $k => $v)
            @if (is_scalar($v))
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach
        <label for="bfBranch" style="display:flex;flex-direction:column;gap:.3rem;font-size:.75rem;font-weight:600;color:var(--adm-muted,#64748b);margin:0">Filter Depo
            <select id="bfBranch" name="branch" aria-label="Filter Depo" class="frm-input is-select is-filter" onchange="this.form.submit()" style="min-width:220px">
                <option value="all" @selected($bfCurrent === 'all')>Semua Depo</option>
                @foreach ($bfBranches as $b)
                    <option value="{{ $b->id }}" @selected($bfCurrent === (string) $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </label>
        <p style="margin:0;font-size:.78rem;color:var(--adm-muted,#64748b);flex:1;min-width:200px">
            @if ($bfCurrent === 'all')
                Menampilkan data <strong>seluruh Depo</strong>. Pilih satu Depo untuk mempersempit tampilan; pilihan ikut terbawa saat pindah menu.
            @else
                Menampilkan data <strong>{{ $bfBranches->firstWhere('id', (int) $bfCurrent)?->name ?? 'Depo terpilih' }}</strong> saja. Pilih "Semua Depo" untuk melihat semuanya.
            @endif
        </p>
        <noscript><button class="adm-btn adm-btn-ghost adm-btn-sm">Terapkan</button></noscript>
    </form>
@endif

{{-- Halaman yang tidak punya filter Depo sendiri: beri tahu bila Depo tersimpan sedang membatasi tampilan, dan beri jalan keluar. --}}
@if (auth()->user()?->isSuperAdmin() && request()->isMethod('GET') && ! $bfShow
    && ! request()->routeIs('admin.system.*', 'admin.master.branches.*')
    && ! \App\Support\BranchContext::current()->isAll())
    @php $bfName = \App\Models\Branch::whereKey(\App\Support\BranchContext::current()->branchId())->value('name'); @endphp
    <div class="adm-branch-note" role="status" style="margin:0 0 16px;padding:10px 16px;border:1px solid var(--adm-border,#e5e9f2);border-radius:14px;font-size:.8rem;color:var(--adm-muted,#64748b)">
        Tampilan dibatasi ke Depo <strong>{{ $bfName ?? 'terpilih' }}</strong>.
        <a href="{{ request()->fullUrlWithQuery(['branch' => 'all']) }}" style="font-weight:600">Tampilkan semua Depo</a>
    </div>
@endif
