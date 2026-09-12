<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Master\EmployeeRequest;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 2 - Master Data: Employee (Blueprint #32, #42 Definition of Done).
 */
class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Employee::query()
            
            ->when($search, fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.employees.index', compact('items', 'search'));
    }

    public function create()
    {

        return view('admin.master.employees.create');
    }

    public function store(EmployeeRequest $request)
    {
        $item = Employee::create($request->validated());

        AuditLogger::log('create', 'Master Data', Employee::class, $item->id, null, $item->toArray());

        return redirect()->route('admin.master.employees.index')->with('status', 'Employee berhasil ditambahkan.');
    }

    public function edit(Employee $item)
    {

        return view('admin.master.employees.edit', compact('item'));
    }

    public function update(EmployeeRequest $request, Employee $item)
    {
        $before = $item->toArray();
        $item->update($request->validated());

        AuditLogger::log('update', 'Master Data', Employee::class, $item->id, $before, $item->toArray());

        return redirect()->route('admin.master.employees.index')->with('status', 'Employee berhasil diperbarui.');
    }

    public function destroy(Employee $item)
    {
        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Master Data', Employee::class, $item->id, $before, null);

        return redirect()->route('admin.master.employees.index')->with('status', 'Employee berhasil dihapus.');
    }
}
