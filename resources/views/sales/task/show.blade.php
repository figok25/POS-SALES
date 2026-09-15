<x-sales-layout>
    <x-slot name="header">Status Tugas</x-slot>

    @if (! $task)
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-3xl mb-2">⏳</p>
            <p class="font-medium text-gray-800">Belum Ada Tugas Hari Ini</p>
            <p class="text-sm text-gray-500 mt-1">
                Anda belum mendapatkan penugasan dari Admin untuk hari ini.
                Fitur operasional (Transaksi, Kunjungan, Tagging Toko, Sales Stock)
                baru aktif setelah Task di-Apply Admin dan Anda menyelesaikan
                Verifikasi Stock.
            </p>
        </div>
    @else
        <div
            x-data="salesTaskGate({
                taskId: {{ $task->id }},
                status: @js($task->status),
                documents: @js($task->documents->map(fn ($d) => ['id' => $d->id, 'title' => $d->title, 'downloaded_at' => $d->downloaded_at])),
                stocks: @js($task->taskStocks->map(fn ($s) => ['id' => $s->id, 'product_name' => $s->product->name ?? '-', 'quantity_assigned' => (float) $s->quantity_assigned, 'quantity_verified' => $s->quantity_verified !== null ? (float) $s->quantity_verified : null])),
            })"
            class="space-y-4"
        >
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Kode Task</span>
                    <span class="font-medium">{{ $task->code }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-500">Tanggal</span>
                    <span>{{ $task->task_date->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-500">Status</span>
                    <span x-text="statusLabel()" class="font-medium"></span>
                </div>
            </div>

            <template x-if="errorMessage">
                <div class="p-3 bg-red-100 text-red-800 rounded text-sm" x-text="errorMessage"></div>
            </template>

            {{-- Step 1: Dokumen --}}
            <div class="bg-white rounded-lg shadow p-4" x-show="status !== 'draft'">
                <p class="font-medium text-sm mb-2">1. Dokumen</p>
                <template x-for="doc in documents" :key="doc.id">
                    <div class="flex items-center justify-between py-1.5 border-b last:border-0 text-sm">
                        <span x-text="doc.title"></span>
                        <button
                            type="button"
                            @click="downloadDocument(doc)"
                            :disabled="doc.downloaded_at"
                            :class="doc.downloaded_at ? 'text-green-600' : 'text-indigo-600 hover:underline'"
                            class="text-xs"
                            x-text="doc.downloaded_at ? 'Sudah Diterima ✓' : 'Tandai Diterima'"
                        ></button>
                    </div>
                </template>
            </div>

            {{-- Step 2: Verifikasi Stock --}}
            <div class="bg-white rounded-lg shadow p-4" x-show="['document_available', 'stock_verification'].includes(status)">
                <p class="font-medium text-sm mb-2">2. Verifikasi Stock yang Dibawa</p>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500">
                            <th class="pb-1">Produk</th>
                            <th class="pb-1 text-right">Dari Admin</th>
                            <th class="pb-1 text-right w-24">Diverifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="stock in stocks" :key="stock.id">
                            <tr class="border-t">
                                <td class="py-1.5" x-text="stock.product_name"></td>
                                <td class="py-1.5 text-right" x-text="stock.quantity_assigned"></td>
                                <td class="py-1.5 text-right">
                                    <input type="number" step="0.01" min="0" x-model.number="stock.quantity_verified"
                                           class="w-20 border-gray-300 rounded px-2 py-1 text-right text-sm">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <button type="button" @click="submitVerification()" :disabled="loading"
                        class="w-full mt-3 py-2 bg-indigo-600 text-white rounded-lg text-sm disabled:opacity-50">
                    Submit Verifikasi
                </button>
            </div>

            {{-- Step 3: Start Work --}}
            <div class="bg-white rounded-lg shadow p-4 text-center" x-show="status === 'ready_to_work'">
                <p class="text-sm text-gray-600 mb-3">Dokumen & stock sudah beres. Anda siap memulai pekerjaan hari ini.</p>
                <button type="button" @click="startWork()" :disabled="loading"
                        class="w-full py-3 bg-green-600 text-white rounded-lg font-medium disabled:opacity-50">
                    🚀 Mulai Kerja
                </button>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center" x-show="status === 'working'">
                <p class="text-sm text-green-800 mb-2">Anda sedang bekerja. Jangan lupa aktifkan Tracking.</p>
                <a href="{{ route('sales.tracking.show') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Buka Tracking</a>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        function salesTaskGate(config) {
            return {
                taskId: config.taskId,
                status: config.status,
                documents: config.documents,
                stocks: config.stocks,
                loading: false,
                errorMessage: null,

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').content;
                },

                statusLabel() {
                    const labels = {
                        draft: 'Menunggu Penugasan Admin',
                        applied: 'Dokumen Sedang Disiapkan',
                        document_available: 'Dokumen Tersedia',
                        stock_verification: 'Verifikasi Stock Berjalan',
                        ready_to_work: 'Siap Bekerja',
                        working: 'Sedang Bekerja',
                        completed: 'Tugas Selesai',
                    };
                    return labels[this.status] ?? this.status;
                },

                async downloadDocument(doc) {
                    if (doc.downloaded_at) return;
                    const res = await fetch(`/api/sales/tasks/${this.taskId}/documents/${doc.id}/download`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    if (json.success) {
                        doc.downloaded_at = json.data.downloaded_at;
                    } else {
                        this.errorMessage = json.message;
                    }
                },

                async submitVerification() {
                    this.loading = true;
                    this.errorMessage = null;
                    const items = this.stocks.map(s => ({
                        sales_task_stock_id: s.id,
                        quantity_verified: s.quantity_verified ?? 0,
                    }));

                    const res = await fetch(`/api/sales/tasks/${this.taskId}/verify-stock`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ items }),
                    });
                    const json = await res.json();
                    this.loading = false;

                    if (json.success) {
                        this.status = json.data.status;
                    } else {
                        this.errorMessage = json.message;
                    }
                },

                async startWork() {
                    this.loading = true;
                    this.errorMessage = null;

                    const res = await fetch(`/api/sales/tasks/${this.taskId}/start-work`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    this.loading = false;

                    if (json.success) {
                        window.location.href = '{{ route("sales.tracking.show") }}';
                    } else {
                        this.errorMessage = json.message;
                    }
                },
            };
        }
    </script>
    @endpush
</x-sales-layout>
