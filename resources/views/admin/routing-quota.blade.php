<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuota Routing TomTom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 40px auto; padding: 0 16px; color: #222; }
        h1 { font-size: 20px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .bar-bg { background: #eee; border-radius: 6px; height: 20px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 6px 0 0 6px; transition: width .3s; }
        .bar-safe { background: #2e7d32; }
        .bar-warn { background: #f9a825; }
        .bar-danger { background: #c62828; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; font-size: 14px; }
        .muted { color: #777; font-size: 13px; }
    </style>
</head>
<body>
    <h1>Kuota Routing TomTom (Free-Only Mode)</h1>
    <p class="muted">
        Section 96-97 blueprint: sistem SENGAJA berhenti memanggil TomTom kalau
        pemakaian bulan ini melewati hard budget internal, dan otomatis jatuh ke
        mode <em>fallback</em> (urutan customer tanpa jarak/durasi presisi) supaya
        tidak pernah keluar dari free tier tanpa sengaja.
    </p>

    <div class="card">
        <strong>Periode: {{ $currentPeriod }}</strong>
        <div style="margin: 12px 0;">
            <div class="bar-bg">
                <div class="bar-fill {{ $percentage >= 100 ? 'bar-danger' : ($percentage >= 80 ? 'bar-warn' : 'bar-safe') }}"
                     style="width: {{ $percentage }}%"></div>
            </div>
        </div>
        <p>{{ number_format($currentCount) }} / {{ number_format($budget) }} request ({{ $percentage }}%)</p>
        @if($percentage >= 100)
            <p style="color:#c62828;"><strong>Budget bulan ini habis — sistem sedang berjalan di mode fallback.</strong></p>
        @elseif($percentage >= 80)
            <p style="color:#f9a825;"><strong>Mendekati batas bulan ini, pantau terus.</strong></p>
        @endif
    </div>

    <div class="card">
        <strong>Riwayat 12 bulan terakhir</strong>
        <table>
            <thead>
                <tr><th>Periode</th><th>Jumlah Request</th></tr>
            </thead>
            <tbody>
                @forelse($history as $row)
                    <tr>
                        <td>{{ $row->period_month }}</td>
                        <td>{{ number_format($row->request_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted">Belum ada data pemakaian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
