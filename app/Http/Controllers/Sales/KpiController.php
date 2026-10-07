<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\KpiPeriod;
use App\Models\Sales;
use App\Services\KpiAchievementService;
use Illuminate\Http\Request;

/** "Target Saya": Sales melihat target & pencapaiannya sendiri. */
class KpiController extends Controller
{
    public function index(Request $request, KpiAchievementService $service)
    {
        $sales = Sales::currentForUser($request->user()->id);
        abort_if($sales === null, 403, 'Akun Anda belum terhubung ke data Sales.');

        $periods = KpiPeriod::where('branch_id', $sales->branch_id)->orderByDesc('start_date')->limit(12)->get();

        $selected = $periods->firstWhere('id', (int) $request->query('period'))
            ?? $periods->first(fn (KpiPeriod $p) => today()->between($p->start_date, $p->end_date))
            ?? $periods->first();

        $result = $selected ? $service->build($selected, $sales->id) : null;

        return view('sales.kpi.index', [
            'periods' => $periods,
            'selected' => $selected,
            'row' => $result['rows'][0] ?? null,
            'products' => $result['products'] ?? collect(),
        ]);
    }
}
