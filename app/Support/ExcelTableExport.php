<?php

namespace App\Support;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelTableExport
{
    /**
     * Export data menjadi file Excel .xlsx asli.
     *
     * @param string $title
     * @param string|null $subtitle
     * @param array<int, string> $columns
     * @param iterable<int, array<int, mixed>> $rows
     * @param array<int, int> $textColumns
     * @param string $filenameBase
     */
    public static function download(
        string $title,
        ?string $subtitle,
        array $columns,
        iterable $rows,
        array $textColumns,
        string $filenameBase,
    ): Response {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        /*
         * ==============================
         * NAMA SHEET
         * ==============================
         *
         * Excel membatasi nama worksheet
         * maksimal 31 karakter.
         */

        $sheet->setTitle(
            substr(
                preg_replace('/[\\\\\/\?\*\[\]:]/', '', $title),
                0,
                31
            ) ?: 'Laporan'
        );

        $columnCount = count($columns);

        if ($columnCount === 0) {
            $columnCount = 1;
        }

        /*
         * ==============================
         * JUDUL
         * ==============================
         */

        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        $sheet->mergeCells("A1:{$lastColumn}1");

        $sheet->setCellValue('A1', $title);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);

        /*
         * ==============================
         * SUBTITLE
         * ==============================
         */

        $headerRow = 2;

        if ($subtitle !== null && $subtitle !== '') {
            $sheet->mergeCells("A2:{$lastColumn}2");

            $sheet->setCellValue('A2', $subtitle);

            $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                'font' => [
                    'size' => 9,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getRowDimension(2)->setRowHeight(18);

            $headerRow = 3;
        }

        /*
         * ==============================
         * HEADER
         * ==============================
         */

        foreach ($columns as $index => $column) {
            $columnLetter = Coordinate::stringFromColumnIndex(
                $index + 1
            );

            $sheet->setCellValue(
                "{$columnLetter}{$headerRow}",
                $column
            );
        }

        $headerRange = "A{$headerRow}:{$lastColumn}{$headerRow}";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '4F46E5',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '3730A3',
                    ],
                ],
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        /*
         * ==============================
         * DATA
         * ==============================
         */

        $currentRow = $headerRow + 1;

        foreach ($rows as $rowIndex => $row) {

            $isEven = $rowIndex % 2 === 0;

            foreach ($columns as $colIndex => $column) {

                $columnLetter = Coordinate::stringFromColumnIndex(
                    $colIndex + 1
                );

                $cellAddress = "{$columnLetter}{$currentRow}";

                $value = $row[$colIndex] ?? '';

                /*
                 * Kolom tertentu dipaksa menjadi TEXT.
                 *
                 * Cocok untuk:
                 * - nomor HP
                 * - kode customer
                 * - NPWP
                 * - kode produk
                 * - nomor rekening
                 * dll.
                 */

                if (in_array($colIndex, $textColumns, true)) {

                    $sheet->setCellValueExplicit(
                        $cellAddress,
                        (string) $value,
                        DataType::TYPE_STRING
                    );

                } else {

                    $sheet->setCellValue(
                        $cellAddress,
                        $value
                    );
                }
            }

            /*
             * ==============================
             * STYLE BARIS
             * ==============================
             */

            $rowRange = "A{$currentRow}:{$lastColumn}{$currentRow}";

            $sheet->getStyle($rowRange)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => $isEven
                            ? 'FFFFFF'
                            : 'F3F4F6',
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'D1D5DB',
                        ],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ]);

            $currentRow++;
        }

        /*
         * ==============================
         * BORDER JUDUL + SUBTITLE
         * ==============================
         */

        $sheet->getStyle("A1:{$lastColumn}{$currentRow}")->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        /*
         * ==============================
         * AUTO WIDTH
         * ==============================
         */

        for ($i = 1; $i <= $columnCount; $i++) {

            $columnLetter = Coordinate::stringFromColumnIndex($i);

            $sheet
                ->getColumnDimension($columnLetter)
                ->setAutoSize(true);
        }

        /*
         * ==============================
         * BATAS AUTO WIDTH
         * ==============================
         *
         * Supaya kolom dengan teks sangat
         * panjang tidak menjadi terlalu lebar.
         */

        for ($i = 1; $i <= $columnCount; $i++) {

            $columnLetter = Coordinate::stringFromColumnIndex($i);

            $dimension = $sheet->getColumnDimension($columnLetter);

            if ($dimension->getWidth() > 45) {
                $dimension->setWidth(45);
            }
        }

        /*
         * ==============================
         * FREEZE HEADER
         * ==============================
         */

        $sheet->freezePane(
            "A" . ($headerRow + 1)
        );

        /*
         * ==============================
         * FILTER HEADER
         * ==============================
         */

        $sheet->setAutoFilter($headerRange);

        /*
         * ==============================
         * PAGE / VIEW
         * ==============================
         */

        $sheet->getSheetView()->setZoomScale(90);

        /*
         * ==============================
         * NAMA FILE
         * ==============================
         */

        $filename =
            $filenameBase .
            '-' .
            now()->format('Ymd_His') .
            '.xlsx';

        /*
         * ==============================
         * GENERATE EXCEL ASLI
         * ==============================
         */

        $writer = new Xlsx($spreadsheet);

        ob_start();

        $writer->save('php://output');

        $content = ob_get_clean();

        /*
         * Bersihkan object spreadsheet
         */

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        /*
         * ==============================
         * RESPONSE DOWNLOAD
         * ==============================
         */

        return response($content, 200, [
            'Content-Type' =>
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

            'Content-Disposition' =>
                'attachment; filename="' . $filename . '"',

            'Cache-Control' =>
                'max-age=0',

            'Pragma' =>
                'public',
        ]);
    }
}
