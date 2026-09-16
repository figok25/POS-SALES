<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Phase 8 - Reports Export (Blueprint #38, #47).
 * Wraps the same $recent delivery-order collection shown on
 * admin.reports.delivery so the exported file always matches what's on
 * screen (most recent 30 orders).
 */
class DeliveryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(protected Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Kode', 'Customer', 'Vehicle', 'Driver', 'Route', 'Status'];
    }

    public function map($do): array
    {
        return [
            $do->code,
            $do->salesTransaction->customer->name ?? '-',
            $do->vehicle->name ?? '-',
            $do->driver->name ?? '-',
            $do->route->name ?? '-',
            $do->status,
        ];
    }

    public function title(): string
    {
        return 'Delivery Report';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
