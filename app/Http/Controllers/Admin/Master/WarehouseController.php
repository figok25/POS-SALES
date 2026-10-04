<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\WarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Support\BranchContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Warehouse (Blueprint #32, #42 Definition of Done).
 *
 * Tambah/edit/hapus memakai modal di halaman index, jadi tidak ada
 * create(), edit(), maupun show(). Daftarkan route dengan:
 * Route::resource('warehouses', WarehouseController::class)->except(['create', 'edit', 'show']);
 */
class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $items = BranchContext::current()->applyTo(
            Warehouse::query()->with('branch')
        )
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Dipakai dropdown Branch di modal tambah/edit - Admin hanya boleh
        // membuat Warehouse untuk Branch-nya sendiri.
        $branchs = BranchContext::current()->applyTo(Branch::query(), 'id')->orderBy('name')->get(['id', 'name']);

        return view('admin.master.warehouses.index', compact('items', 'search', 'branchs'));
    }

    public function store(WarehouseRequest $request)
    {
        $data = $this->payload($request);

        // Anti-IDOR (Multi Branch/Depo): branch_id TIDAK PERNAH dipercaya
        // mentah dari form untuk Admin biasa.
        $branchContext = BranchContext::current();
        if (! $branchContext->isAll()) {
            $data['branch_id'] = $branchContext->branchId();
        }

        $item = Warehouse::create($data);

        AuditLogger::log('create', 'Master Data', Warehouse::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.warehouses.index')->with('status', 'Warehouse berhasil ditambahkan.');
    }

    public function update(WarehouseRequest $request, Warehouse $item)
    {
        $branchContext = BranchContext::current();

        if (! $branchContext->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Warehouse ini.');
        }

        $before = $item->toArray();
        $data = $this->payload($request);

        if (! $branchContext->isAll()) {
            $data['branch_id'] = $branchContext->branchId();
        }

        $item->update($data);

        AuditLogger::log('update', 'Master Data', Warehouse::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.warehouses.index')->with('status', 'Warehouse berhasil diperbarui.');
    }

    public function destroy(Warehouse $item)
    {
        if (! BranchContext::current()->allows($item->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Warehouse ini.');
        }

        $before = $item->toArray();

        try {
            $item->delete();
        } catch (QueryException $e) {
            // Masih dipakai tabel lain (foreign key).
            return redirect()->route('admin.master.warehouses.index')
                ->withErrors(['delete' => 'Warehouse tidak bisa dihapus karena masih dipakai data lain.']);
        }

        AuditLogger::log('delete', 'Master Data', Warehouse::class, $item->id, $before, null);

        return redirect()->route('admin.master.warehouses.index')->with('status', 'Warehouse berhasil dihapus.');
    }

    /**
     * Checkbox yang tidak dicentang tidak dikirim browser, jadi is_active
     * harus dipaksa jadi boolean supaya bisa dinonaktifkan saat edit.
     */
    private function payload(WarehouseRequest $request): array
    {
        return array_merge($request->validated(), ['is_active' => $request->boolean('is_active')]);
    }
}