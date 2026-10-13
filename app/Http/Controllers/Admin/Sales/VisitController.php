<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sales;
use App\Models\SalesTransaction;
use App\Models\Visit;
use App\Support\BranchContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Phase 6 - Admin: Monitoring Kunjungan (Blueprint #12.4, #28 Activity Report).
 */
class VisitController extends Controller
{
    private function parseDate(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Ada transaksi selesai oleh Sales+Customer yang sama selama kunjungan (sama dengan Visit::hasTransaction). */
    private function hasTransactionSql(Builder $q, bool $exists): Builder
    {
        $method = $exists ? 'whereExists' : 'whereNotExists';

        return $q->{$method}(function ($sub) {
            $sub->selectRaw('1')
                ->from('sales_transactions')
                ->whereColumn('sales_transactions.sales_id', 'visits.sales_id')
                ->whereColumn('sales_transactions.customer_id', 'visits.customer_id')
                ->where('sales_transactions.status', SalesTransaction::STATUS_COMPLETED)
                ->whereColumn('sales_transactions.created_at', '>=', 'visits.check_in_at')
                ->where(function ($w) {
                    $w->whereNull('visits.check_out_at')
                        ->orWhereColumn('sales_transactions.created_at', '<=', 'visits.check_out_at');
                });
        });
    }

    private function filteredQuery(Request $request): Builder
    {
        $status = $request->query('status');
        $salesId = $request->query('sales_id');
        $condition = $request->query('condition');
        $outcome = $request->query('outcome');
        $search = trim((string) $request->query('q', ''));

        $from = $this->parseDate($request->query('date_from'));
        $to = $this->parseDate($request->query('date_to'));
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $query = BranchContext::current()->applyVia(
            Visit::query()->with(['sales', 'customer']),
            fn ($q, $branchId) => $q->whereHas('sales', fn ($qq) => $qq->where('branch_id', $branchId))
        )
            ->when(in_array($status, [Visit::STATUS_ONGOING, Visit::STATUS_COMPLETED], true), fn ($q) => $q->where('status', $status))
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->when(in_array($condition, Visit::CONDITIONS, true), fn ($q) => $q->where('check_in_condition', $condition))
            ->when($from, fn ($q) => $q->whereDate('check_in_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('check_in_at', '<=', $to->toDateString()))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $q->where(function ($w) use ($like) {
                    $w->whereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('code', 'like', $like))
                        ->orWhereHas('sales', fn ($s) => $s->where('name', 'like', $like));
                });
            });

        $this->applyOutcome($query, $outcome);

        return $query->orderBy('id', 'desc');
    }

    private function applyOutcome(Builder $query, ?string $outcome): void
    {
        $notClosed = fn ($q) => $q->where(function ($w) {
            $w->whereNull('visits.check_in_condition')->orWhere('visits.check_in_condition', '!=', Visit::CONDITION_CLOSED);
        });

        match ($outcome) {
            Visit::OUTCOME_CLOSED => $query->where('visits.check_in_condition', Visit::CONDITION_CLOSED),
            Visit::OUTCOME_ONGOING => $query->tap($notClosed)->where('visits.status', Visit::STATUS_ONGOING),
            Visit::OUTCOME_TRANSACTION => $this->hasTransactionSql(
                $query->tap($notClosed)->where('visits.status', '!=', Visit::STATUS_ONGOING), true
            ),
            Visit::OUTCOME_NO_TRANSACTION => $this->hasTransactionSql(
                $query->tap($notClosed)->where('visits.status', '!=', Visit::STATUS_ONGOING), false
            ),
            default => null,
        };
    }

    public function index(Request $request)
    {
        $context = BranchContext::current();
        $status = $request->query('status');

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => $this->parseDate($request->query('date_from'))?->toDateString(),
            'date_to' => $this->parseDate($request->query('date_to'))?->toDateString(),
            'sales_id' => $request->query('sales_id'),
            'condition' => $request->query('condition'),
            'outcome' => $request->query('outcome'),
            'branch' => $context->isAll() ? 'all' : (string) $context->branchId(),
        ];

        $query = $this->filteredQuery($request);

        $summary = [
            'count' => (clone $query)->toBase()->count(),
            'sales' => (clone $query)->toBase()->distinct()->count('visits.sales_id'),
            'stores' => (clone $query)->toBase()->distinct()->count('visits.customer_id'),
        ];

        $items = $query->paginate(15)->withQueryString();

        $salesList = $context->applyTo(Sales::query())->orderBy('name')->get();

        $branches = auth()->user()?->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $isFiltered = filled($filters['q']) || filled($filters['date_from']) || filled($filters['date_to'])
            || filled($filters['sales_id']) || filled($filters['condition']) || filled($filters['outcome'])
            || (auth()->user()?->isSuperAdmin() && ! $context->isAll());

        return view('admin.sales.visits.index', compact('items', 'status', 'filters', 'summary', 'salesList', 'branches', 'isFiltered'));
    }

    public function show(Visit $visit)
    {
        $visit->loadMissing('sales');
        if (! BranchContext::current()->allows($visit->sales->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke Kunjungan ini.');
        }

        $visit->load(['sales', 'customer', 'promoItems']);

        return view('admin.sales.visits.show', compact('visit'));
    }
}
