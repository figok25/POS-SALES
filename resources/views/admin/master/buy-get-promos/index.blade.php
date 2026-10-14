@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $branchChecks = function ($selectedIds, $idPrefix) use ($branches) {
        $html = '<div style="display:flex;flex-wrap:wrap;gap:.35rem 1rem">';
        foreach ($branches as $b) {
            $chk = in_array($b->id, $selectedIds, true) ? ' checked' : '';
            $html .= '<label style="display:flex;align-items:center;gap:.3rem"><input type="checkbox" name="branch_ids[]" value="'.$b->id.'"'.$chk.'> '.e($b->name).'</label>';
        }

        return $html.'</div>';
    };
    $productOptions = function ($selected = null) use ($products) {
        $html = '<option value="">- pilih produk -</option>';
        foreach ($products as $p) {
            $sel = (string) $selected === (string) $p->id ? ' selected' : '';
            $html .= '<option value="'.$p->id.'"'.$sel.'>'.e($p->name).($p->sku ? ' ('.e($p->sku).')' : '').'</option>';
        }
        return $html;
    };
@endphp

<x-admin-layout>
    <div class="frm-page">
        <div class="frm-head">
            <div>
                <h1 class="frm-title">Program Beli Gratis</h1>
                <p class="frm-sub">Program distributor &ldquo;Beli X produk A, gratis Y produk B&rdquo;. Berlaku kelipatan, dan barang gratis otomatis memotong <strong>stok Sales</strong>. Kalau stok barang gratis di Sales habis, Customer tidak mendapat gratis (transaksi tetap jalan). Tiap program dipilih berlaku di Depo mana saja.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="frm-alert" role="status"><span class="frm-alert-text">{{ session('status') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="frm-alert" role="alert" style="color:#b91c1c"><span class="frm-alert-text">{{ $errors->first() }}</span></div>
        @endif

        {{-- Tambah program --}}
        <div class="panel" style="padding:1rem;margin-bottom:1rem">
            <h2 class="frm-title" style="font-size:1rem;margin-bottom:.75rem">Tambah program</h2>
            <form method="POST" action="{{ route('admin.master.buy-get-promos.store') }}" class="frm-grid">
                @csrf
                <div class="frm-field">
                    <label class="frm-label">Nama program <span class="frm-req">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="frm-input" placeholder="mis. Promo Slim Vanila + Kopi">
                </div>
                <div class="frm-field">
                    <label class="frm-label">Jenis program <span class="frm-req">*</span></label>
                    <select name="type" class="frm-input">
                        <option value="buy_get" @selected(old('type', 'buy_get') === 'buy_get')>Beli Gratis (beli produk, dapat gratis)</option>
                        <option value="shell_exchange" @selected(old('type') === 'shell_exchange')>Tukar Cangkang (serahkan bungkus kosong, dapat gratis)</option>
                    </select>
                    <div class="frm-sub">Tukar Cangkang: &ldquo;Produk yang dibeli&rdquo; = produk cangkang, &ldquo;Jumlah beli&rdquo; = jumlah cangkang per tukar (mis. 8). Contoh: 8 cangkang Ori Kretek = gratis 1 Ori Kretek.</div>
                </div>
                <div class="frm-field">
                    <label class="frm-label">Produk yang dibeli / cangkang <span class="frm-req">*</span></label>
                    <select name="buy_product_id" required class="frm-input">{!! $productOptions(old('buy_product_id')) !!}</select>
                </div>
                <div class="frm-field">
                    <label class="frm-label">Jumlah beli <span class="frm-req">*</span></label>
                    <input type="number" name="buy_qty" value="{{ old('buy_qty', 1) }}" step="0.01" min="0.01" required class="frm-input">
                </div>
                <div class="frm-field">
                    <label class="frm-label">Produk gratis <span class="frm-req">*</span></label>
                    <select name="free_product_id" required class="frm-input">{!! $productOptions(old('free_product_id')) !!}</select>
                </div>
                <div class="frm-field">
                    <label class="frm-label">Jumlah gratis <span class="frm-req">*</span></label>
                    <input type="number" name="free_qty" value="{{ old('free_qty', 1) }}" step="0.01" min="0.01" required class="frm-input">
                </div>
                <div class="frm-field">
                    <label class="frm-label">Mulai (opsional)</label>
                    <input type="date" name="starts_on" value="{{ old('starts_on') }}" class="frm-input">
                </div>
                <div class="frm-field">
                    <label class="frm-label">Selesai (opsional)</label>
                    <input type="date" name="ends_on" value="{{ old('ends_on') }}" class="frm-input">
                </div>
                <div class="frm-field">
                    <label class="frm-label">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500" class="frm-input">
                </div>
                <div class="frm-field" style="grid-column:1/-1">
                    <label class="frm-label">Berlaku di Depo <span class="frm-req">*</span></label>
                    {!! $branchChecks(array_map('intval', old('branch_ids', $defaultBranchId ? [$defaultBranchId] : [])), 'new') !!}
                    <div class="frm-sub">Program hanya berlaku untuk Sales di Depo yang dicentang. Tiap Depo bisa punya program berbeda.</div>
                </div>
                <div class="frm-field">
                    <label class="frm-label"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                </div>
                <div class="frm-field">
                    <button type="submit" class="adm-btn adm-btn-primary">Simpan program</button>
                </div>
            </form>
        </div>

        {{-- Daftar program --}}
        <div class="panel">
            @if ($promos->isEmpty())
                <div class="frm-empty">
                    <p class="frm-empty-title">Belum ada program</p>
                    <p class="frm-empty-text">Tambahkan program di atas.</p>
                </div>
            @else
                <div class="frm-table-wrap">
                    <table class="frm-table">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Aturan</th>
                                <th>Depo</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th class="is-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promos as $promo)
                                <tr>
                                    <td><div class="frm-name">{{ $promo->name }}</div>@if ($promo->notes)<div class="frm-sub">{{ $promo->notes }}</div>@endif</td>
                                    <td data-label="Aturan">@if ($promo->isShellExchange())<span class="frm-status is-off">Tukar Cangkang</span> {{ $fmt($promo->buy_qty) }} cangkang @else Beli {{ $fmt($promo->buy_qty) }} @endif<strong>{{ $promo->buyProduct->name ?? '-' }}</strong> &rarr; gratis {{ $fmt($promo->free_qty) }} <strong>{{ $promo->freeProduct->name ?? '-' }}</strong></td>
                                    <td data-label="Depo">
                                        @forelse ($promo->branches as $b)
                                            <span class="frm-status is-on">{{ $b->name }}</span>
                                        @empty
                                            <span class="frm-status is-off">Belum ada Depo</span>
                                        @endforelse
                                    </td>
                                    <td data-label="Periode">{{ $promo->starts_on?->format('d/m/Y') ?? 'sejak awal' }} &ndash; {{ $promo->ends_on?->format('d/m/Y') ?? 'tanpa batas' }}</td>
                                    <td class="frm-cell-status">
                                        @if ($promo->is_active)
                                            <span class="frm-status is-on">Aktif</span>
                                        @else
                                            <span class="frm-status is-off">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="is-end">
                                        <button type="button" class="adm-btn adm-btn-ghost adm-btn-sm" onclick="var r=document.getElementById('edit-{{ $promo->id }}'); r.hidden=!r.hidden">Edit</button>
                                    </td>
                                </tr>
                                <tr id="edit-{{ $promo->id }}" hidden>
                                    <td colspan="6">
                                        <form method="POST" action="{{ route('admin.master.buy-get-promos.update', $promo) }}" class="frm-grid">
                                            @csrf
                                            @method('PUT')
                                            <div class="frm-field"><label class="frm-label">Nama program</label>
                                                <input type="text" name="name" value="{{ $promo->name }}" required maxlength="255" class="frm-input"></div>
                                            <div class="frm-field"><label class="frm-label">Jenis program</label>
                                                <select name="type" class="frm-input">
                                                    <option value="buy_get" @selected($promo->type === 'buy_get')>Beli Gratis</option>
                                                    <option value="shell_exchange" @selected($promo->type === 'shell_exchange')>Tukar Cangkang</option>
                                                </select></div>
                                            <div class="frm-field"><label class="frm-label">Produk dibeli / cangkang</label>
                                                <select name="buy_product_id" required class="frm-input">{!! $productOptions($promo->buy_product_id) !!}</select></div>
                                            <div class="frm-field"><label class="frm-label">Jumlah beli</label>
                                                <input type="number" name="buy_qty" value="{{ $promo->buy_qty }}" step="0.01" min="0.01" required class="frm-input"></div>
                                            <div class="frm-field"><label class="frm-label">Produk gratis</label>
                                                <select name="free_product_id" required class="frm-input">{!! $productOptions($promo->free_product_id) !!}</select></div>
                                            <div class="frm-field"><label class="frm-label">Jumlah gratis</label>
                                                <input type="number" name="free_qty" value="{{ $promo->free_qty }}" step="0.01" min="0.01" required class="frm-input"></div>
                                            <div class="frm-field"><label class="frm-label">Mulai</label>
                                                <input type="date" name="starts_on" value="{{ $promo->starts_on?->toDateString() }}" class="frm-input"></div>
                                            <div class="frm-field"><label class="frm-label">Selesai</label>
                                                <input type="date" name="ends_on" value="{{ $promo->ends_on?->toDateString() }}" class="frm-input"></div>
                                            <div class="frm-field"><label class="frm-label">Catatan</label>
                                                <input type="text" name="notes" value="{{ $promo->notes }}" maxlength="500" class="frm-input"></div>
                                            <div class="frm-field" style="grid-column:1/-1"><label class="frm-label">Berlaku di Depo</label>
                                                {!! $branchChecks($promo->branches->pluck('id')->map(fn ($i) => (int) $i)->all(), 'e'.$promo->id) !!}</div>
                                            <div class="frm-field"><label class="frm-label"><input type="checkbox" name="is_active" value="1" @checked($promo->is_active)> Aktif</label></div>
                                            <div class="frm-field"><button type="submit" class="adm-btn adm-btn-primary adm-btn-sm">Simpan</button></div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($promos->hasPages())
                    <div class="frm-pager">{{ $promos->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>
