<x-admin-layout>
    {{-- Kepala halaman --}}
    <div class="frm-head">
        <div>
            <h1 class="frm-title">Visit Plan: {{ $sales->name }}</h1>
            <p class="frm-sub">
                Centang outlet yang dikunjungi pada hari tsb. Urutan kunjungan mengikuti urutan checkbox dicentang.
                Outlet yang belum di-tag ke Sales ini tidak muncul di sini &mdash; atur dulu di
                <a href="{{ route('admin.sales.customer-assignments.index') }}" class="panel-link">Customer Assignment</a>.
            </p>
        </div>
        <a href="{{ route('admin.sales.visit-plans.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Kembali
        </a>
    </div>

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="frm-alert" role="status" data-alert="auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span class="frm-alert-text">{{ session('status') }}</span>
            <button type="button" class="frm-alert-close" aria-label="Tutup notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    @endif
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

    @if ($customers->isEmpty())
        <section class="panel">
            <div class="frm-empty">
                <div class="frm-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <p class="frm-empty-title">Belum ada outlet ter-tag</p>
                <p class="frm-empty-text">Sales ini belum punya outlet yang di-tag. Tag outlet dulu sebelum menyusun Visit Plan.</p>
                <a href="{{ route('admin.sales.customer-assignments.index') }}" class="adm-btn adm-btn-primary adm-btn-sm">Ke Customer Assignment</a>
            </div>
        </section>
    @else
        @php $customerIndex = $customers->pluck('id')->values()->flip(); @endphp

        <form method="POST" action="{{ route('admin.sales.visit-plans.update', $sales) }}">
            @csrf
            @method('PUT')

            <div class="frm-vp-grid">
                @foreach ($days as $dayNum => $dayName)
                    @php
                        $dayPlans         = $plans[$dayNum] ?? collect();
                        $dayCustomerIds   = $dayPlans->pluck('customer_id')->all();
                        $dayPlansByCustomer = $dayPlans->keyBy('customer_id');
                        $dayOrder         = array_flip($dayCustomerIds);
                    @endphp
                    <section class="panel" data-day-container="{{ $dayNum }}">
                        <div class="panel-head">
                            <h2 class="panel-title">{{ $dayName }}</h2>
                            <span class="frm-count" data-day-count="{{ $dayNum }}">{{ count($dayCustomerIds) }} outlet</span>
                        </div>

                        <div class="frm-vp-list" data-day-list="{{ $dayNum }}">
                            @foreach ($customers->sortBy(fn ($c) => $dayOrder[$c->id] ?? 999) as $customer)
                                <label class="frm-vp-item" data-i="{{ $customerIndex[$customer->id] }}">
                                    <input type="checkbox"
                                           name="plan[{{ $dayNum }}][]"
                                           value="{{ $customer->id }}"
                                           data-day="{{ $dayNum }}"
                                           class="frm-check visit-plan-checkbox"
                                           @checked(in_array($customer->id, $dayCustomerIds))>
                                    <span class="frm-vp-no" aria-hidden="true"></span>
                                    <span class="frm-vp-name">{{ $customer->name }}</span>
                                    @if (($dayPlansByCustomer[$customer->id]->source ?? null) === 'tagging')
                                        <span class="frm-tag" title="Otomatis ditambahkan dari Tagging Toko yang di-approve">dari tagging</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="frm-vp-actions">
                <button type="submit" class="adm-btn adm-btn-primary">Simpan Visit Plan</button>
                <a href="{{ route('admin.sales.visit-plans.index') }}" class="adm-btn adm-btn-ghost">Batal</a>
            </div>
        </form>

        <script>
            // Urutan kunjungan = urutan checkbox dicentang per hari.
            // Server menyimpan urutan persis sesuai urutan elemen <input> di dalam
            // array plan[hari][] saat form di-submit, jadi urutan DOM = urutan simpan.
            // Outlet tercentang dikelompokkan di atas (sesuai urutan dicentang),
            // yang tidak tercentang kembali ke posisi aslinya di bawah.
            (function () {
                const isChecked = (el) => el.querySelector('.visit-plan-checkbox').checked;

                function refresh(list) {
                    let n = 0;
                    list.querySelectorAll('.frm-vp-item').forEach(function (label) {
                        const checked = isChecked(label);
                        label.classList.toggle('is-checked', checked);
                        label.querySelector('.frm-vp-no').textContent = checked ? ++n : '';
                    });
                    const counter = document.querySelector('[data-day-count="' + list.dataset.dayList + '"]');
                    if (counter) counter.textContent = n + ' outlet';
                }

                function place(label, list) {
                    const others = Array.from(list.querySelectorAll('.frm-vp-item')).filter((el) => el !== label);
                    if (isChecked(label)) {
                        const checked = others.filter(isChecked);
                        const last = checked[checked.length - 1];
                        last ? last.after(label) : list.prepend(label);
                    } else {
                        const idx = Number(label.dataset.i);
                        const next = others.filter((el) => !isChecked(el)).find((el) => Number(el.dataset.i) > idx);
                        next ? next.before(label) : list.append(label);
                    }
                }

                document.querySelectorAll('[data-day-list]').forEach(function (list) {
                    refresh(list);
                    list.addEventListener('change', function (e) {
                        if (!e.target.classList.contains('visit-plan-checkbox')) return;
                        place(e.target.closest('label'), list);
                        e.target.focus({ preventScroll: true }); // pindah posisi DOM bisa melepas fokus keyboard
                        refresh(list);
                    });
                });
            })();

            document.querySelectorAll('[data-alert]').forEach(function (el) {
                var close = function () {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                };
                var btn = el.querySelector('.frm-alert-close');
                if (btn) btn.addEventListener('click', close);
                if (el.dataset.alert === 'auto') setTimeout(close, 6000);
            });
        </script>
    @endif
</x-admin-layout>