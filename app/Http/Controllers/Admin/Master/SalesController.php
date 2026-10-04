<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\SalesRequest;
use App\Models\Branch;
use App\Models\Sales;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\BranchContext;
use App\Support\ExcelTableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
 *
 * Tambah/edit/hapus memakai modal di halaman index, jadi tidak ada create()
 * dan edit(). Daftarkan route export SEBELUM resource:
 *
 *   Route::get('sales/export', [SalesController::class, 'export'])->name('sales.export');
 *   Route::resource('sales', SalesController::class)->except(['create', 'edit', 'show']);
 */
class SalesController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $items = BranchContext::current()->applyTo(
            Sales::query()->with(['branch', 'user'])
        )
            ->tap(fn (Builder $query) => $this->applySearch($query, $search))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Dipakai dropdown Branch di modal tambah/edit. Admin (bukan Super
        // Admin) hanya boleh lihat/pilih Branch-nya sendiri di form -
        // lihat juga pemaksaan branch_id di store()/update() di bawah.
        $branchs = BranchContext::current()->applyTo(Branch::query(), 'id')->orderBy('name')->get(['id', 'name']);

        return view('admin.master.sales.index', compact('items', 'search', 'branchs'));
    }

    /**
     * Laporan Sales: unduh file .xls (HTML table berformat, bukan .xlsx
     * biner -- lihat catatan lengkap di App\Support\ExcelTableExport)
     * mengikuti filter pencarian yang sedang aktif pada halaman index
     * (kalau ada). Password TIDAK pernah ikut diekspor.
     */
    public function export(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $items = BranchContext::current()->applyTo(
            Sales::query()->with(['branch', 'user'])
        )
            ->tap(fn (Builder $query) => $this->applySearch($query, $search))
            ->orderByDesc('id')
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
            subtitle: ($search !== '' ? "Filter pencarian: \"{$search}\" | " : '').'Diunduh: '.now()->format('d M Y H:i').' | Total: '.$items->count().' sales',
            columns: ['Kode', 'Nama', 'Telepon', 'Branch', 'Email Login', 'Status'],
            rows: $rows,
            textColumns: [0, 2], // Kode, Telepon - jaga angka 0 di depan
            filenameBase: 'laporan-sales',
        );
    }

    public function store(SalesRequest $request)
    {
        [$data, $email, $password] = $this->payload($request);

        // Anti-IDOR (Multi Branch/Depo): branch_id TIDAK PERNAH dipercaya
        // mentah dari form untuk Admin biasa - dipaksa ke Branch
        // miliknya sendiri. Hanya Super Admin yang boleh memilih Branch
        // bebas (termasuk membuat Sales untuk Branch mana pun).
        $branchContext = BranchContext::current();
        if (! $branchContext->isAll()) {
            $data['branch_id'] = $branchContext->branchId();
        } elseif (empty($data['branch_id'])) {
            throw ValidationException::withMessages(['branch_id' => 'Branch wajib dipilih.']);
        }

        // Akun baru butuh email DAN password; email saja akan gagal di database.
        if ($email && ! $password) {
            throw ValidationException::withMessages(['password' => 'Password wajib diisi untuk membuat akun login.']);
        }

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

    public function update(SalesRequest $request, Sales $item)
    {
        $branchContext = BranchContext::current();

        // Anti-IDOR (Multi Branch/Depo Scenario A/B): Admin Branch A tidak
        // boleh mengubah Sales Branch B hanya karena tahu ID-nya lewat URL
        // /admin/master/sales/{id} - route model binding sudah me-resolve
        // $item SEBELUM middleware authorize custom mana pun sempat jalan,
        // jadi dicek eksplisit di sini.
        if (! $branchContext->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Sales ini.');
        }

        $before = $item->toArray();

        [$data, $email, $password] = $this->payload($request);

        // Admin biasa tidak boleh MEMINDAHKAN Sales ke Branch lain lewat
        // payload - branch_id dipaksa tetap ke Branch Admin tsb (yang,
        // berkat pengecekan allows() di atas, sudah pasti sama dengan
        // $item->branch_id semula).
        if (! $branchContext->isAll()) {
            $data['branch_id'] = $branchContext->branchId();
        }

        // Belum punya akun: email dan password harus diisi bersamaan.
        if (! $item->user && ($email xor $password)) {
            throw ValidationException::withMessages([
                $email ? 'password' : 'email' => 'Email dan password harus diisi bersamaan untuk membuat akun login.',
            ]);
        }

        DB::transaction(function () use ($item, $data, $email, $password) {
            $item->update($data);

            if ($item->user) {
                // Sudah punya akun login sebelumnya: update kalau diisi,
                // biarkan tidak berubah kalau field dikosongkan. Nama akun
                // ikut nama sales supaya tidak berbeda.
                $updates = array_filter([
                    'name' => $item->name,
                    'email' => $email,
                    'password' => $password,
                ], fn ($v) => ! empty($v));

                $item->user->update($updates);
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
        if (! BranchContext::current()->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Sales ini.');
        }

        $before = $item->toArray();

        try {
            $item->delete();
        } catch (QueryException $e) {
            // Masih punya customer, visit, transaksi, dll.
            return redirect()->route('admin.master.sales.index')
                ->withErrors(['delete' => 'Sales tidak bisa dihapus karena masih dipakai data lain.']);
        }

        AuditLogger::log('delete', 'Master Data', Sales::class, $item->id, $before, null);

        return redirect()->route('admin.master.sales.index')->with('status', 'Sales berhasil dihapus. Catatan: akun login (User) TIDAK ikut terhapus otomatis -- kelola manual lewat menu System > Users bila perlu.');
    }

    /**
     * Pencarian kode / nama. Kondisi OR dibungkus closure supaya tidak
     * menembus kondisi lain pada query yang sama.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($search) {
            $w->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }));
    }

    /**
     * Pisahkan data Sales dari field akun login. is_active dipaksa boolean
     * (checkbox kosong tidak dikirim browser).
     *
     * @return array{0: array, 1: ?string, 2: ?string} [data sales, email, password]
     */
    private function payload(SalesRequest $request): array
    {
        $data = array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        unset($data['email'], $data['password']);

        return [$data, $email, $password];
    }
}