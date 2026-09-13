<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Phase 8 - Driver (Blueprint #38). Driver = Employee dengan flag is_driver;
 * data karyawan tetap dikelola di Master Data > Employee (Fase 2), di sini
 * hanya menandai/menampilkan siapa saja yang berperan sebagai Driver.
 */
class DriverController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = Employee::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->orderBy('is_driver', 'desc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.drivers.index', compact('items', 'search'));
    }

    public function toggle(Employee $item)
    {
        $before = $item->toArray();
        $item->update(['is_driver' => ! $item->is_driver]);

        AuditLogger::log('update', 'Operations', Employee::class, $item->id, $before, $item->toArray());

        return back()->with('status', $item->is_driver
            ? "{$item->name} ditandai sebagai Driver."
            : "{$item->name} tidak lagi menjadi Driver.");
    }
}
