<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $format === 'a4' ? 'Nota Transaksi' : 'Struk' }} {{ $transaction->code }}</title>
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
        <a href="{{ route('admin.sales.transactions.print', ['transaction' => $transaction, 'format' => $format === 'a4' ? 'struk' : 'a4']) }}">
            Ganti ke Format {{ $format === 'a4' ? 'Struk' : 'A4' }}
        </a>
        <a href="{{ route('admin.sales.transactions.show', $transaction) }}">&larr; Kembali ke Transaksi</a>
    </div>

    @if ($format === 'a4')
        <div class="sheet">
            <div class="a4-header">
                <div>
                    <h2>{{ $company->name ?? config('app.name', 'POS & Sales') }}</h2>
                    @if ($company?->address)<div>{{ $company->address }}</div>@endif
                    @if ($company?->phone)<div>Telp: {{ $company->phone }}</div>@endif
                </div>
                <div style="text-align:right;">
                    <h2>NOTA TRANSAKSI</h2>
                    <div>No: {{ $transaction->code }}</div>
                    <div>Tanggal: {{ $transaction->created_at->format('d/m/Y H:i') }}</div>
                    @if ($transaction->invoice)<div>Invoice: {{ $transaction->invoice->code }}</div>@endif
                </div>
            </div>

            <div class="a4-parties">
                <div>
                    <strong>Kepada Yth:</strong><br>
                    {{ $transaction->customer->name ?? '-' }}<br>
                    {{ $transaction->customer->address ?? '' }}<br>
                    {{ $transaction->customer->phone ?? '' }}
                </div>
                <div style="text-align:right;">
                    <strong>Sales:</strong> {{ $transaction->sales->name ?? '-' }}<br>
                    <strong>Status:</strong> {{ ucfirst($transaction->status) }}
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
                    @foreach ($transaction->items as $line)
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
                <tr><td>Subtotal</td><td style="text-align:right;">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr>
                <tr><td>Diskon</td><td style="text-align:right;">Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td></tr>
                <tr><td>Pajak</td><td style="text-align:right;">Rp {{ number_format($transaction->tax, 0, ',', '.') }}</td></tr>
                <tr style="border-top:1px solid #999;"><td><strong>Total</strong></td><td style="text-align:right;"><strong>Rp {{ number_format($transaction->total, 0, ',', '.') }}</strong></td></tr>
            </table>

            @if ($transaction->notes)
                <p style="font-size:13px; margin-top:14px;"><strong>Catatan:</strong> {{ $transaction->notes }}</p>
            @endif

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
                <tr><td>No. Transaksi</td><td style="text-align:right;">{{ $transaction->code }}</td></tr>
                <tr><td>Tanggal</td><td style="text-align:right;">{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
                <tr><td>Sales</td><td style="text-align:right;">{{ $transaction->sales->name ?? '-' }}</td></tr>
                <tr><td>Customer</td><td style="text-align:right;">{{ $transaction->customer->name ?? '-' }}</td></tr>
            </table>
            <hr>
            <table class="struk-items">
                @foreach ($transaction->items as $line)
                    <tr><td colspan="2">{{ $line->product->name ?? '-' }}</td></tr>
                    <tr>
                        <td>{{ rtrim(rtrim(number_format($line->quantity, 2, '.', ''), '0'), '.') }} x {{ number_format($line->price, 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($line->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
            <hr>
            <table class="struk-kv">
                <tr><td>Subtotal</td><td style="text-align:right;">{{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr>
                <tr><td>Diskon</td><td style="text-align:right;">{{ number_format($transaction->discount, 0, ',', '.') }}</td></tr>
                <tr><td>Pajak</td><td style="text-align:right;">{{ number_format($transaction->tax, 0, ',', '.') }}</td></tr>
                <tr><td><strong>Total</strong></td><td style="text-align:right;"><strong>{{ number_format($transaction->total, 0, ',', '.') }}</strong></td></tr>
            </table>
            <hr>
            <p class="struk-center">Terima kasih</p>
        </div>
    @endif
</body>
</html>
