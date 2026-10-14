{{-- Panel revisi/pembatalan + riwayat perubahan. Dipakai di halaman detail BKB & BTB. --}}
@php
    $docStatus = $doc->status;
    $storeRoute = $kind === 'BKB' ? 'bkb' : 'btb';
@endphp

@if ($docStatus !== 'cancelled')
    @can('distribution.apply')
        <div class="frm-head-actions" style="margin: 16px 0;">
            <a href="{{ route('admin.distribution.'.$storeRoute.'.edit', $doc) }}" class="adm-btn adm-btn-ghost">Revisi {{ $kind }}</a>
            @if ($docStatus === 'applied')
                <button type="button" class="adm-btn adm-btn-danger" onclick="var p=document.getElementById('void-panel'); p.hidden=!p.hidden">Batalkan &amp; Kembalikan Stok</button>
            @endif
        </div>

        @if ($docStatus === 'applied')
            <div class="panel" id="void-panel" hidden style="margin-bottom:16px;">
                <div class="panel-head"><h2 class="panel-title">Batalkan {{ $kind }} yang sudah Applied</h2></div>
                <form action="{{ route('admin.distribution.'.$storeRoute.'.void', $doc) }}" method="POST" onsubmit="return confirm('Batalkan dokumen ini dan kembalikan stok?')">
                    @csrf
                    <div class="frm-modal-body" style="padding: 18px 22px;">
                        <div class="frm-field">
                            <label class="frm-label">Alasan Pembatalan <span class="frm-req">*</span></label>
                            <input type="text" name="reason" required maxlength="500" class="frm-input" placeholder="mis. Dokumen dobel / salah input">
                        </div>
                    </div>
                    <div class="frm-modal-foot"><button type="submit" class="adm-btn adm-btn-danger">Batalkan &amp; Kembalikan Stok</button></div>
                </form>
            </div>
        @endif
    @endcan
@endif

@if (($history ?? collect())->isNotEmpty())
    <div class="panel" style="margin-bottom:16px;">
        <div class="panel-head"><h2 class="panel-title">Riwayat Revisi</h2></div>
        <div class="frm-table-wrap">
            <table class="frm-table">
                <thead><tr><th>Waktu</th><th>Aksi</th><th>Oleh</th><th>Alasan</th><th>Dampak Stok</th></tr></thead>
                <tbody>
                    @foreach ($history as $log)
                        @php
                            $impact = collect($log->after['stock_impact'] ?? [])->filter(fn ($r) => (float) ($r['delta'] ?? 0) != 0.0);
                            $names = \App\Models\Product::whereIn('id', $impact->pluck('product_id'))->pluck('name', 'id');
                        @endphp
                        <tr>
                            <td data-label="Waktu">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td data-label="Aksi">{{ $log->action === 'void' ? 'Dibatalkan' : 'Direvisi' }}</td>
                            <td data-label="Oleh">{{ $log->user->name ?? '-' }}</td>
                            <td data-label="Alasan">{{ $log->after['reason'] ?? '-' }}</td>
                            <td data-label="Dampak Stok">
                                @if (! ($log->after['stock_applied'] ?? false))
                                    <span class="frm-meta">Tidak ada (belum Applied)</span>
                                @else
                                    @forelse ($impact as $r)
                                        <div>{{ $names[$r['product_id']] ?? '#'.$r['product_id'] }}: {{ rtrim(rtrim(number_format($r['old'], 2, '.', ''), '0'), '.') }} &rarr; {{ rtrim(rtrim(number_format($r['new'], 2, '.', ''), '0'), '.') }}</div>
                                    @empty
                                        <span class="frm-meta">Jumlah tidak berubah</span>
                                    @endforelse
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
