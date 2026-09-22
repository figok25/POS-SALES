<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $format === 'a4' ? 'Invoice' : 'Struk' }} {{ $invoice->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; margin: 0; background: #e5e7eb; }

        .toolbar { padding: 12px; background: #f3f4f6; display: flex; gap: 8px; justify-content: center; align-items: center; border-bottom: 1px solid #d1d5db; }
        .toolbar a, .toolbar button { padding: 8px 14px; border-radius: 6px; font-size: 14px; text-decoration: none; border: 1px solid #d1d5db; background: #fff; color: #111; cursor: pointer; }
        .toolbar button.primary { background: #2563eb; color: #fff; border-color: #2563eb; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }

        @if ($format === 'a4')
            @page { size: A4; margin: 15mm; }
            .sheet { background: #fff; max-width: 190mm; margin: 16px auto; padding: 14mm; box-shadow: 0 0 6px rgba(0,0,0,.15); }
            @media print { .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; } }
            .a4-header { display: flex; justify-content: space-between; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
            .a4-header h2 { margin: 0 0 4px; }
            .a4-parties { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 13px; }
            table.a4-items { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 14px; }
            table.a4-items th, table.a4-items td { border: 1px solid #ccc; padding: 6px 8px; }
            table.a4-items th { background: #f3f4f6; text-align: left; }
            table.a4-totals { width: 300px; margin-left: auto; font-size: 13px; }
            table.a4-totals td { padding: 3px 0; }
            .a4-signatures { display: flex; justify-content: space-between; margin-top: 60px; font-size: 13px; text-align: center; }
        @else
            @page { size: 80mm auto; margin: 2mm; }
            .sheet { background: #fff; max-width: 78mm; margin: 16px auto; padding: 6px; font-size: 12px; line-height: 1.45; }
            @media print { .sheet { margin: 0 auto; box-shadow: none; } }
            .struk-center { text-align: center; }
            .struk hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
            table.struk-kv { width: 100%; font-size: 12px; }
            table.struk-kv td { padding: 1px 0; vertical-align: top; }
            table.struk-items { width: 100%; font-size: 12px; border-collapse: collapse; }
            table.struk-items td { padding: 1px 0; vertical-align: top; }
        @endif
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="primary" onclick="window.print()">&#128424; Cetak</button>
        <a href="{{ route('admin.sales.invoices.print', ['invoice' => $invoice, 'format' => $format === 'a4' ? 'struk' : 'a4']) }}">
            Ganti ke Format {{ $format === 'a4' ? 'Struk' : 'A4' }}
        </a>
        <a href="{{ route('admin.sales.invoices.show', $invoice) }}">&larr; Kembali ke Invoice</a>
    </div>

    @if ($format === 'a4')
        <div class="sheet">
            <div class="a4-header">
                <div>
                    <h2>{{ $company->name ?? config('app.name', 'POS & Sales') }}</h2>
                    @if ($company?->address)<div>{{ $company->address }}</div>@endif
                    @if ($company?->phone)<div>Telp: {{ $company->phone }}</div>@endif
                    @if ($company?->npwp)<div>NPWP: {{ $company->npwp }}</div>@endif
                </div>
                <div style="text-align:right;">
                    <h2>INVOICE</h2>
                    <div>No: {{ $invoice->code }}</div>
                    <div>Tanggal: {{ $invoice->date->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="a4-parties">
                <div>
                    <strong>Kepada Yth:</strong><br>
                    {{ $invoice->customer->name ?? '-' }}<br>
                    {{ $invoice->customer->address ?? '' }}<br>
                    {{ $invoice->customer->phone ?? '' }}
                </div>
                <div style="text-align:right;">
                    <strong>Sales:</strong> {{ $invoice->sales->name ?? '-' }}<br>
                    <strong>Status:</strong> {{ ucfirst($invoice->status) }}
                </div>
            </div>

            <table class="a4-items">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align:right;">Qty</th>
                        <th style="text-align:right;">Harga</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $line)
                        <tr>
                            <td>{{ $line->product->name ?? '-' }}</td>
                            <td style="text-align:right;">{{ rtrim(rtrim(number_format($line->quantity, 2, '.', ''), '0'), '.') }}</td>
                            <td style="text-align:right;">{{ number_format($line->price, 0, ',', '.') }}</td>
                            <td style="text-align:right;">{{ number_format($line->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="a4-totals">
                <tr><td>Subtotal</td><td style="text-align:right;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
                <tr><td>Diskon</td><td style="text-align:right;">Rp {{ number_format($invoice->discount, 0, ',', '.') }}</td></tr>
                <tr><td>Pajak</td><td style="text-align:right;">Rp {{ number_format($invoice->tax, 0, ',', '.') }}</td></tr>
                <tr style="border-top:1px solid #999;"><td><strong>Grand Total</strong></td><td style="text-align:right;"><strong>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></td></tr>
                <tr><td>Terbayar</td><td style="text-align:right;">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td></tr>
                <tr><td><strong>Outstanding</strong></td><td style="text-align:right;"><strong>Rp {{ number_format($invoice->outstanding(), 0, ',', '.') }}</strong></td></tr>
            </table>

            <div class="a4-signatures">
                <div>Dibuat oleh,<br><br><br>(....................)</div>
                <div>Diterima oleh,<br><br><br>(....................)</div>
            </div>
        </div>
    @else
        <div class="sheet struk">
            <div class="struk-center">
                <strong>{{ $company->name ?? config('app.name', 'POS & Sales') }}</strong><br>
                @if ($company?->address)<span>{{ $company->address }}</span><br>@endif
                @if ($company?->phone)<span>{{ $company->phone }}</span>@endif
            </div>
            <hr>
            <table class="struk-kv">
                <tr><td>No. Invoice</td><td style="text-align:right;">{{ $invoice->code }}</td></tr>
                <tr><td>Tanggal</td><td style="text-align:right;">{{ $invoice->date->format('d/m/Y') }}</td></tr>
                <tr><td>Sales</td><td style="text-align:right;">{{ $invoice->sales->name ?? '-' }}</td></tr>
                <tr><td>Customer</td><td style="text-align:right;">{{ $invoice->customer->name ?? '-' }}</td></tr>
            </table>
            <hr>
            <table class="struk-items">
                @foreach ($invoice->items as $line)
                    <tr><td colspan="2">{{ $line->product->name ?? '-' }}</td></tr>
                    <tr>
                        <td>{{ rtrim(rtrim(number_format($line->quantity, 2, '.', ''), '0'), '.') }} x {{ number_format($line->price, 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($line->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
            <hr>
            <table class="struk-kv">
                <tr><td>Subtotal</td><td style="text-align:right;">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
                <tr><td>Diskon</td><td style="text-align:right;">{{ number_format($invoice->discount, 0, ',', '.') }}</td></tr>
                <tr><td>Pajak</td><td style="text-align:right;">{{ number_format($invoice->tax, 0, ',', '.') }}</td></tr>
                <tr><td><strong>Grand Total</strong></td><td style="text-align:right;"><strong>{{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></td></tr>
                <tr><td>Terbayar</td><td style="text-align:right;">{{ number_format($invoice->paid_amount, 0, ',', '.') }}</td></tr>
            </table>
            <hr>
            <p class="struk-center">Terima kasih atas pembayaran Anda</p>
        </div>
    @endif
</body>
</html>
