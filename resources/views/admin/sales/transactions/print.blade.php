@php
    $isA4 = $format === 'a4';
    $qty  = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    $rp   = fn ($v) => number_format($v, 0, ',', '.');
    $css  = 'css/print.css';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isA4 ? 'Nota Transaksi' : 'Struk' }} {{ $transaction->code }}</title>
    <link rel="stylesheet" href="{{ asset($css) }}?v={{ @filemtime(public_path($css)) }}">
</head>
<body class="{{ $isA4 ? 'format-a4' : 'format-struk' }}">
    {{-- Toolbar (tidak ikut tercetak) --}}
    <div class="toolbar no-print">
        <button type="button" class="toolbar-btn is-primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak
        </button>

        <div class="toolbar-seg" role="group" aria-label="Format cetak">
            <a href="{{ route('admin.sales.transactions.print', ['transaction' => $transaction, 'format' => 'a4']) }}"
               class="{{ $isA4 ? 'is-active' : '' }}" @if ($isA4) aria-current="true" @endif>A4</a>
            <a href="{{ route('admin.sales.transactions.print', ['transaction' => $transaction, 'format' => 'struk']) }}"
               class="{{ ! $isA4 ? 'is-active' : '' }}" @if (! $isA4) aria-current="true" @endif>Struk</a>
        </div>

        <a href="{{ route('admin.sales.transactions.show', $transaction) }}" class="toolbar-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Kembali ke Transaksi
        </a>
    </div>

    @if ($isA4)
        <div class="sheet">
            <div class="a4-header">
                <div class="a4-company">
                    <h2>{{ $company->name ?? config('app.name', 'POS & Sales') }}</h2>
                    @if ($company?->address)<div>{{ $company->address }}</div>@endif
                    @if ($company?->phone)<div>Telp: {{ $company->phone }}</div>@endif
                </div>
                <div class="r">
                    <h2 class="a4-title">NOTA TRANSAKSI</h2>
                    <div>No: {{ $transaction->code }}</div>
                    <div>Tanggal: {{ $transaction->created_at->format('d/m/Y H:i') }}</div>
                    @if ($transaction->invoice)<div>Invoice: {{ $transaction->invoice->code }}</div>@endif
                </div>
            </div>

            <div class="a4-parties">
                <div>
                    <span class="a4-label">Toko (Kepada Yth)</span>
                    <strong>{{ $transaction->customerLabel() }}</strong><br>
                    {{ $transaction->customer->address ?? '' }}<br>
                    {{ $transaction->customer->phone ?? '' }}
                </div>
                <div class="r">
                    <span class="a4-label">Sales</span>
                    {{ $transaction->sales->name ?? '-' }}<br>
                    <span class="a4-label is-spaced">Status</span>
                    <span class="a4-status is-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                </div>
            </div>

            <table class="a4-items">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="r">Qty</th>
                        <th class="r">Harga</th>
                        <th class="r">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaction->items as $line)
                        <tr>
                            <td>{{ $line->product->name ?? '-' }}</td>
                            <td class="r">{{ $qty($line->quantity) }}</td>
                            <td class="r">{{ $rp($line->price) }}</td>
                            <td class="r">{{ $rp($line->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="a4-totals">
                <tr><td>Subtotal</td><td class="r">Rp {{ $rp($transaction->subtotal) }}</td></tr>
                <tr><td>Diskon</td><td class="r">Rp {{ $rp($transaction->discount) }}</td></tr>
                <tr><td>Pajak</td><td class="r">Rp {{ $rp($transaction->tax) }}</td></tr>
                <tr class="is-grand"><td>Total</td><td class="r">Rp {{ $rp($transaction->total) }}</td></tr>
            </table>

            @if ($transaction->notes)
                <p class="a4-notes"><strong>Catatan:</strong> {{ $transaction->notes }}</p>
            @endif

            <div class="a4-signatures">
                <div>Dibuat oleh,<br><br><br>(....................)</div>
                <div>Diterima oleh,<br><br><br>(....................)</div>
            </div>
        </div>
    @else
        <div class="sheet struk">
            <div class="struk-center">
                <strong class="struk-name">{{ $company->name ?? config('app.name', 'POS & Sales') }}</strong><br>
                @if ($company?->address)<span>{{ $company->address }}</span><br>@endif
                @if ($company?->phone)<span>{{ $company->phone }}</span>@endif
            </div>
            <hr>
            <table class="struk-kv">
                <tr><td>No. Transaksi</td><td class="r">{{ $transaction->code }}</td></tr>
                <tr><td>Tanggal</td><td class="r">{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
                <tr><td>Sales</td><td class="r">{{ $transaction->sales->name ?? '-' }}</td></tr>
            </table>
            <hr>
            <div class="struk-store" style="word-break: break-word; line-height: 1.35">
                <div>Toko:</div>
                <strong>{{ $transaction->customerLabel() }}</strong>
                @if ($transaction->customer?->address)<div>{{ $transaction->customer->address }}</div>@endif
            </div>
            <hr>
            <table class="struk-items">
                @foreach ($transaction->items as $line)
                    <tr><td colspan="2">{{ $line->product->name ?? '-' }}</td></tr>
                    <tr>
                        <td>{{ $qty($line->quantity) }} x {{ $rp($line->price) }}</td>
                        <td class="r">{{ $rp($line->subtotal) }}</td>
                    </tr>
                @endforeach
            </table>
            <hr>
            <table class="struk-kv">
                <tr><td>Subtotal</td><td class="r">{{ $rp($transaction->subtotal) }}</td></tr>
                <tr><td>Diskon</td><td class="r">{{ $rp($transaction->discount) }}</td></tr>
                <tr><td>Pajak</td><td class="r">{{ $rp($transaction->tax) }}</td></tr>
                <tr class="is-grand"><td>Total</td><td class="r">{{ $rp($transaction->total) }}</td></tr>
            </table>
            <hr>
            <p class="struk-center">Terima kasih</p>
        </div>
    @endif
</body>
</html>