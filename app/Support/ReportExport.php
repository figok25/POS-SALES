<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export laporan ke Excel (.xlsx) atau PDF dari struktur data yang SAMA.
 *
 * Satu laporan = beberapa "section" (tabel). Excel: satu sheet per section.
 * PDF: satu dokumen, tiap section satu tabel berjudul.
 *
 *   $sections = [
 *       ['title' => 'Kunjungan', 'columns' => ['A', 'B'], 'rows' => [[1, 'x'], ...], 'note' => null],
 *   ];
 *
 * Nilai angka (int/float) disimpan sebagai angka di Excel & rata kanan di PDF;
 * semua string ditulis sebagai teks (aman dari rumus/formula injection dan
 * menjaga angka 0 di depan, mis. kode atau telepon).
 */
class ReportExport
{
    /** Batas baris per section di PDF agar memori/waktu render terjaga (Excel tidak dibatasi). */
    public const PDF_MAX_ROWS = 3000;

    public static function normalizeFormat(?string $format): string
    {
        return strtolower((string) $format) === 'pdf' ? 'pdf' : 'xlsx';
    }

    /**
     * @param  array<int, array{title: string, columns: array<int, string>, rows: iterable, note?: ?string}>  $sections
     */
    public static function download(string $format, string $title, ?string $subtitle, array $sections, string $filenameBase, bool $landscape = true): Response
    {
        return self::normalizeFormat($format) === 'pdf'
            ? self::pdf($title, $subtitle, $sections, $filenameBase, $landscape)
            : self::xlsx($title, $subtitle, $sections, $filenameBase);
    }

    public static function xlsx(string $title, ?string $subtitle, array $sections, string $filenameBase): Response
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $usedNames = [];

        foreach (array_values($sections) as $index => $section) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(self::sheetName($section['title'] ?? 'Laporan', $index, $usedNames));

            $columns = array_values($section['columns'] ?? []);
            $colCount = max(1, count($columns));
            $lastCol = Coordinate::stringFromColumnIndex($colCount);

            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValueExplicit('A1', $title.' - '.($section['title'] ?? ''), DataType::TYPE_STRING);
            $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);

            $sheet->mergeCells("A2:{$lastCol}2");
            $sheet->setCellValueExplicit('A2', (string) $subtitle, DataType::TYPE_STRING);
            $sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '6B7280']]]);

            $headerRow = 4;
            foreach ($columns as $i => $label) {
                $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1).$headerRow, (string) $label, DataType::TYPE_STRING);
            }
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '374151']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);

            $row = $headerRow + 1;
            $hasRows = false;

            foreach ($section['rows'] ?? [] as $values) {
                $hasRows = true;
                foreach (array_values((array) $values) as $i => $value) {
                    $coord = Coordinate::stringFromColumnIndex($i + 1).$row;

                    if (is_int($value) || is_float($value)) {
                        $sheet->setCellValueExplicit($coord, $value, DataType::TYPE_NUMERIC);
                        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(is_int($value) ? '#,##0' : '#,##0.00');
                    } else {
                        $sheet->setCellValueExplicit($coord, (string) ($value ?? ''), DataType::TYPE_STRING);
                    }
                }

                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
                }
                $row++;
            }

            if (! $hasRows) {
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->setCellValueExplicit("A{$row}", 'Tidak ada data.', DataType::TYPE_STRING);
                $row++;
            } else {
                $sheet->getStyle("A{$headerRow}:{$lastCol}".($row - 1))->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                $sheet->setAutoFilter("A{$headerRow}:{$lastCol}".($row - 1));
            }

            if (! empty($section['note'])) {
                $row++;
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->setCellValueExplicit("A{$row}", (string) $section['note'], DataType::TYPE_STRING);
                $sheet->getStyle("A{$row}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '6B7280']]]);
            }

            for ($i = 1; $i <= $colCount; $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }

            $sheet->freezePane('A'.($headerRow + 1));
            $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getSheetView()->setZoomScale(90);
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $spreadsheet->createSheet()->setTitle('Laporan');
        }
        $spreadsheet->setActiveSheetIndex(0);

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $content = ob_get_clean();

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.self::filename($filenameBase, 'xlsx').'"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }

    public static function pdf(string $title, ?string $subtitle, array $sections, string $filenameBase, bool $landscape = true): Response
    {
        // Render PDF memakan memori; naikkan batas hanya untuk request ini.
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $prepared = [];
        foreach (array_values($sections) as $section) {
            $rows = [];
            $truncated = false;

            foreach ($section['rows'] ?? [] as $values) {
                if (count($rows) >= self::PDF_MAX_ROWS) {
                    $truncated = true;
                    break;
                }
                $rows[] = array_values((array) $values);
            }

            $note = $section['note'] ?? null;
            if ($truncated) {
                $note = trim(($note ? $note.' ' : '').'PDF hanya memuat '.number_format(self::PDF_MAX_ROWS, 0, ',', '.').' baris pertama. Gunakan Excel untuk seluruh data.');
            }

            $prepared[] = [
                'title' => (string) ($section['title'] ?? ''),
                'columns' => array_values($section['columns'] ?? []),
                'rows' => $rows,
                'note' => $note,
            ];
        }

        $html = view('exports.report-pdf', [
            'title' => $title,
            'subtitle' => $subtitle,
            'sections' => $prepared,
            'generatedAt' => now()->format('d M Y H:i'),
        ])->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $landscape ? 'landscape' : 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 90, $canvas->get_height() - 24, 'Hal. {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.self::filename($filenameBase, 'pdf').'"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }

    private static function filename(string $base, string $ext): string
    {
        return $base.'-'.now()->format('Ymd_His').'.'.$ext;
    }

    /** Nama sheet Excel: tanpa karakter terlarang, maks 31 karakter, unik. */
    private static function sheetName(string $title, int $index, array &$used): string
    {
        $name = trim(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $title));
        $name = mb_substr($name !== '' ? $name : 'Laporan', 0, 31);

        $candidate = $name;
        $n = 2;
        while (in_array(mb_strtolower($candidate), $used, true)) {
            $suffix = ' '.$n++;
            $candidate = mb_substr($name, 0, 31 - mb_strlen($suffix)).$suffix;
        }
        $used[] = mb_strtolower($candidate);

        return $candidate;
    }
}
