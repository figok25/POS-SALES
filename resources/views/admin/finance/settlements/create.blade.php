<x-admin-layout>
    <div class="p-6 max-w-lg">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Buat Draft Settlement</h1>
            <a href="{{ route('admin.finance.settlements.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.finance.settlements.store') }}" class="bg-white rounded shadow p-4 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Sales</label>
                <select name="sales_id" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                    @foreach ($saless as $sales)
                        <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Warehouse Tujuan Retur</label>
                <select name="warehouse_id" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="text-xs text-gray-500">
                Draft akan otomatis menghitung total payment cash yang belum disetor & sisa Sales Stock
                Sales ini saat ini. Anda bisa mengoreksi qty retur & jumlah setoran di langkah berikutnya
                sebelum di-Apply.
            </p>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Buat Draft</button>
        </form>
    </div>
</x-admin-layout>
