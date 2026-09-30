{{-- Keeps current filters (from/to/module) and adds ?export=xlsx|json --}}
<div class="flex gap-2">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx', 'page' => null]) }}"
       class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm">Export Excel</a>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'json', 'page' => null]) }}"
       class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-1.5 rounded text-sm">Export JSON</a>
</div>
