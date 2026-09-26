<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\SalesRequest;
use App\Models\Sales;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ExcelTableExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 - Master Data: Sales (Blueprint #32, #42 Definition of Done).
 *
 * Manajemen Akun Sales (Email & Password): setiap Sales BOLEH punya akun
 * login (tabel `users`, guard `web` yang sama dengan Admin) lewat relasi
 * `sales.user_id`. Field email/password di sini murni mengelola akun
 * User terkait -- TIDAK ada guard/tabel terpisah, TIDAK menyentuh
 * SalesDevice/SalesTrackingSession/native-token (App\Http\Controllers\
 * Sales\NativeTokenController) atau apa pun di sisi Sales App/mobile;
 * ini hanya menambah/mengganti email & password akun yang sudah dipakai
 * Sales App untuk login sejak awal.
 */
class SalesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Sales::query()
            ->with(['branch', 'user'])
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.sales.index', compact('items', 'search'));
    }

    /**
     * Laporan Sales: unduh file .xls (HTML table berformat, bukan .xlsx
     * biner -- lihat catatan lengkap di App\Support\ExcelTableExport)
     * mengikuti filter pencarian yang sedang aktif pada halaman index
     * (kalau ada). Password TIDAK pernah ikut diekspor.
     */
    public function export(Request $request): Response
    {
        $search = $request->query('q');

        $items = Sales::query()
            ->with(['branch', 'user'])
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->get();

        $rows = $items->map(fn ($s) => [
            $s->code,
            $s->name,
            $s->phone,
            $s->branch->name ?? '-',
            $s->user->email ?? '(belum ada akun login)',
            $s->is_active ? 'Aktif' : 'Nonaktif',
        ]);

        return ExcelTableExport::download(
            title: 'Laporan Data Sales',
            subtitle: ($search ? "Filter pencarian: \"{$search}\" | " : '').'Diunduh: '.now()->format('d M Y H:i').' | Total: '.$items->count().' sales',
            columns: ['Kode', 'Nama', 'Telepon', 'Branch', 'Email Login', 'Status'],
            rows: $rows,
            textColumns: [0, 2], // Kode, Telepon - jaga angka 0 di depan
            filenameBase: 'laporan-sales',
        );
    }

    public function create()
    {
        $branchs = \App\Models\Branch::orderBy('name')->get();

        return view('admin.master.sales.create', compact('branchs'));
    }

    public function store(SalesRequest $request)
    {
        $data = $request->validated();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        unset($data['email'], $data['password']);

        $item = DB::transaction(function () use ($data, $email, $password) {
            $sales = Sales::create($data);

            if ($email) {
                $user = User::create([
                    'name' => $sales->name,
                    'email' => $email,
                    'password' => $password,
                ]);
                $user->assignRole('sales');
                $sales->update(['user_id' => $user->id]);
            }

            return $sales;
        });

        AuditLogger::log('create', 'Master Data', Sales::class, $item->id, null, $item->fresh()->toArray());

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil ditambahkan.'.($email ? ' Akun login sudah dibuat.' : ''));
    }

    public function edit(Sales $item)
    {
        $branchs = \App\Models\Branch::orderBy('name')->get();
        $item->load('user');

        return view('admin.master.sales.edit', compact('item', 'branchs'));
    }

    public function update(SalesRequest $request, Sales $item)
    {
        $before = $item->toArray();

        $data = $request->validated();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        unset($data['email'], $data['password']);

        DB::transaction(function () use ($item, $data, $email, $password) {
            $item->update($data);

            if ($item->user) {
                // Sudah punya akun login sebelumnya: update kalau diisi,
                // biarkan tidak berubah kalau field dikosongkan.
                $updates = array_filter([
                    'email' => $email,
                    'password' => $password,
                ], fn ($v) => ! empty($v));

                if (! empty($updates)) {
                    $item->user->update($updates);
                }
            } elseif ($email && $password) {
                // Belum punya akun login, Admin baru mengisinya sekarang.
                $user = User::create([
                    'name' => $item->name,
                    'email' => $email,
                    'password' => $password,
                ]);
                $user->assignRole('sales');
                $item->update(['user_id' => $user->id]);
            }
        });

        AuditLogger::log('update', 'Master Data', Sales::class, $item->id, $before, $item->fresh()->toArray());

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil diperbarui.');
    }

    public function destroy(Sales $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Sales::class, $item->id, $before, null);

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil dihapus. Catatan: akun login (User) TIDAK ikut terhapus otomatis -- kelola manual lewat menu System > Users bila perlu.');
    }
}
