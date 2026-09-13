<x-admin-layout>
    <div class="p-6 max-w-4xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">{{ $item->name }}</h1>
            <div class="text-sm space-x-3">
                <a href="{{ route('admin.sales.customer-assignments.edit', $item) }}" class="text-indigo-600 hover:underline">Assign / Reassign Sales</a>
                <a href="{{ route('admin.master.customers.edit', $item) }}" class="text-blue-600 hover:underline">Edit</a>
                <a href="{{ route('admin.master.customers.index') }}" class="text-gray-600 hover:underline">&larr; Kembali</a>
            </div>
        </div>

        <div class="bg-white rounded shadow p-4 space-y-2 text-sm mb-6">
            <div class="flex justify-between"><span class="text-gray-500">Kode</span><span>{{ $item->code }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Sales Penanggung Jawab</span><span>{{ $item->sales->name ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Telepon</span><span>{{ $item->phone ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Alamat</span><span class="text-right">{{ $item->address ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">NPWP</span><span>{{ $item->npwp ?? '-' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Status</span>
                <span>
                    @if ($item->is_active)
                        <span class="text-green-700 bg-green-100 px-2 py-0.5 rounded text-xs">Aktif</span>
                    @else
                        <span class="text-gray-600 bg-gray-100 px-2 py-0.5 rounded text-xs">Nonaktif</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h2 class="font-semibold text-sm mb-2">Riwayat Transaksi Penjualan</h2>
                <div class="bg-white rounded shadow divide-y text-sm">
                    @forelse ($transactions as $trx)
                        <a href="{{ route('admin.sales.transactions.show', $trx) }}" class="flex justify-between px-3 py-2 hover:bg-gray-50">
                            <span>{{ $trx->code }} <span class="text-gray-400">({{ $trx->created_at->format('d M Y') }})</span></span>
                            <span>Rp {{ number_format($trx->total, 0, ',', '.') }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-4 text-gray-500">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="font-semibold text-sm mb-2">Riwayat Invoice</h2>
                <div class="bg-white rounded shadow divide-y text-sm">
                    @forelse ($invoices as $inv)
                        <a href="{{ route('admin.sales.invoices.show', $inv) }}" class="flex justify-between px-3 py-2 hover:bg-gray-50">
                            <span>{{ $inv->code }}</span>
                            <span>Rp {{ number_format($inv->grand_total, 0, ',', '.') }} - {{ ucfirst($inv->status) }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-4 text-gray-500">Belum ada invoice.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="font-semibold text-sm mb-2">Riwayat Kunjungan</h2>
                <div class="bg-white rounded shadow divide-y text-sm">
                    @forelse ($visits as $visit)
                        <div class="flex justify-between px-3 py-2">
                            <span>{{ $visit->sales->name ?? '-' }}</span>
                            <span>{{ $visit->check_in_at->format('d M Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="px-3 py-4 text-gray-500">Belum ada kunjungan.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="font-semibold text-sm mb-2">Riwayat Customer Assignment</h2>
                <div class="bg-white rounded shadow divide-y text-sm">
                    @forelse ($assignments as $row)
                        <div class="flex justify-between px-3 py-2 {{ $row->isCurrent() ? 'bg-green-50' : '' }}">
                            <span>{{ $row->sales->name ?? '-' }}</span>
                            <span class="text-gray-500">{{ $row->assigned_at->format('d M Y') }}@if ($row->unassigned_at) &rarr; {{ $row->unassigned_at->format('d M Y') }}@endif</span>
                        </div>
                    @empty
                        <p class="px-3 py-4 text-gray-500">Belum ada riwayat assignment.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h2 class="font-semibold text-sm mb-2">Riwayat Tagging</h2>
                <div class="bg-white rounded shadow divide-y text-sm">
                    @forelse ($taggings as $tag)
                        <a href="{{ route('admin.sales.customer-taggings.show', $tag) }}" class="flex justify-between px-3 py-2 hover:bg-gray-50">
                            <span>{{ $tag->sales->name ?? '-' }}</span>
                            <span>{{ ucfirst($tag->status) }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-4 text-gray-500">Belum ada tagging.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
