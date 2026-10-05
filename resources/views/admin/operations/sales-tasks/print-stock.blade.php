@php
    $statusLabel = ucwords(str_replace('_', ' ', $salesTask->status));
    $statusTone = match ($salesTask->status) {
        'completed' => 'is-completed',
        'cancelled', 'stock_variance' => 'is-cancelled',
        default => 'is-partial',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Stock - {{ $salesTask->code }}</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>
<body class="format-a4">
    <div class="toolbar no-print">
        <button type="button" class="toolbar-btn is-primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            Cetak
        </button>
        <a href="{{ route('admin.sales-tasks.show', $salesTask) }}" class="toolbar-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Kembali ke Sales Task
        </a>
    </div>

    <div class="sheet">
        <div class="a4-header">
            <div class="a4-company">
                <h2>{{ $company->name ?? config('app.name', 'POS & Sales') }}</h2>
                @if ($company?->address)<div>{{ $company->address }}</div>@endif
                @if ($company?->phone)<div>Telp: {{ $company->phone }}</div>@endif
            </div>
            <div class="r">
                <h2>DAFTAR STOCK SALES</h2>
                <div>No. Task: {{ $salesTask->code }}</div>
                @if ($salesTask->bkbDistribusi)<div>Sumber BKB: {{ $salesTask->bkbDistribusi->code }}</div>@endif
            </div>
        </div>

        <div class="a4-parties">
            <div>
                <span class="a4-label">Sales</span>
                <span class="b">{{ $salesTask->sales->name ?? '-' }}</span>
                <span class="a4-label is-spaced">Branch</span>
                <span class="b">{{ $salesTask->branch->name ?? '-' }}</span>
            </div>
            <div class="r">
                <span class="a4-label">Tanggal Tugas</span>
                <span class="b">{{ $salesTask->task_date?->format('d/m/Y') ?? '-' }}</span>
                <span class="a4-label is-spaced">Status</span>
                <span class="a4-status {{ $statusTone }}">{{ $statusLabel }}</span>
            </div>
        </div>

        <table class="a4-items">
            <thead>
                <tr>
                    <th class="c">No</th>
                    <th>Produk</th>
                    <th>SKU</th>
                    <th class="r">Qty Ditugaskan</th>
                    <th class="r">Qty Diverifikasi</th>
                    <th class="r">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salesTask->taskStocks as $i => $line)
                    <tr @class(['is-diff' => $line->difference() != 0])>
                        <td class="c">{{ $i + 1 }}</td>
                        <td>{{ $line->product->name ?? '-' }}</td>
                        <td>{{ $line->product->sku ?? '-' }}</td>
                        <td class="r">{{ number_format($line->quantity_assigned, 2) }}</td>
                        <td class="r">{{ $line->quantity_verified !== null ? number_format($line->quantity_verified, 2) : '-' }}</td>
                        <td class="r">{{ $line->quantity_verified !== null ? number_format($line->difference(), 2) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($salesTask->notes)
            <p class="a4-notes"><strong>Catatan:</strong> {{ $salesTask->notes }}</p>
        @endif

        <div class="a4-signatures">
            <div>Yang Menyerahkan,<br><br><br><br>(....................)</div>
            <div>Yang Menerima (Sales),<br><br><br><br>({{ $salesTask->sales->name ?? '....................' }})</div>
        </div>
    </div>
</body>
</html>