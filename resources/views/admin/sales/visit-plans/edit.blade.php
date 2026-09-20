<x-admin-layout>
    <div class="p-6">
        <h1 class="text-xl font-semibold mb-1">Visit Plan: {{ $sales->name }}</h1>
        <p class="text-sm text-gray-500 mb-4">Centang outlet yang dikunjungi pada hari tsb. Urutan kunjungan mengikuti urutan checkbox dicentang. Outlet yang belum di-tag ke Sales ini tidak muncul di sini — atur dulu di <a href="{{ route('admin.sales.customer-assignments.index') }}" class="text-blue-700 hover:underline">Customer Assignment</a>.</p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('status') }}</div>
        @endif

        @if ($customers->isEmpty())
            <div class="p-3 bg-yellow-100 text-yellow-800 rounded text-sm">Sales ini belum punya outlet yang di-tag. Tag outlet dulu sebelum menyusun Visit Plan.</div>
        @else
            <form method="POST" action="{{ route('admin.sales.visit-plans.update', $sales) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    @foreach ($days as $dayNum => $dayName)
                        @php $dayCustomerIds = ($plans[$dayNum] ?? collect())->pluck('customer_id')->all(); @endphp
                        <div class="bg-white rounded shadow p-4" data-day-container="{{ $dayNum }}">
                            <h2 class="font-semibold mb-3">{{ $dayName }}</h2>
                            <div class="space-y-2 max-h-80 overflow-y-auto" data-day-list="{{ $dayNum }}">
                                @foreach ($customers->sortBy(fn ($c) => array_search($c->id, $dayCustomerIds) === false ? 999 : array_search($c->id, $dayCustomerIds)) as $customer)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox"
                                               name="plan[{{ $dayNum }}][]"
                                               value="{{ $customer->id }}"
                                               data-day="{{ $dayNum }}"
                                               class="visit-plan-checkbox"
                                               @checked(in_array($customer->id, $dayCustomerIds))>
                                        <span class="flex-1">{{ $customer->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Simpan Visit Plan</button>
            </form>

            <script>
                // Urutan kunjungan = urutan checkbox dicentang per hari (paling
                // sederhana & anti-salah-ketik dibanding input angka manual).
                // Server menyimpan urutan persis sesuai urutan elemen <input>
                // di dalam array plan[hari][] saat form di-submit.
                document.querySelectorAll('.visit-plan-checkbox').forEach(cb => {
                    cb.addEventListener('change', function () {
                        if (!this.checked) return;
                        const list = document.querySelector(`[data-day-list="${this.dataset.day}"]`);
                        list?.appendChild(this.closest('label'));
                    });
                });
            </script>
        @endif
    </div>
</x-admin-layout>
