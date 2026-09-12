<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\BranchRequest;
use App\Models\Branch;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Branch (Blueprint #32, #42 Definition of Done).
 */
class BranchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Branch::query()
            ->with(['company'])
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.branches.index', compact('items', 'search'));
    }

    public function create()
    {
        $companys = \App\Models\Company::orderBy('name')->get();
        return view('admin.master.branches.create', compact('companys'));
    }

    public function store(BranchRequest $request)
    {
        $item = Branch::create($request->validated());

        AuditLogger::log('create', 'Master Data', Branch::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.branches.index')->with('status', 'Branch berhasil ditambahkan.');
    }

    public function edit(Branch $item)
    {
        $companys = \App\Models\Company::orderBy('name')->get();
        return view('admin.master.branches.edit', compact('item', 'companys'));
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
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Branch::class, $item->id, $before, null);

        return redirect()->route('admin.master.branches.index')->with('status', 'Branch berhasil dihapus.');
    }
}
