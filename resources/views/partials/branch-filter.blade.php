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
    <form method="GET" action="{{ url()->current() }}" class="adm-branch-filter" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin:0 0 12px;font-size:.8rem">
        @foreach (request()->except(['branch', 'page']) as $k => $v)
            @if (is_scalar($v))
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach
        <label for="bfBranch" style="font-weight:600">Depo</label>
        <select id="bfBranch" name="branch" class="frm-input is-select is-filter" onchange="this.form.submit()" aria-label="Filter Depo">
            <option value="all" @selected($bfCurrent === 'all')>Semua Depo</option>
            @foreach ($bfBranches as $b)
                <option value="{{ $b->id }}" @selected($bfCurrent === (string) $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
        <noscript><button class="adm-btn adm-btn-ghost adm-btn-sm">Terapkan</button></noscript>
    </form>
@endif
