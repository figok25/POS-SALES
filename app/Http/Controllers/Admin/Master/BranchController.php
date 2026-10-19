<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\BranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\BranchDeletionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 2 - Master Data: Branch (Blueprint #32, #42 Definition of Done).
 *
 * Tambah & edit dikerjakan lewat modal di halaman index, jadi tidak ada
 * action create()/edit() lagi.
 */
class BranchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Branch::query()
            ->with(['company'])
            ->tap(fn ($query) => \App\Support\Search::apply($query, $search, ['code', 'name']))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Pilihan company untuk select di modal tambah/edit.
        $companys = Company::orderBy('name')->get();

        return view('admin.master.branches.index', compact('items', 'search', 'companys'));
    }

    public function store(BranchRequest $request)
    {
        $item = Branch::create($request->validated());

        AuditLogger::log('create', 'Master Data', Branch::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.branches.index')->with('status', 'Branch berhasil ditambahkan.');
    }

    public function update(BranchRequest $request, Branch $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Branch::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.branches.index')->with('status', 'Branch berhasil diperbarui.');
    }

    public function destroy(Branch $item)
    {
        $before = $item->toArray();

        try {
            $item->delete();
        } catch (QueryException $e) {
            return redirect()->route('admin.master.branches.index')->withErrors([
                'delete' => "Branch \"{$item->name}\" tidak bisa dihapus karena masih ada data lain yang terhubung (Warehouse, Sales, Customer, transaksi, dsb). Gunakan \"Hapus + Semua Data\" bila Branch ini memang mau dihapus beserta seluruh isinya (mis. Branch percobaan/tes).",
            ]);
        }

        AuditLogger::log('delete', 'Master Data', Branch::class, $item->id, $before, null);

        return redirect()->route('admin.master.branches.index')->with('status', 'Branch berhasil dihapus.');
    }

    /**
     * Hapus Branch beserta SELURUH data yang terikat padanya (Warehouse,
     * Sales, Customer, stok, transaksi, dst). Dipakai untuk membuang Branch
     * percobaan/tes. Wajib mengetik ulang kode Branch sebagai konfirmasi,
     * dan hanya Super Admin yang boleh (dijaga oleh route group).
     */
    public function purge(Request $request, Branch $item, BranchDeletionService $service)
    {
        $data = $request->validate([
            'confirm_code' => ['required', 'string'],
        ]);

        try {
            $summary = $service->purge($item, $data['confirm_code']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('purge_branch_id', $item->id);
        }

        return redirect()->route('admin.master.branches.index')->with(
            'status',
            sprintf(
                'Branch berhasil dihapus beserta seluruh datanya (Sales %d, Customer %d, Warehouse %d, Transaksi %d).',
                $summary['sales'],
                $summary['customers'],
                $summary['warehouses'],
                $summary['sales_transactions'],
            )
        );
    }
}