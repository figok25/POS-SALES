<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\CustomerRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sales;
use App\Services\AuditLogger;
use App\Services\CustomerAssignmentService;
use App\Support\BranchContext;
use App\Support\ExcelTableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 2 - Master Data: Customer (Blueprint #32, #42 Definition of Done).
 * Phase 6 Hardening - perubahan sales_id (Customer Assignment, Blueprint
 * #729) selalu lewat CustomerAssignmentService agar riwayat & audit
 * tercatat konsisten, tidak lagi mass-update kolom sales_id langsung.
 *
 * Tambah/edit/hapus memakai modal di halaman index, jadi tidak ada create()
 * dan edit(). show() tetap ada (Customer Detail). Daftarkan route export
 * SEBELUM resource supaya "export" tidak dianggap sebagai {item}:
 *
 *   Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
 *   Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
 */
class CustomerController extends Controller
{
    public function __construct(protected CustomerAssignmentService $assignmentService)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $items = BranchContext::current()->applyTo(
            Customer::query()->with('sales')
        )
            ->tap(fn (Builder $query) => $this->applySearch($query, $search))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Dipakai dropdown Sales di modal tambah/edit - Admin hanya boleh
        // menugaskan Customer ke Sales di Branch-nya sendiri (lihat juga
        // CustomerRequest::withValidator untuk validasi server-side-nya).
        $saless = BranchContext::current()->applyTo(Sales::query())->orderBy('name')->get(['id', 'name', 'branch_id']);

        // Dropdown Branch di modal: hanya dirender untuk Super Admin dalam
        // mode "Semua Depo". Admin biasa selalu terkunci ke Branch akunnya.
        $branchs = BranchContext::current()->applyTo(Branch::where('is_active', true), 'id')->orderBy('name')->get(['id', 'name']);

        return view('admin.master.customers.index', compact('items', 'search', 'saless', 'branchs'));
    }

    /**
     * Laporan Data Customer: unduh file .xls (HTML table berformat, bukan
     * .xlsx biner -- lihat catatan lengkap di App\Support\ExcelTableExport)
     * mengikuti filter pencarian yang sedang aktif pada halaman index (kalau ada).
     */
    public function export(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $items = BranchContext::current()->applyTo(
            Customer::query()->with(['sales', 'branch'])
        )
            ->tap(fn (Builder $query) => $this->applySearch($query, $search))
            ->orderByDesc('id')
            ->get();

        $rows = $items->map(fn ($c) => [
            $c->code,
            $c->name,
            $c->address,
            $c->phone,
            $c->npwp,
            $c->branch->name ?? '-',
            $c->sales->name ?? '-',
            $c->is_active ? 'Aktif' : 'Nonaktif',
        ]);

        return ExcelTableExport::download(
            title: 'Laporan Data Customer',
            subtitle: ($search !== '' ? "Filter pencarian: \"{$search}\" | " : '').'Diunduh: '.now()->format('d M Y H:i').' | Total: '.$items->count().' customer',
            columns: ['Kode', 'Nama', 'Alamat', 'Telepon', 'NPWP', 'Branch', 'Sales', 'Status'],
            rows: $rows,
            textColumns: [0, 3, 4], // Kode, Telepon, NPWP - jaga angka 0 di depan & angka panjang
            filenameBase: 'laporan-data-customer',
        );
    }

    /**
     * Customer Detail (Blueprint #16): informasi dasar, riwayat
     * penjualan, invoice, visit, tagging, dan riwayat Customer Assignment.
     */
    public function show(Customer $item)
    {
        // Anti-IDOR (Multi Branch/Depo): /admin/master/customers/{id}
        // dengan ID Customer Branch lain yang diketahui/ditebak.
        if (! BranchContext::current()->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Customer ini.');
        }

        $item->load(['sales']);

        $transactions = $item->salesTransactions()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $invoices = $item->invoices()->orderBy('id', 'desc')->limit(10)->get();
        $visits = $item->visits()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $taggings = $item->taggings()->with('sales')->orderBy('id', 'desc')->limit(10)->get();
        $assignments = $item->assignments()->with(['sales', 'assignedBy'])->limit(10)->get();

        return view('admin.master.customers.show', compact('item', 'transactions', 'invoices', 'visits', 'taggings', 'assignments'));
    }

    public function store(CustomerRequest $request)
    {
        $data = $this->payload($request);
        $salesId = $data['sales_id'] ?? null;
        unset($data['sales_id']);

        $data['branch_id'] = $this->resolveBranchIdForStore($data['branch_id'] ?? null, $salesId ? (int) $salesId : null);

        $item = DB::transaction(function () use ($data, $salesId) {
            $item = Customer::create($data);

            if ($salesId) {
                $this->assignmentService->assign(
                    $item,
                    (int) $salesId,
                    auth()->id(),
                    'Ditetapkan saat pembuatan customer (Master Data)',
                );
            }

            return $item;
        });

        AuditLogger::log('create', 'Master Data', Customer::class, $item->id, null, $item->fresh()->toArray());

        return redirect()->route('admin.master.customers.index')->with('status', 'Customer berhasil ditambahkan.');
    }

    public function update(CustomerRequest $request, Customer $item)
    {
        $branchContext = BranchContext::current();

        if (! $branchContext->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Customer ini.');
        }

        $before = $item->toArray();

        $data = $this->payload($request);
        $newSalesId = $data['sales_id'] ?? null;
        unset($data['sales_id']);

        // Admin biasa tidak boleh MEMINDAHKAN Customer ke Branch lain lewat
        // payload - branch_id dipaksa tetap ke Branch Admin tsb. Super Admin
        // boleh mengubah Branch lewat dropdown; kalau dikosongkan, Branch
        // lama dipertahankan (JANGAN ditimpa NULL).
        if (! $branchContext->isAll()) {
            $data['branch_id'] = $branchContext->branchId();
        } elseif (empty($data['branch_id'])) {
            unset($data['branch_id']);
        } elseif ($newSalesId) {
            $this->assertSalesMatchesBranch((int) $newSalesId, (int) $data['branch_id']);
        }

        DB::transaction(function () use ($item, $data, $newSalesId) {
            $item->update($data);

            // sales_id tidak ikut di $data, jadi $item->sales_id masih nilai lama.
            if ((int) $item->sales_id !== (int) $newSalesId) {
                if ($newSalesId) {
                    $this->assignmentService->assign(
                        $item,
                        (int) $newSalesId,
                        auth()->id(),
                        'Diubah lewat Master Data > Customer',
                    );
                } else {
                    $this->assignmentService->unassign($item, auth()->id(), 'Diubah lewat Master Data > Customer');
                }
            }
        });

        AuditLogger::log('update', 'Master Data', Customer::class, $item->id, $before, $item->fresh()->toArray());

        return redirect()->route('admin.master.customers.index')->with('status', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $item)
    {
        if (! BranchContext::current()->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Customer ini.');
        }

        $before = $item->toArray();

        try {
            $item->delete();
        } catch (QueryException $e) {
            // Masih punya transaksi, invoice, visit, dll.
            return redirect()->route('admin.master.customers.index')
                ->withErrors(['delete' => 'Customer tidak bisa dihapus karena masih dipakai data lain.']);
        }

        AuditLogger::log('delete', 'Master Data', Customer::class, $item->id, $before, null);

        return redirect()->route('admin.master.customers.index')->with('status', 'Customer berhasil dihapus.');
    }

    /**
     * Tentukan branch_id untuk Customer BARU (Anti-IDOR Multi Branch/Depo:
     * nilai dari form tidak pernah dipercaya untuk Admin biasa).
     *
     *  - Admin/Sales : selalu Branch akunnya. Kalau akun belum punya Branch,
     *    tolak dengan pesan jelas -- sebelumnya Customer tersimpan dengan
     *    branch_id NULL sehingga langsung hilang dari daftar (daftar
     *    difilter per Branch) dan terlihat seperti "tidak tersimpan".
     *  - Super Admin : Branch dari dropdown; kalau kosong, otomatis
     *    mengikuti Branch Sales yang dipilih.
     */
    private function resolveBranchIdForStore(mixed $formBranchId, ?int $salesId): int
    {
        $context = BranchContext::current();

        if (! $context->isAll()) {
            if (! $context->branchId()) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Akun Anda belum terhubung ke Branch/Depo, jadi customer belum bisa disimpan. Minta Super Admin mengisi Branch akun Anda di System > Users.',
                ]);
            }

            return (int) $context->branchId();
        }

        $branchId = $formBranchId ? (int) $formBranchId : null;

        if (! $branchId && $salesId) {
            $branchId = Sales::find($salesId)?->branch_id;
        }

        if (! $branchId) {
            throw ValidationException::withMessages(['branch_id' => 'Branch wajib dipilih.']);
        }

        if ($salesId) {
            $this->assertSalesMatchesBranch($salesId, (int) $branchId);
        }

        return (int) $branchId;
    }

    /**
     * Sales yang ditugaskan harus berada di Branch yang sama dengan Customer.
     */
    private function assertSalesMatchesBranch(int $salesId, int $branchId): void
    {
        $salesBranchId = Sales::find($salesId)?->branch_id;

        if ($salesBranchId !== null && (int) $salesBranchId !== $branchId) {
            throw ValidationException::withMessages([
                'sales_id' => 'Sales yang dipilih berada di Branch yang berbeda dengan Branch customer.',
            ]);
        }
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
     * Data siap simpan: is_active dipaksa boolean (checkbox kosong tidak
     * dikirim browser). Koordinat yang diisi manual oleh Admin dianggap sudah
     * diverifikasi, sama seperti koordinat yang lolos approve Tagging Toko.
     */
    private function payload(CustomerRequest $request): array
    {
        $data = array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]);

        if (! empty($data['latitude']) && ! empty($data['longitude'])) {
            $data['location_status'] = 'verified';
        }

        return $data;
    }
}