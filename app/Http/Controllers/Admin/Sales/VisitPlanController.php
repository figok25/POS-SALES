<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\SalesVisitPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Fitur A.2 - Perencanaan Kunjungan Harian (Visit Plan): antarmuka Admin
 * menyusun jadwal kunjungan berulang per hari (Senin..Minggu) untuk
 * masing-masing Sales, dari outlet yang sudah di-tag ke Sales tsb
 * (lihat CustomerAssignmentController). Dipakai otomatis oleh
 * SalesTaskController::store() saat membuat Sales Task baru.
 */
class VisitPlanController extends Controller
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function index(Request $request)
    {
        $search = $request->query('q');

        $saless = Sales::where('is_active', true)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('customers')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.sales.visit-plans.index', compact('saless', 'search'));
    }

    public function edit(Sales $sales)
    {
        $customers = $sales->customers()->where('is_active', true)->orderBy('name')->get();

        $plans = SalesVisitPlan::where('sales_id', $sales->id)
            ->orderBy('sequence')
            ->get()
            ->groupBy('day_of_week');

        return view('admin.sales.visit-plans.edit', [
            'sales' => $sales,
            'customers' => $customers,
            'plans' => $plans,
            'days' => self::DAYS,
        ]);
    }

    public function update(Request $request, Sales $sales)
    {
        // Format input: plan[day_of_week][] = customer_id, urutan array = sequence.
        $data = $request->validate([
            'plan' => ['nullable', 'array'],
            'plan.*' => ['array'],
            'plan.*.*' => ['integer', 'exists:customers,id'],
        ]);

        DB::transaction(function () use ($data, $sales) {
            SalesVisitPlan::where('sales_id', $sales->id)->delete();

            foreach (($data['plan'] ?? []) as $day => $customerIds) {
                foreach (array_values($customerIds) as $sequence => $customerId) {
                    SalesVisitPlan::create([
                        'sales_id' => $sales->id,
                        'day_of_week' => (int) $day,
                        'customer_id' => $customerId,
                        'sequence' => $sequence,
                    ]);
                }
            }
        });

        return redirect()->route('admin.sales.visit-plans.edit', $sales)
            ->with('status', 'Visit Plan mingguan untuk '.$sales->name.' berhasil disimpan.');
    }
}
