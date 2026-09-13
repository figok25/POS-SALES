<x-admin-layout>
    <div class="p-6 max-w-lg">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">Tambah Catatan Income/Expense</h1>
            <a href="{{ route('admin.finance.cash-ledgers.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.finance.cash-ledgers.store') }}" class="bg-white rounded shadow p-4 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Tipe</label>
                <select name="type" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Income</option>
                    <option value="expense" {{ old('type', 'expense') === 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Kategori</label>
                <input type="text" name="category" value="{{ old('category') }}" placeholder="mis. ATK, Listrik, Sewa"
                       class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Jumlah</label>
                <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Keterangan (opsional)</label>
                <input type="text" name="description" value="{{ old('description') }}" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Simpan</button>
        </form>
    </div>
</x-admin-layout>
