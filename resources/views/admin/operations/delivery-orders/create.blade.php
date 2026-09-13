<x-admin-layout>
    <div class="p-6 max-w-2xl">
        <h1 class="text-xl font-semibold mb-4">Buat Draft Delivery Order</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.operations.delivery-orders.store') }}" class="bg-white p-4 rounded shadow">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Sales Transaction *</label>
                <select name="sales_transaction_id" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">-- Pilih Transaksi (belum ada DO) --</option>
                    @foreach ($transactions as $t)
                        <option value="{{ $t->id }}" @selected(old('sales_transaction_id') == $t->id)>
                            {{ $t->code }} - {{ $t->customer->name ?? '-' }} (Rp {{ number_format($t->total, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                @if ($transactions->isEmpty())
                    <p class="text-xs text-gray-500 mt-1">Tidak ada transaksi yang siap dibuatkan Delivery Order.</p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Vehicle</label>
                    <select name="vehicle_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Tanpa Vehicle --</option>
                        @foreach ($vehicles as $v)
                            <option value="{{ $v->id }}" @selected(old('vehicle_id') == $v->id)>{{ $v->name }} ({{ $v->plate_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Driver</label>
                    <select name="driver_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Tanpa Driver --</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" @selected(old('driver_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Route</label>
                    <select name="route_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">-- Tanpa Route --</option>
                        @foreach ($routes as $r)
                            <option value="{{ $r->id }}" @selected(old('route_id') == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal Jadwal</label>
                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <p class="text-xs text-gray-500 mb-4">Item pengiriman otomatis mengikuti item pada Sales Transaction terpilih.</p>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.operations.delivery-orders.index') }}" class="px-3 py-2 text-sm rounded border">Batal</a>
                <button class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">Simpan Draft</button>
            </div>
        </form>
    </div>
</x-admin-layout>
