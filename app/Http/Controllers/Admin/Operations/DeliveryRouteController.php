<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Operations\DeliveryRouteRequest;
use App\Models\Customer;
use App\Models\DeliveryRoute;
use App\Models\RouteCustomer;
use App\Models\Sales;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 8 - Route (Blueprint #38, #47).
 *
 * Modul Manajemen Rute (Route Management): master rute distribusi
 * (Kode, Nama, Jenis Rute, Salesman, Area, Keterangan) beserta detail
 * pelanggan yang tergabung di dalamnya (pola hari kunjungan Senin..Minggu
 * dan minggu kunjungan W1..W4). Lihat App\Models\RouteCustomer untuk
 * catatan kenapa ini entity yang berbeda dari Rute Kanvas mobile
 * (SalesVisitPlan) dan GPS tracking TomTom (SalesRoute/RouteStop) --
 * modul ini murni untuk kebutuhan Admin, tidak disentuh oleh Sales App.
 */
class DeliveryRouteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = DeliveryRoute::query()
            ->with('sales')
            ->withCount('routeCustomers')
            ->when($search, fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.routes.index', compact('items', 'search'));
    }

    public function create()
    {
        $salesList = Sales::where('is_active', true)->orderBy('name')->get();
        $routeTypes = DeliveryRoute::ROUTE_TYPES;

        return view('admin.operations.routes.create', compact('salesList', 'routeTypes'));
    }

    public function store(DeliveryRouteRequest $request)
    {
        $item = DeliveryRoute::create($request->validated());

        AuditLogger::log('create', 'Operations', DeliveryRoute::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.operations.routes.edit', $item)->with('status', 'Route berhasil ditambahkan. Silakan lengkapi data pelanggan pada rute ini.');
    }

    public function edit(Request $request, DeliveryRoute $item)
    {
        $item->load('sales');

        $salesList = Sales::where('is_active', true)->orderBy('name')->get();
        $routeTypes = DeliveryRoute::ROUTE_TYPES;

        $routeCustomers = RouteCustomer::query()
            ->with('customer')
            ->where('route_id', $item->id)
            ->whereHas('customer')
            ->get()
            ->sortBy(fn ($rc) => $rc->customer->name);

        // Pelanggan aktif yang belum tergabung ke rute ini, untuk dropdown
        // "Tambah Pelanggan". Dibatasi 500 teratas supaya dropdown tetap
        // ringan -- Admin bisa cari lewat input search bawaan <select> besar
        // atau memakai Upload Data Rute untuk penambahan massal.
        $alreadyAddedIds = $routeCustomers->pluck('customer_id')->all();
        $availableCustomers = Customer::where('is_active', true)
            ->whereNotIn('id', $alreadyAddedIds)
            ->orderBy('name')
            ->limit(500)
            ->get();

        return view('admin.operations.routes.edit', compact(
            'item', 'salesList', 'routeTypes', 'routeCustomers', 'availableCustomers'
        ));
    }

    public function update(DeliveryRouteRequest $request, DeliveryRoute $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Operations', DeliveryRoute::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.operations.routes.edit', $item)->with('status', 'Route berhasil diperbarui.');
    }

    public function destroy(DeliveryRoute $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Operations', DeliveryRoute::class, $item->id, $before, null);

        return redirect()->route('admin.operations.routes.index')->with('status', 'Route berhasil dihapus.');
    }

    /**
     * Tambah satu pelanggan ke rute, lengkap pola hari/minggu kunjungan.
     */
    public function storeCustomer(Request $request, DeliveryRoute $item)
    {
        $data = $request->validate($this->customerScheduleRules() + [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $exists = RouteCustomer::where('route_id', $item->id)->where('customer_id', $data['customer_id'])->exists();
        if ($exists) {
            return back()->with('error', 'Pelanggan tersebut sudah tergabung pada rute ini.');
        }

        $routeCustomer = RouteCustomer::create($this->fillScheduleDefaults($data) + [
            'route_id' => $item->id,
            'customer_id' => $data['customer_id'],
        ]);

        AuditLogger::log('create', 'Operations', RouteCustomer::class, $routeCustomer->id, null, $routeCustomer->toArray());

        return back()->with('status', 'Pelanggan berhasil ditambahkan ke rute.');
    }

    /**
     * Perbarui pola hari/minggu kunjungan satu baris pelanggan pada rute.
     */
    public function updateCustomer(Request $request, DeliveryRoute $item, RouteCustomer $routeCustomer)
    {
        abort_unless($routeCustomer->route_id === $item->id, 404);

        $data = $request->validate($this->customerScheduleRules());

        $before = $routeCustomer->toArray();
        $routeCustomer->update($this->fillScheduleDefaults($data));

        AuditLogger::log('update', 'Operations', RouteCustomer::class, $routeCustomer->id, $before, $routeCustomer->toArray());

        return back()->with('status', 'Jadwal kunjungan pelanggan berhasil diperbarui.');
    }

    /**
     * Keluarkan satu pelanggan dari rute (tidak menghapus data Customer).
     */
    public function destroyCustomer(DeliveryRoute $item, RouteCustomer $routeCustomer)
    {
        abort_unless($routeCustomer->route_id === $item->id, 404);

        $before = $routeCustomer->toArray();
        $routeCustomer->delete();

        AuditLogger::log('delete', 'Operations', RouteCustomer::class, $routeCustomer->id, $before, null);

        return back()->with('status', 'Pelanggan berhasil dikeluarkan dari rute.');
    }

    /**
     * Download Data Rute: export CSV berisi seluruh pelanggan pada rute ini
     * beserta pola hari/minggu kunjungannya. File hasil download ini juga
     * bisa dipakai ulang sebagai template untuk Upload Data Rute.
     */
    public function exportCustomers(DeliveryRoute $item): StreamedResponse
    {
        $routeCustomers = RouteCustomer::with('customer')
            ->where('route_id', $item->id)
            ->whereHas('customer')
            ->get()
            ->sortBy(fn ($rc) => $rc->customer->name);

        $filename = 'rute-'.$item->code.'-pelanggan-'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $columns = array_merge(
            ['kode_pelanggan', 'nama_pelanggan'],
            array_keys(RouteCustomer::DAY_COLUMNS),
            array_keys(RouteCustomer::WEEK_COLUMNS)
        );
        $headerLabels = array_merge(
            ['Kode Pelanggan', 'Nama Pelanggan'],
            array_values(RouteCustomer::DAY_COLUMNS),
            array_values(RouteCustomer::WEEK_COLUMNS)
        );

        $callback = function () use ($routeCustomers, $headerLabels, $columns) {
            $out = fopen('php://output', 'w');
            // BOM supaya Excel membaca UTF-8 dengan benar (nama toko sering ada karakter non-ASCII).
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headerLabels);

            foreach ($routeCustomers as $rc) {
                $row = [$rc->customer->code, $rc->customer->name];
                foreach (array_slice($columns, 2) as $col) {
                    $row[] = $rc->{$col} ? 1 : 0;
                }
                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Upload Data Rute: import CSV (format sama seperti hasil Download Data
     * Rute) untuk menambah/memperbarui pelanggan pada rute ini secara
     * massal. Pelanggan dicocokkan berdasarkan Kode Pelanggan; baris dengan
     * kode yang tidak ditemukan akan dilewati dan dilaporkan.
     */
    public function importCustomers(Request $request, DeliveryRoute $item)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'File tidak dapat dibaca.');
        }

        // Buang BOM UTF-8 kalau ada, supaya header kolom pertama tidak ikut ternoda.
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong atau format tidak valid.');
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $codeIdx = array_search('kode_pelanggan', $header, true);
        if ($codeIdx === false) {
            fclose($handle);
            return back()->with('error', 'Kolom "kode_pelanggan" tidak ditemukan pada baris header CSV.');
        }

        $dayKeys = array_keys(RouteCustomer::DAY_COLUMNS);
        $weekKeys = array_keys(RouteCustomer::WEEK_COLUMNS);
        $scheduleKeys = array_merge($dayKeys, $weekKeys);
        $scheduleIdx = [];
        foreach ($scheduleKeys as $key) {
            $idx = array_search($key, $header, true);
            if ($idx !== false) {
                $scheduleIdx[$key] = $idx;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = [];
        $rowNum = 1;

        DB::transaction(function () use ($handle, $item, $codeIdx, $scheduleIdx, &$created, &$updated, &$skipped, &$rowNum) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue; // baris kosong, lewati
                }

                $code = trim((string) ($row[$codeIdx] ?? ''));
                if ($code === '') {
                    $skipped[] = "Baris {$rowNum}: kode pelanggan kosong.";
                    continue;
                }

                $customer = Customer::where('code', $code)->first();
                if (! $customer) {
                    $skipped[] = "Baris {$rowNum}: kode pelanggan \"{$code}\" tidak ditemukan.";
                    continue;
                }

                $scheduleData = [];
                foreach ($scheduleIdx as $key => $idx) {
                    $raw = strtolower(trim((string) ($row[$idx] ?? '')));
                    $scheduleData[$key] = in_array($raw, ['1', 'ya', 'yes', 'y', 'true'], true);
                }

                $routeCustomer = RouteCustomer::where('route_id', $item->id)
                    ->where('customer_id', $customer->id)
                    ->first();

                if ($routeCustomer) {
                    $routeCustomer->update($scheduleData);
                    $updated++;
                } else {
                    RouteCustomer::create($scheduleData + [
                        'route_id' => $item->id,
                        'customer_id' => $customer->id,
                    ]);
                    $created++;
                }
            }
        });

        fclose($handle);

        AuditLogger::log('import', 'Operations', RouteCustomer::class, $item->id, null, [
            'route_id' => $item->id, 'created' => $created, 'updated' => $updated, 'skipped' => count($skipped),
        ]);

        $message = "Upload selesai: {$created} pelanggan baru ditambahkan, {$updated} diperbarui.";
        if (! empty($skipped)) {
            $message .= ' '.count($skipped).' baris dilewati.';

            return back()->with('status', $message)->with('importWarnings', $skipped);
        }

        return back()->with('status', $message);
    }

    /**
     * Aturan validasi untuk 11 kolom checkbox pola kunjungan (hari + minggu).
     */
    private function customerScheduleRules(): array
    {
        $rules = [];
        foreach (array_merge(array_keys(RouteCustomer::DAY_COLUMNS), array_keys(RouteCustomer::WEEK_COLUMNS)) as $key) {
            $rules[$key] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * Isi default false untuk checkbox yang tidak dikirim form (checkbox
     * yang tidak dicentang memang tidak ikut terkirim di HTML).
     */
    private function fillScheduleDefaults(array $data): array
    {
        foreach (array_merge(array_keys(RouteCustomer::DAY_COLUMNS), array_keys(RouteCustomer::WEEK_COLUMNS)) as $key) {
            $data[$key] = (bool) ($data[$key] ?? false);
        }

        return $data;
    }
}
