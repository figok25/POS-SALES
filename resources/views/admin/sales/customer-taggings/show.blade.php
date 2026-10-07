<x-admin-layout>
    <div class="frm-page">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Detail Tagging Toko</h1>
                <p class="frm-sub">{{ $tagging->name }}</p>
            </div>
            <a href="{{ route('admin.sales.customer-taggings.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        {{-- Error validasi --}}
        @if ($errors->any())
            <div class="frm-alert is-error is-block" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <div class="frm-alert-text">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="frm-stack">
            {{-- Detail tagging --}}
            <section class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Informasi Toko</h2>
                    @if ($tagging->status === 'approved')
                        <span class="frm-status is-on">Approved</span>
                    @elseif ($tagging->status === 'rejected')
                        <span class="frm-status is-danger">Rejected</span>
                    @else
                        <span class="frm-status is-warn">Pending</span>
                    @endif
                </div>

                <dl class="frm-detail">
                    <div><dt>Sales</dt><dd>{{ $tagging->sales->name ?? '-' }}</dd></div>
                    <div><dt>Waktu Tagging</dt><dd>{{ $tagging->tagged_at->format('d M Y H:i') }}</dd></div>
                    <div><dt>Nama Toko</dt><dd>{{ $tagging->name }}</dd></div>
                    <div><dt>Telepon</dt><dd>{{ $tagging->phone ?? '-' }}</dd></div>
                    <div><dt>Alamat</dt><dd>{{ $tagging->address ?? '-' }}</dd></div>
                    <div><dt>Tipe Toko</dt><dd>{{ $tagging->customer_type ?? '-' }}</dd></div>
                    <div>
                        <dt>Koordinat</dt>
                        <dd>
                            @if ($tagging->latitude && $tagging->longitude)
                                <a class="panel-link" target="_blank" rel="noopener"
                                   href="https://maps.google.com/?q={{ $tagging->latitude }},{{ $tagging->longitude }}">{{ $tagging->latitude }}, {{ $tagging->longitude }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div><dt>Catatan Sales</dt><dd>{{ $tagging->notes ?? '-' }}</dd></div>
                    @if ($tagging->customer)
                        <div>
                            <dt>Customer Terkait</dt>
                            <dd>
                                <a href="{{ route('admin.master.customers.show', $tagging->customer) }}" class="panel-link">{{ $tagging->customer->code }} - {{ $tagging->customer->name }}</a>
                            </dd>
                        </div>
                    @endif
                    @if ($tagging->auto_approved)
                        <div><dt>Persetujuan</dt><dd>Otomatis oleh sistem (tidak terindikasi duplikat) &middot; {{ $tagging->reviewed_at?->format('d M Y H:i') }}</dd></div>
                    @endif
                    @if ($tagging->reviewer)
                        <div>
                            <dt>Direview oleh</dt>
                            <dd>{{ $tagging->reviewer->name }} &middot; {{ $tagging->reviewed_at?->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($tagging->review_notes)
                        <div><dt>Catatan Review</dt><dd>{{ $tagging->review_notes }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Alasan ditahan (terindikasi duplikat saat tagging) --}}
            @if ($tagging->duplicate_reason)
                <div class="frm-alert is-warn is-block" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    <div class="frm-alert-text">
                        <p class="frm-alert-title">Ditahan karena terindikasi duplikat</p>
                        <p>{{ $tagging->duplicate_reason }}</p>
                        @if ($tagging->duplicateCustomer)
                            <p><a href="{{ route('admin.master.customers.show', $tagging->duplicateCustomer) }}" class="panel-link">Lihat customer yang mirip: {{ $tagging->duplicateCustomer->code }} - {{ $tagging->duplicateCustomer->name }}</a></p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Kemungkinan duplikasi --}}
            @if ($duplicates->isNotEmpty())
                <div class="frm-alert is-warn is-block" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    <div class="frm-alert-text">
                        <p class="frm-alert-title">Kemungkinan duplikasi dengan customer yang sudah ada</p>
                        <ul>
                            @foreach ($duplicates as $dup)
                                <li>{{ $dup->code }} - {{ $dup->name }} ({{ $dup->phone ?? '-' }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Aksi review --}}
            @if ($tagging->isPending())
                <div class="frm-grid">
                    <section class="panel">
                        <div class="panel-head">
                            <h2 class="panel-title">Setujui</h2>
                        </div>
                        <form action="{{ route('admin.sales.customer-taggings.approve', $tagging) }}" method="POST">
                            @csrf
                            <div class="frm-panel-body">
                                <div class="frm-field">
                                    <label for="approve-notes" class="frm-label">Catatan approval <span class="frm-opt">(opsional)</span></label>
                                    <textarea id="approve-notes" name="review_notes" rows="3" class="frm-input is-area" placeholder="Catatan approval"></textarea>
                                </div>
                            </div>
                            <div class="frm-panel-foot">
                                <button type="submit" class="adm-btn adm-btn-success adm-btn-sm adm-btn-block">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    Approve &amp; Buat Customer
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="panel">
                        <div class="panel-head">
                            <h2 class="panel-title">Tolak</h2>
                        </div>
                        <form action="{{ route('admin.sales.customer-taggings.reject', $tagging) }}" method="POST">
                            @csrf
                            <div class="frm-panel-body">
                                <div class="frm-field">
                                    <label for="reject-notes" class="frm-label">Alasan penolakan <span class="frm-opt">(opsional)</span></label>
                                    <textarea id="reject-notes" name="review_notes" rows="3" class="frm-input is-area" placeholder="Alasan penolakan"></textarea>
                                </div>
                            </div>
                            <div class="frm-panel-foot">
                                <button type="submit" class="adm-btn adm-btn-danger adm-btn-sm adm-btn-block">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    Reject
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>