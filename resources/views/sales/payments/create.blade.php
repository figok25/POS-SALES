<x-sales-layout>
    <x-slot name="header">Catat Pembayaran</x-slot>

    @if ($errors->any())
        <div class="mb-3 p-3 bg-red-100 text-red-800 rounded text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-4 space-y-2 text-sm mb-4">
        <div class="flex justify-between"><span class="text-gray-500">Invoice</span><span>{{ $invoice->code }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Customer</span><span>{{ $invoice->customer->name ?? '-' }}</span></div>
        <div class="flex justify-between font-semibold"><span>Outstanding</span><span>Rp {{ number_format($invoice->outstanding(), 0, ',', '.') }}</span></div>
    </div>

    <form method="POST" action="{{ route('sales.payments.store', $invoice) }}" class="bg-white rounded-lg shadow p-4 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Jumlah Diterima</label>
            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->outstanding() }}"
                   value="{{ old('amount', $invoice->outstanding()) }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Metode</label>
            <select name="method" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                <option value="cash" selected>Cash</option>
                <option value="transfer">Transfer</option>
                <option value="other">Lainnya</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Catatan (opsional)</label>
            <input type="text" name="notes" value="{{ old('notes') }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
        </div>

        <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm">Simpan Pembayaran</button>
    </form>
</x-sales-layout>
