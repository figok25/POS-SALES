<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

@php
    /*
     * Template export Excel HTML (.xls).
     *
     * Penting:
     * XML Microsoft Office di bawah dibangun menggunakan chr(60)
     * dan chr(62), bukan menulis tag XML namespace secara langsung.
     *
     * Alasannya, Blade menganggap pola <x:...> sebagai Blade Component.
     */

    $excelSheetName = e(
        \Illuminate\Support\Str::limit($title, 28, '')
    );

    $freezeRow = $subtitle ? 3 : 2;

    /*
     * Karakter XML:
     * chr(60) = <
     * chr(62) = >
     *
     * Dengan cara ini Blade tidak akan mendeteksi tag XML
     * sebagai Blade Component.
     */

    $lt = chr(60);
    $gt = chr(62);

    $msoWorkbookXml =
        $lt . '!--[if gte mso 9]>' .
        $lt . 'xml' . $gt .

        $lt . 'x:ExcelWorkbook' . $gt .
            $lt . 'x:ExcelWorksheets' . $gt .
                $lt . 'x:ExcelWorksheet' . $gt .

                    $lt . 'x:Name' . $gt .
                        $excelSheetName .
                    $lt . '/x:Name' . $gt .

                    $lt . 'x:WorksheetOptions' . $gt .

                        $lt . 'x:DisplayGridlines/' . $gt .

                        $lt . 'x:FreezePanes/' . $gt .

                        $lt . 'x:FrozenNoSplit/' . $gt .

                        $lt . 'x:SplitHorizontal' . $gt .
                            '1' .
                        $lt . '/x:SplitHorizontal' . $gt .

                        $lt . 'x:TopRowBottomPane' . $gt .
                            $freezeRow .
                        $lt . '/x:TopRowBottomPane' . $gt .

                        $lt . 'x:ActivePane' . $gt .
                            '2' .
                        $lt . '/x:ActivePane' . $gt .

                    $lt . '/x:WorksheetOptions' . $gt .

                $lt . '/x:ExcelWorksheet' . $gt .
            $lt . '/x:ExcelWorksheets' . $gt .
        $lt . '/x:ExcelWorkbook' . $gt .

        $lt . '/xml' . $gt .
        $lt . '![endif]' . $gt;
@endphp

{!! $msoWorkbookXml !!}

<style>
    table {
        border-collapse: collapse;
        font-family: Calibri, Arial, sans-serif;
        font-size: 11pt;
    }

    .report-title {
        font-size: 14pt;
        font-weight: bold;
        color: #1F2937;
    }

    .report-subtitle {
        font-size: 9pt;
        color: #6B7280;
    }

    th {
        background: #4F46E5;
        color: #FFFFFF;
        font-weight: bold;
        border: 1px solid #3730A3;
        padding: 6px 8px;
        text-align: left;
        white-space: nowrap;
    }

    td {
        border: 1px solid #D1D5DB;
        padding: 5px 8px;
        vertical-align: top;
    }

    .row-even {
        background: #FFFFFF;
    }

    .row-odd {
        background: #F3F4F6;
    }

    .text-cell {
        mso-number-format: '\@';
    }
</style>

</head>

<body>

<table>

    {{-- Judul laporan --}}
    <tr>
        <td
            class="report-title"
            colspan="{{ count($columns) }}"
        >
            {{ $title }}
        </td>
    </tr>

    {{-- Subtitle --}}
    @if ($subtitle)
        <tr>
            <td
                class="report-subtitle"
                colspan="{{ count($columns) }}"
            >
                {{ $subtitle }}
            </td>
        </tr>
    @endif

    {{-- Header kolom --}}
    <tr>
        @foreach ($columns as $col)
            <th>
                {{ $col }}
            </th>
        @endforeach
    </tr>

    {{-- Data --}}
    @forelse ($rows as $i => $row)

        <tr>

            @foreach ($row as $colIndex => $value)

                <td
                    class="{{ $i % 2 === 0 ? 'row-even' : 'row-odd' }}
                    {{ in_array($colIndex, $textColumns ?? [], true) ? 'text-cell' : '' }}"
                >
                    {{ $value }}
                </td>

            @endforeach

        </tr>

    @empty

        <tr>
            <td
                colspan="{{ count($columns) }}"
                style="text-align:center; color:#9CA3AF; padding:12px;"
            >
                Tidak ada data.
            </td>
        </tr>

    @endforelse

</table>

</body>
</html>
