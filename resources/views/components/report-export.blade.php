{{-- Keeps current filters (from/to/module) and adds ?export=xlsx|json --}}
<div class="frm-head-actions">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx', 'page' => null]) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Export Excel
    </a>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'json', 'page' => null]) }}" class="adm-btn adm-btn-ghost adm-btn-sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Export JSON
    </a>
</div>
