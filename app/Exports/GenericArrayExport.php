<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export .xlsx generik dipakai bersama oleh semua halaman Reports
 * (admin.reports.*) lewat ReportController::exportResponse(). Satu
 * class untuk kelima laporan -- cukup kirim heading kolom + baris data
 * (array polos, tanpa key), tidak perlu bikin class baru per laporan.
 */
class GenericArrayExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(protected array $rows, protected array $headings)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
