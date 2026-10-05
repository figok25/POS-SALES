<x-admin-layout>
    @php
        $canManage = \Illuminate\Support\Facades\Gate::allows('sales-task.manage');
        $isVariance = $salesTask->status === \App\Models\SalesTask::STATUS_STOCK_VARIANCE;
        $canReassign = in_array($salesTask->status, [\App\Models\SalesTask::STATUS_DRAFT, \App\Models\SalesTask::STATUS_DOCUMENT_AVAILABLE], true);

        $statusMap = [
            'draft' => ['Draft', 'is-off'],
            'document_available' => ['Document Available', 'is-info'],
            'stock_verification' => ['Stock Verification', 'is-warn'],
            'stock_variance' => ['Stock Variance', 'is-danger'],
            'ready_to_work' => ['Ready to Work', 'is-info'],
            'working' => ['Working', 'is-warn'],
            'completed' => ['Completed', 'is-on'],
            'cancelled' => ['Cancelled', 'is-danger'],
        ];
        [$statusLabel, $statusTone] = $statusMap[$salesTask->status] ?? [ucwords(str_replace('_', ' ', $salesTask->status)), 'is-off'];
    @endphp

    <div class="frm-page is-medium">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Sales Task {{ $salesTask->code }}</h1>
                <p class="frm-sub"><span class="frm-status {{ $statusTone }}">{{ $statusLabel }}</span></p>
            </div>
            <a href="{{ route('admin.sales-tasks.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        {{-- Notifikasi --}}
        @if (session('status'))
            <div class="frm-alert" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                <span class="frm-alert-text">{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="frm-alert is-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span class="frm-alert-text">{{ session('error') }}</span>
            </div>
        @endif
        @if ($salesTask->isDraft())
            <div class="frm-alert is-warn" role="note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span class="frm-alert-text">Apply akan merilis dokumen &amp; stock ini ke Sales App (status berubah ke Document Available).</span>
            </div>
        @endif
        @if ($isVariance)
            <div class="frm-alert is-error is-block" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                <div class="frm-alert-text">
                    <p class="frm-alert-title">Ada selisih stock</p>
                    <p class="frm-alert-body">
                        Ada selisih antara Qty Ditugaskan dan Qty Diverifikasi (angka merah di tabel Stock).
                        Task ini tertahan dan Sales BELUM BISA mulai bekerja sampai Anda menyetujui selisihnya.
                    </p>
                </div>
            </div>
        @endif

        <div class="frm-stack">
            {{-- Informasi --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi</h2>
                </div>
                <dl class="frm-detail">
                    <div>
                        <dt>BKB Distribusi (sumber stock)</dt>
                        <dd>
                            @if ($salesTask->bkbDistribusi)
                                <a href="{{ route('admin.distribution.bkb.show', $salesTask->bkbDistribusi) }}" class="panel-link">{{ $salesTask->bkbDistribusi->code }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div><dt>Sales</dt><dd>{{ $salesTask->sales->name ?? '-' }}</dd></div>
                    <div><dt>Branch</dt><dd>{{ $salesTask->branch->name ?? '-' }}</dd></div>
                    <div><dt>Tanggal Tugas</dt><dd>{{ $salesTask->task_date?->format('d/m/Y') ?? '-' }}</dd></div>
                    <div><dt>Catatan</dt><dd>{{ $salesTask->notes ?: '-' }}</dd></div>
                </dl>
            </section>

            {{-- Stock --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Stock yang Ditugaskan</h2>
                    <a href="{{ route('admin.sales-tasks.print-stock', $salesTask) }}" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost adm-btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                        Cetak Daftar Stock
                    </a>
                </div>
                @if ($salesTask->taskStocks->count())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="is-num">Qty Ditugaskan</th>
                                    <th class="is-num">Qty Diverifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salesTask->taskStocks as $line)
                                    <tr>
                                        <td>
                                            <span class="frm-line frm-name">{{ $line->product->name ?? '-' }}</span>
                                            <span class="frm-line">{{ $line->product->sku ?? '-' }}</span>
                                        </td>
                                        <td data-label="Ditugaskan" class="is-num"><span class="frm-num">{{ number_format($line->quantity_assigned, 2) }}</span></td>
                                        <td data-label="Diverifikasi" class="is-num">
                                            <span @class(['frm-num', 'is-strong is-neg' => $line->difference() != 0])>
                                                {{ $line->quantity_verified !== null ? number_format($line->quantity_verified, 2) : '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="panel-empty">Belum ada stock yang ditugaskan.</p>
                @endif
            </section>

            {{-- Rute Kunjungan --}}
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Rute Kunjungan</h2>
                        <p class="frm-meta">Otomatis dari Rute Kanvas</p>
                    </div>
                    @if ($salesTask->sales_id)
                        <a href="{{ route('admin.sales.route-map.index', ['sales_id' => $salesTask->sales_id, 'date' => optional($salesTask->task_date)->toDateString()]) }}"
                           class="panel-link">Lihat di Peta Rute</a>
                    @endif
                </div>
                @if ($salesTask->planCustomers->count())
                    <ul class="row-list">
                        @foreach ($salesTask->planCustomers as $plan)
                            <li class="row-item">
                                <div class="frm-stop">
                                    <span class="frm-stop-no">{{ $plan->sequence + 1 }}</span>
                                    <span class="frm-name">{{ $plan->customer->name ?? "Customer #{$plan->customer_id}" }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="panel-empty">Tidak ada rute kunjungan tercatat untuk Task ini.</p>
                @endif
            </section>

            {{-- Dokumen Task --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Dokumen Task</h2>
                    <span class="frm-count">{{ $salesTask->documents->count() }} dokumen</span>
                </div>
                @if ($salesTask->documents->count())
                    <div class="frm-table-wrap">
                        <table class="frm-table">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Tipe</th>
                                    <th>Status Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salesTask->documents as $doc)
                                    <tr>
                                        <td><span class="frm-name">{{ $doc->title }}</span></td>
                                        <td data-label="Tipe">{{ $doc->type }}</td>
                                        <td class="frm-cell-status">
                                            @if ($doc->downloaded_at)
                                                <span class="frm-status is-on">Downloaded</span>
                                            @else
                                                <span class="frm-status is-off">Belum diunduh</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="panel-empty">Belum ada dokumen. Dokumen dibuat saat Task di-Apply.</p>
                @endif
            </section>

            {{-- Tindakan --}}
            @if ($canManage && ($salesTask->isDraft() || $isVariance))
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Tindakan</h2>
                    </div>
                    <div class="frm-panel-foot is-split">
                        @if ($salesTask->isDraft())
                            <form action="{{ route('admin.sales-tasks.cancel', $salesTask) }}" method="POST" onsubmit="return confirm('Batalkan Task ini?')">
                                @csrf
                                <button type="submit" class="adm-btn adm-btn-ghost is-danger adm-btn-sm">Batalkan</button>
                            </form>
                            <form action="{{ route('admin.sales-tasks.apply', $salesTask) }}" method="POST" onsubmit="return confirm('Apply/Release Task ini ke Sales?')">
                                @csrf
                                <button type="submit" class="adm-btn adm-btn-success adm-btn-sm">Apply / Release</button>
                            </form>
                        @else
                            <span></span>
                            <form action="{{ route('admin.sales-tasks.approve-variance', $salesTask) }}" method="POST" onsubmit="return confirm('Setujui selisih stock ini? Task akan menjadi Ready to Work.')">
                                @csrf
                                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Setujui Selisih Stock</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Ganti Sales --}}
            @if ($canManage && $canReassign)
                <form action="{{ route('admin.sales-tasks.reassign', $salesTask) }}" method="POST" class="panel"
                      onsubmit="return confirm('Pindahkan penugasan & Sales Stock ke Sales terpilih?')">
                    @csrf
                    <div class="panel-head">
                        <h2 class="panel-title">Edit Penugasan (Ganti Sales)</h2>
                    </div>
                    <div class="frm-panel-body">
                        <p class="frm-hint">
                            Mengganti Sales akan memindahkan Sales Stock BKB ini dari Sales lama ke Sales baru (bukan Apply ulang).
                            Hanya bisa dilakukan sebelum Sales memulai Verifikasi Stock/Start Work.
                        </p>
                        <div class="frm-field">
                            <label class="frm-label" for="reassign_sales_id">Sales Baru <span class="frm-req">*</span></label>
                            <select id="reassign_sales_id" name="sales_id" required class="frm-input is-select">
                                <option value="">Pilih Sales baru</option>
                                @foreach ($salesList as $s)
                                    @if ($s->id !== $salesTask->sales_id)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="frm-panel-foot">
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Pindahkan</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-admin-layout>