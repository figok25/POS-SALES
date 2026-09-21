<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Stock - {{ $salesTask->code }}</title>
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

        @page { size: A4; margin: 15mm; }
        .sheet { background: #fff; max-width: 190mm; margin: 16px auto; padding: 14mm; box-shadow: 0 0 6px rgba(0,0,0,.15); font-size: 13px; }
        @media print { .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; } }

        .a4-header { display: flex; justify-content: space-between; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
        .a4-header h2 { margin: 0 0 4px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; margin-bottom: 16px; }
        .info-grid dt { color: #555; display: inline; }
        .info-grid dd { display: inline; margin: 0; font-weight: 600; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 6px 8px; }
        table.items th { background: #f3f4f6; text-align: left; }
        .diff-row { color: #b91c1c; font-weight: 600; }

        .a4-signatures { display: flex; justify-content: space-between; margin-top: 60px; text-align: center; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="primary" onclick="window.print()">&#128424; Cetak</button>
        <a href="{{ route('admin.sales-tasks.show', $salesTask) }}">&larr; Kembali ke Sales Task</a>
    </div>

    <div class="sheet">
        <div class="a4-header">
            <div>
                <h2>{{ $company->name ?? config('app.name', 'POS & Sales') }}</h2>
                @if ($company?->address)<div>{{ $company->address }}</div>@endif
                @if ($company?->phone)<div>Telp: {{ $company->phone }}</div>@endif
            </div>
            <div style="text-align:right;">
                <h2>DAFTAR STOCK SALES</h2>
                <div>No. Task: {{ $salesTask->code }}</div>
                @if ($salesTask->bkbDistribusi)<div>Sumber BKB: {{ $salesTask->bkbDistribusi->code }}</div>@endif
            </div>
        </div>

        <div class="info-grid">
            <div><dt>Sales:</dt> <dd>{{ $salesTask->sales->name ?? '-' }}</dd></div>
            <div><dt>Branch:</dt> <dd>{{ $salesTask->branch->name ?? '-' }}</dd></div>
            <div><dt>Tanggal Tugas:</dt> <dd>{{ $salesTask->task_date?->format('d/m/Y') }}</dd></div>
            <div><dt>Status:</dt> <dd class="capitalize">{{ str_replace('_', ' ', $salesTask->status) }}</dd></div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>SKU</th>
                    <th style="text-align:right;">Qty Ditugaskan</th>
                    <th style="text-align:right;">Qty Diverifikasi</th>
                    <th style="text-align:right;">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salesTask->taskStocks as $i => $line)
                    <tr class="{{ $line->difference() != 0 ? 'diff-row' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $line->product->name ?? '-' }}</td>
                        <td>{{ $line->product->sku ?? '-' }}</td>
                        <td style="text-align:right;">{{ number_format($line->quantity_assigned, 2) }}</td>
                        <td style="text-align:right;">{{ $line->quantity_verified !== null ? number_format($line->quantity_verified, 2) : '-' }}</td>
                        <td style="text-align:right;">{{ $line->quantity_verified !== null ? number_format($line->difference(), 2) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($salesTask->notes)
            <p><strong>Catatan:</strong> {{ $salesTask->notes }}</p>
        @endif

        <div class="a4-signatures">
            <div>Yang Menyerahkan,<br><br><br><br>(....................)</div>
            <div>Yang Menerima (Sales),<br><br><br><br>({{ $salesTask->sales->name ?? '....................' }})</div>
        </div>
    </div>
</body>
</html>
