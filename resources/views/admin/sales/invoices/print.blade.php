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
    <title>{{ $isA4 ? 'Invoice' : 'Struk' }} {{ $invoice->code }}</title>
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
            <a href="{{ route('admin.sales.invoices.print', ['invoice' => $invoice, 'format' => 'a4']) }}"
               class="{{ $isA4 ? 'is-active' : '' }}" @if ($isA4) aria-current="true" @endif>A4</a>
            <a href="{{ route('admin.sales.invoices.print', ['invoice' => $invoice, 'format' => 'struk']) }}"
               class="{{ ! $isA4 ? 'is-active' : '' }}" @if (! $isA4) aria-current="true" @endif>Struk</a>
        </div>

        <a href="{{ route('admin.sales.invoices.show', $invoice) }}" class="toolbar-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Kembali ke Invoice
        </a>
    </div>

    @if ($isA4)
        <div class="sheet">
            <div class="a4-header">
                <div class="a4-company">
                    <h2>{{ $company->name ?? config('app.name', 'POS & Sales') }}</h2>
                    @if ($company?->address)<div>{{ $company->address }}</div>@endif
                    @if ($company?->phone)<div>Telp: {{ $company->phone }}</div>@endif
                    @if ($company?->npwp)<div>NPWP: {{ $company->npwp }}</div>@endif
                </div>
                <div class="r">
                    <h2 class="a4-title">INVOICE</h2>
                    <div>No: {{ $invoice->code }}</div>
                    <div>Tanggal: {{ $invoice->date->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="a4-parties">
                <div>
                    <span class="a4-label">Toko (Kepada Yth)</span>
                    <strong>{{ $invoice->customer->name ?? '-' }}</strong><br>
                    {{ $invoice->customer->address ?? '' }}<br>
                    {{ $invoice->customer->phone ?? '' }}
                </div>
                <div class="r">
                    <span class="a4-label">Sales</span>
                    {{ $invoice->sales->name ?? '-' }}<br>
                    <span class="a4-label is-spaced">Status</span>
                    <span class="a4-status is-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
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
                    @foreach ($invoice->items as $line)
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
                <tr><td>Subtotal</td><td class="r">Rp {{ $rp($invoice->subtotal) }}</td></tr>
                <tr><td>Diskon</td><td class="r">Rp {{ $rp($invoice->discount) }}</td></tr>
                <tr><td>Pajak</td><td class="r">Rp {{ $rp($invoice->tax) }}</td></tr>
                <tr class="is-grand"><td>Grand Total</td><td class="r">Rp {{ $rp($invoice->grand_total) }}</td></tr>
                <tr><td>Terbayar</td><td class="r">Rp {{ $rp($invoice->paid_amount) }}</td></tr>
                <tr class="is-due"><td>Outstanding</td><td class="r">Rp {{ $rp($invoice->outstanding()) }}</td></tr>
            </table>

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
                <tr><td>No. Invoice</td><td class="r">{{ $invoice->code }}</td></tr>
                <tr><td>Tanggal</td><td class="r">{{ $invoice->date->format('d/m/Y') }}</td></tr>
                <tr><td>Sales</td><td class="r">{{ $invoice->sales->name ?? '-' }}</td></tr>
            </table>
            <hr>
            <div class="struk-store" style="word-break: break-word; line-height: 1.35">
                <div>Toko:</div>
                <strong>{{ $invoice->customer->name ?? '-' }}</strong>
                @if ($invoice->customer?->address)<div>{{ $invoice->customer->address }}</div>@endif
            </div>
            <hr>
            <table class="struk-items">
                @foreach ($invoice->items as $line)
                    <tr><td colspan="2">{{ $line->product->name ?? '-' }}</td></tr>
                    <tr>
                        <td>{{ $qty($line->quantity) }} x {{ $rp($line->price) }}</td>
                        <td class="r">{{ $rp($line->subtotal) }}</td>
                    </tr>
                @endforeach
            </table>
            <hr>
            <table class="struk-kv">
                <tr><td>Subtotal</td><td class="r">{{ $rp($invoice->subtotal) }}</td></tr>
                <tr><td>Diskon</td><td class="r">{{ $rp($invoice->discount) }}</td></tr>
                <tr><td>Pajak</td><td class="r">{{ $rp($invoice->tax) }}</td></tr>
                <tr class="is-grand"><td>Grand Total</td><td class="r">{{ $rp($invoice->grand_total) }}</td></tr>
                <tr><td>Terbayar</td><td class="r">{{ $rp($invoice->paid_amount) }}</td></tr>
            </table>
            <hr>
            <p class="struk-center">Terima kasih atas pembayaran Anda</p>
        </div>
    @endif
</body>
</html>