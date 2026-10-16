<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 28px 40px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #111827; }
        h1 { font-size: 15px; margin: 0 0 3px 0; }
        .sub { font-size: 8px; color: #4b5563; margin: 0 0 12px 0; }
        h2 { font-size: 11px; margin: 14px 0 4px 0; padding: 3px 6px; background: #e5e7eb; border-left: 3px solid #374151; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #374151; color: #ffffff; font-weight: bold; padding: 4px 4px; border: 1px solid #d1d5db; text-align: center; }
        td { padding: 3px 4px; border: 1px solid #d1d5db; vertical-align: top; }
        tr.even td { background: #f3f4f6; }
        tr.total td { font-weight: bold; background: #e5e7eb; }
        td.num { text-align: right; white-space: nowrap; }
        .note { font-size: 7px; color: #6b7280; font-style: italic; margin: 2px 0 0 0; }
        .empty { padding: 8px; text-align: center; color: #6b7280; border: 1px solid #d1d5db; }
        .foot { margin-top: 14px; font-size: 7px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if ($subtitle)
        <p class="sub">{{ $subtitle }}</p>
    @endif

    @foreach ($sections as $section)
        <h2>{{ $section['title'] }}</h2>

        @if (count($section['rows']))
            <table>
                <thead>
                    <tr>
                        @foreach ($section['columns'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $i => $row)
                        @php $isTotal = in_array('TOTAL', array_slice($row, 0, 2), true); @endphp
                        <tr class="{{ $isTotal ? 'total' : ($i % 2 ? 'even' : '') }}">
                            @foreach ($row as $cell)
                                @if (is_int($cell))
                                    <td class="num">{{ number_format($cell, 0, ',', '.') }}</td>
                                @elseif (is_float($cell))
                                    <td class="num">{{ number_format($cell, 2, ',', '.') }}</td>
                                @else
                                    <td>{{ $cell }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Tidak ada data.</div>
        @endif

        @if (! empty($section['note']))
            <p class="note">{{ $section['note'] }}</p>
        @endif
    @endforeach

    <p class="foot">Dibuat {{ $generatedAt }}</p>
</body>
</html>
