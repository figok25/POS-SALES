<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\CompanyRequest;
use App\Models\Company;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Company (Blueprint #32, #42 Definition of Done).
 */
class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Company::query()
            
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.companies.index', compact('items', 'search'));
    }

    public function create()
    {

        return view('admin.master.companies.create');
    }

    public function store(CompanyRequest $request)
    {
        $item = Company::create($request->validated());

        AuditLogger::log('create', 'Master Data', Company::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.companies.index')->with('status', 'Company berhasil ditambahkan.');
    }

    public function edit(Company $item)
    {

        return view('admin.master.companies.edit', compact('item'));
    }

    public function update(CompanyRequest $request, Company $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Company::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.companies.index')->with('status', 'Company berhasil diperbarui.');
    }

    public function destroy(Company $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Company::class, $item->id, $before, null);

        return redirect()->route('admin.master.companies.index')->with('status', 'Company berhasil dihapus.');
    }
}
