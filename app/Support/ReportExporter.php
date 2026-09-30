<?php

namespace App\Support;

use App\Exports\ArrayExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportExporter
{
    /** Returns a download response if ?export=xlsx|json is present, otherwise null. */
    public static function handle(Request $request, string $name, array $headings, iterable $rows)
    {
        $format = $request->query('export');
        if (! in_array($format, ['xlsx', 'json'], true)) {
            return null;
        }

        $rows = collect($rows)->map(fn ($r) => array_values($r))->all();
        $file = $name . '-' . now()->format('Ymd-His');

        if ($format === 'json') {
            $data = array_map(fn ($r) => array_combine($headings, $r), $rows);

            return response()->json($data, 200, [
                'Content-Disposition' => "attachment; filename=\"{$file}.json\"",
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return Excel::download(new ArrayExport($headings, $rows), $file . '.xlsx');
    }
}
