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

                        <div class="frm-vp-toolbar">
                            <input type="search" class="frm-input" placeholder="Cari outlet di hari {{ $dayName }}..." data-day-search="{{ $dayNum }}" aria-label="Cari outlet di hari {{ $dayName }}">
                            <label class="frm-vp-selectall">
                                <input type="checkbox" class="frm-check" data-day-select-all="{{ $dayNum }}">
                                Pilih semua outlet yang tampil (untuk dipindahkan)
                            </label>
                            <div class="frm-vp-bulk" data-day-bulk="{{ $dayNum }}">
                                <span data-day-bulk-count="{{ $dayNum }}">0 dipilih</span>
                                <select class="frm-input is-select" data-day-bulk-target="{{ $dayNum }}" aria-label="Pindahkan ke hari">
                                    <option value="">Pindahkan ke hari...</option>
                                    @foreach ($days as $otherDayNum => $otherDayName)
                                        @if ($otherDayNum !== $dayNum)
                                            <option value="{{ $otherDayNum }}">{{ $otherDayName }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" data-day-bulk-move="{{ $dayNum }}">Pindahkan</button>
                                <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" data-day-bulk-clear="{{ $dayNum }}">Batal</button>
                            </div>
                        </div>

                        <div class="frm-vp-list" data-day-list="{{ $dayNum }}">
                            @foreach ($customers->sortBy(fn ($c) => $dayOrder[$c->id] ?? 999) as $customer)
                                <div class="frm-vp-item" data-i="{{ $customerIndex[$customer->id] }}" data-customer="{{ $customer->id }}">
                                    <input type="checkbox" class="frm-check vp-select" aria-label="Pilih {{ $customer->name }} untuk dipindahkan">
                                    <label class="frm-vp-row">
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
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="frm-alert is-info" role="status" data-vp-move-status hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span class="frm-alert-text"></span>
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
                    list.querySelectorAll('.frm-vp-item').forEach(function (item) {
                        const checked = isChecked(item);
                        item.classList.toggle('is-checked', checked);
                        item.querySelector('.frm-vp-no').textContent = checked ? ++n : '';
                        // Outlet yang dilepas (unchecked) tidak relevan lagi untuk
                        // dipindahkan -- lepas juga centang "pilih"-nya.
                        if (!checked) {
                            const sel = item.querySelector('.vp-select');
                            if (sel) sel.checked = false;
                        }
                    });
                    const counter = document.querySelector('[data-day-count="' + list.dataset.dayList + '"]');
                    if (counter) counter.textContent = n + ' outlet';
                }

                function place(item, list) {
                    const others = Array.from(list.querySelectorAll('.frm-vp-item')).filter((el) => el !== item);
                    if (isChecked(item)) {
                        const checked = others.filter(isChecked);
                        const last = checked[checked.length - 1];
                        last ? last.after(item) : list.prepend(item);
                    } else {
                        const idx = Number(item.dataset.i);
                        const next = others.filter((el) => !isChecked(el)).find((el) => Number(el.dataset.i) > idx);
                        next ? next.before(item) : list.append(item);
                    }
                }

                document.querySelectorAll('[data-day-list]').forEach(function (list) {
                    refresh(list);
                    list.addEventListener('change', function (e) {
                        if (!e.target.classList.contains('visit-plan-checkbox')) return;
                        place(e.target.closest('.frm-vp-item'), list);
                        // Pindah posisi DOM bisa melepas fokus keyboard -- tapi jangan
                        // rebut fokus kalau perubahan ini dipicu JS (mis. saat
                        // "Pindahkan" bulk dijalankan untuk banyak outlet sekaligus).
                        if (e.isTrusted) e.target.focus({ preventScroll: true });
                        refresh(list);
                    });
                });
            })();

            // Cari outlet per hari -- supaya tidak perlu scroll panjang untuk
            // menemukan satu toko di daftar yang banyak.
            document.querySelectorAll('[data-day-search]').forEach(function (input) {
                const list = document.querySelector('[data-day-list="' + input.dataset.daySearch + '"]');
                if (!list) return;
                input.addEventListener('input', function () {
                    const q = input.value.trim().toLowerCase();
                    list.querySelectorAll('.frm-vp-item').forEach(function (item) {
                        const name = item.querySelector('.frm-vp-name').textContent.toLowerCase();
                        item.style.display = (q !== '' && ! name.includes(q)) ? 'none' : '';
                    });
                });
            });

            // Pilih banyak outlet (yang sedang dicentang di hari itu) lalu
            // pindahkan sekaligus ke hari lain -- lepas dari hari asal,
            // tercentang di hari tujuan.
            (function () {
                function bulkBar(day) { return document.querySelector('[data-day-bulk="' + day + '"]'); }

                function updateBulkBar(day) {
                    const list = document.querySelector('[data-day-list="' + day + '"]');
                    const bar = bulkBar(day);
                    if (!list || !bar) return;
                    const selected = list.querySelectorAll('.vp-select:checked').length;
                    bar.classList.toggle('is-visible', selected > 0);
                    const label = bar.querySelector('[data-day-bulk-count]');
                    if (label) label.textContent = selected + ' dipilih';
                }

                document.querySelectorAll('[data-day-list]').forEach(function (list) {
                    const day = list.dataset.dayList;
                    list.addEventListener('change', function (e) {
                        if (!e.target.classList.contains('vp-select')) return;
                        updateBulkBar(day);
                    });
                });

                document.querySelectorAll('[data-day-select-all]').forEach(function (toggle) {
                    const day = toggle.dataset.daySelectAll;
                    const list = document.querySelector('[data-day-list="' + day + '"]');
                    toggle.addEventListener('change', function () {
                        list.querySelectorAll('.frm-vp-item.is-checked').forEach(function (item) {
                            if (item.style.display === 'none') return; // lagi disembunyikan pencarian
                            const sel = item.querySelector('.vp-select');
                            if (sel) sel.checked = toggle.checked;
                        });
                        updateBulkBar(day);
                    });
                });

                document.querySelectorAll('[data-day-bulk-clear]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const day = btn.dataset.dayBulkClear;
                        const list = document.querySelector('[data-day-list="' + day + '"]');
                        list.querySelectorAll('.vp-select:checked').forEach(function (cb) { cb.checked = false; });
                        const selectAll = document.querySelector('[data-day-select-all="' + day + '"]');
                        if (selectAll) selectAll.checked = false;
                        updateBulkBar(day);
                    });
                });

                document.querySelectorAll('[data-day-bulk-move]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const day = btn.dataset.dayBulkMove;
                        const select = document.querySelector('[data-day-bulk-target="' + day + '"]');
                        const targetDay = select.value;
                        if (!targetDay) { select.focus(); return; }

                        const sourceList = document.querySelector('[data-day-list="' + day + '"]');
                        const targetList = document.querySelector('[data-day-list="' + targetDay + '"]');
                        if (!sourceList || !targetList) return;

                        const items = Array.from(sourceList.querySelectorAll('.vp-select:checked'))
                            .map(function (cb) { return cb.closest('.frm-vp-item'); });

                        items.forEach(function (item) {
                            const customerId = item.dataset.customer;
                            const sourceCheckbox = item.querySelector('.visit-plan-checkbox');
                            const targetItem = targetList.querySelector('.frm-vp-item[data-customer="' + customerId + '"]');
                            const targetCheckbox = targetItem ? targetItem.querySelector('.visit-plan-checkbox') : null;

                            if (sourceCheckbox && sourceCheckbox.checked) {
                                sourceCheckbox.checked = false;
                                sourceCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            if (targetCheckbox && !targetCheckbox.checked) {
                                targetCheckbox.checked = true;
                                targetCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });

                        const moved = items.length;
                        const targetName = select.options[select.selectedIndex].text;

                        select.value = '';
                        const selectAll = document.querySelector('[data-day-select-all="' + day + '"]');
                        if (selectAll) selectAll.checked = false;
                        updateBulkBar(day);

                        const status = document.querySelector('[data-vp-move-status]');
                        if (status && moved > 0) {
                            status.querySelector('.frm-alert-text').textContent =
                                moved + ' outlet dipindahkan ke hari ' + targetName + '. Klik "Simpan Visit Plan" untuk menyimpan perubahan.';
                            status.hidden = false;
                        }
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