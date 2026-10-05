<x-admin-layout>
    @php
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => "Jum'at",
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $dayName = $dayNames[$dayOfWeekIso] ?? '-';

        // Data untuk pratinjau item & rute per BKB (dirender oleh JS di bawah).
        $bkbsForJs = $assignableBkbs->mapWithKeys(function ($b) {
            return [
                $b->id => $b->items->map(function ($i) {
                    return [
                        'name' => $i->product->name ?? '-',
                        'sku' => $i->product->sku ?? '-',
                        'quantity' => number_format((float) $i->quantity, 2),
                    ];
                })->values()->all(),
            ];
        })->all();

        $routesForJs = $routePreviewByBkb->map(fn ($stops) => $stops->values()->all())->all();
    @endphp

    <div class="frm-page is-medium">
        {{-- Kepala halaman --}}
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Buat Sales Task</h1>
                <p class="frm-sub">Pilih tanggal tugas, lalu BKB Distribusi yang Sales-nya punya Rute Kanvas pada hari itu.</p>
            </div>
            <a href="{{ route('admin.sales-tasks.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="frm-alert is-error is-block" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <div class="frm-alert-text">
                    <ul>
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="frm-stack">
            {{-- Otomasi Sales Task Berdasarkan Rute Harian: tanggal dipilih DULU
                 (form GET terpisah, auto-reload), baru daftar BKB/Sales yang
                 muncul di bawah disaring sesuai Rute Kanvas hari itu. --}}
            <form method="GET" action="{{ route('admin.sales-tasks.create') }}" class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Tanggal Tugas</h2>
                    <span class="frm-tag">{{ $dayName }}</span>
                </div>
                <div class="frm-panel-body">
                    <div class="frm-field">
                        <label class="frm-label" for="task_date_picker">Tanggal</label>
                        <input id="task_date_picker" type="date" name="task_date" value="{{ $taskDate->toDateString() }}"
                               onchange="this.form.submit()" class="frm-input is-wide">
                        <p class="frm-hint">
                            Daftar Sales di bawah otomatis disaring: hanya Sales yang punya <strong>Rute Kanvas</strong> terjadwal pada hari ini yang ditampilkan.
                        </p>
                    </div>

                    <noscript><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Terapkan Tanggal</button></noscript>

                    @if ($skippedCount > 0)
                        <div class="frm-alert is-warn" role="note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                            <span class="frm-alert-text">{{ $skippedCount }} BKB Applied lain disembunyikan karena Sales-nya belum punya Rute Kanvas hari ini.</span>
                        </div>
                    @endif
                </div>
            </form>

            @if ($assignableBkbs->isEmpty())
                <div class="frm-alert is-warn is-block" role="status">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    <div class="frm-alert-text">
                        <p class="frm-alert-title">Tidak ada BKB Distribusi yang bisa ditugaskan</p>
                        <p class="frm-alert-body">
                            Tidak ada BKB Distribusi Applied yang Sales-nya punya Rute Kanvas untuk tanggal ini.
                            @if ($skippedCount > 0)
                                Ada {{ $skippedCount }} BKB Applied tersedia, tapi Sales-nya belum diatur Rute Kanvas-nya untuk hari ini &mdash;
                                atur dulu di menu <a href="{{ route('admin.sales.visit-plans.index') }}" class="panel-link">Visit Plan (Rute Kanvas)</a>.
                            @else
                                Buat/Apply BKB Distribusi terlebih dahulu di menu Distribusi &rarr; BKB Distribusi.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.sales-tasks.store') }}" class="frm-stack">
                @csrf
                <input type="hidden" name="task_date" value="{{ $taskDate->toDateString() }}">

                {{-- Sumber stock --}}
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Sumber Stock</h2>
                    </div>
                    <div class="frm-panel-body">
                        <div class="frm-field">
                            <label class="frm-label" for="bkb_distribusi_id">BKB Distribusi (sumber stock) <span class="frm-req">*</span></label>
                            <select id="bkb_distribusi_id" name="bkb_distribusi_id" required
                                    @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('bkb_distribusi_id')])
                                    @disabled($assignableBkbs->isEmpty())>
                                <option value="">Pilih BKB Distribusi</option>
                                @foreach ($assignableBkbs as $bkb)
                                    <option value="{{ $bkb->id }}" @selected(old('bkb_distribusi_id', $selectedBkbId) == $bkb->id)>
                                        {{ $bkb->code }} &mdash; Sales: {{ $bkb->sales->name ?? '-' }} &mdash; Warehouse: {{ $bkb->warehouse->name ?? '-' }} &mdash; {{ $routePreviewByBkb[$bkb->id]->count() }} toko
                                    </option>
                                @endforeach
                            </select>
                            @error('bkb_distribusi_id') <p class="frm-error">{{ $message }}</p> @enderror
                            <p class="frm-hint">Sales Task hanya menugaskan BKB yang sudah Apply (stock sudah pindah ke Sales Stock). Item &amp; quantity mengikuti BKB, tidak bisa diubah di sini.</p>
                        </div>

                        <div class="frm-grid">
                            <div class="frm-field">
                                <label class="frm-label" for="branch_id">Branch <span class="frm-req">*</span></label>
                                <select id="branch_id" name="branch_id" required @class(['frm-input', 'is-select', 'is-invalid' => $errors->has('branch_id')])>
                                    <option value="">Pilih Branch</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id') <p class="frm-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="frm-field">
                                <label class="frm-label" for="notes">Catatan <span class="frm-opt">(opsional)</span></label>
                                <textarea id="notes" name="notes" rows="2" @class(['frm-input', 'is-area', 'is-invalid' => $errors->has('notes')])>{{ old('notes') }}</textarea>
                                @error('notes') <p class="frm-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Stock yang dibawa (pratinjau, dirender JS) --}}
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Stock yang Dibawa</h2>
                        <span class="frm-count">Dari BKB terpilih</span>
                    </div>
                    <div class="frm-items-wrap is-flush">
                        <table class="frm-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="is-num">Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="bkb-items-body"></tbody>
                        </table>
                    </div>
                </section>

                {{-- Otomasi Sales Task Berdasarkan Rute Harian: TIDAK ADA LAGI
                     input manual "+ Tambah Customer". Daftar toko di bawah ini
                     murni preview read-only, ditarik otomatis dari Rute Kanvas
                     (SalesVisitPlan) Sales terpilih pada tanggal tugas di atas. --}}
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2 class="panel-title">Rute Kunjungan Hari Ini</h2>
                            <p class="frm-meta">Otomatis dari Rute Kanvas. Untuk mengubah urutan/isi rute, edit lewat menu Visit Plan &mdash; bukan di sini.</p>
                        </div>
                    </div>
                    <div id="plan-body"></div>

                    <div class="frm-panel-body">
                        <div class="frm-alert is-info" role="note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            <span class="frm-alert-text">Dokumen standar (Surat Jalan, Barang Keluar, Daftar Stock) otomatis dibuat mengacu ke BKB ini dan dirilis ke Sales saat Task di-Apply.</span>
                        </div>
                    </div>

                    <div class="frm-panel-foot">
                        <a href="{{ route('admin.sales-tasks.index') }}" class="adm-btn adm-btn-ghost adm-btn-sm">Batal</a>
                        <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm" @disabled($assignableBkbs->isEmpty())>Simpan Draft</button>
                    </div>
                </section>
            </form>
        </div>
    </div>

    <script>
        const bkbs = @json($bkbsForJs);
        const routes = @json($routesForJs);

        // Semua teks disisipkan lewat textContent (nama produk/customer berasal dari database).
        function el(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }

        function renderBkbItems() {
            const select = document.getElementById('bkb_distribusi_id');
            const tbody = document.getElementById('bkb-items-body');
            const items = select ? bkbs[select.value] : null;
            tbody.replaceChildren();

            if (!items || items.length === 0) {
                const tr = el('tr');
                const td = el('td', 'frm-items-empty', 'Pilih BKB Distribusi di atas untuk melihat item.');
                td.colSpan = 2;
                tr.appendChild(td);
                tbody.appendChild(tr);
                return;
            }

            items.forEach(function (i) {
                const tr = el('tr');
                const name = el('td');
                name.append(el('span', 'frm-line frm-name', i.name), el('span', 'frm-line', i.sku));
                const qty = el('td', 'is-num');
                qty.appendChild(el('span', 'frm-num is-strong', i.quantity));
                tr.append(name, qty);
                tbody.appendChild(tr);
            });
        }

        function renderRoutePreview() {
            const select = document.getElementById('bkb_distribusi_id');
            const box = document.getElementById('plan-body');
            const stops = select ? routes[select.value] : null;
            box.replaceChildren();

            if (!stops || stops.length === 0) {
                box.appendChild(el('p', 'panel-empty', 'Pilih BKB Distribusi di atas untuk melihat rute.'));
                return;
            }

            const list = el('ul', 'row-list');
            stops.forEach(function (name, i) {
                const li = el('li', 'row-item');
                const stop = el('div', 'frm-stop');
                stop.append(el('span', 'frm-stop-no', String(i + 1)), el('span', 'frm-name', name));
                li.appendChild(stop);
                list.appendChild(li);
            });
            box.appendChild(list);
        }

        document.getElementById('bkb_distribusi_id')?.addEventListener('change', function () {
            renderBkbItems();
            renderRoutePreview();
        });
        renderBkbItems();
        renderRoutePreview();
    </script>
</x-admin-layout>