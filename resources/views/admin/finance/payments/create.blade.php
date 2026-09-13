<x-admin-layout>
    <div class="p-6 max-w-lg">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Catat Payment</h1>
            <a href="{{ route('admin.sales.invoices.show', $invoice) }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali</a>
        </div>

        <div class="bg-white rounded shadow p-4 mb-4 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Invoice</span><span>{{ $invoice->code }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Customer</span><span>{{ $invoice->customer->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Grand Total</span><span>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span></div>
            <div class="flex justify-between font-medium"><span>Outstanding</span><span>Rp {{ number_format($invoice->outstanding(), 0, ',', '.') }}</span></div>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.finance.payments.store', $invoice) }}" class="bg-white rounded shadow p-4 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Jumlah</label>
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
                <label class="block text-sm font-medium mb-1">Tanggal Bayar</label>
                <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">No. Referensi (opsional)</label>
                <input type="text" name="reference_no" value="{{ old('reference_no') }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Catatan (opsional)</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Simpan Payment</button>
        </form>
    </div>
</x-admin-layout>
