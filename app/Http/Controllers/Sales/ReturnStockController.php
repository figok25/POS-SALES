<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCurrentSales;
use App\Http\Requests\Sales\ReturnStockRequest;
use App\Models\BtbDistribusi;
use App\Models\Product;
use App\Models\SalesTask;
use App\Models\Stock;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\DocumentCode;
use Illuminate\Support\Facades\DB;

/**
 * Business Flow Update v3.1 (Blueprint #13.11, #13.12):
 *
 * Sales Mobile -> Return Stock -> Submit -> Laravel -> BTB Distribusi
 * (WAITING_CHECK) -> Admin.
 *
 * Sales TIDAK membuat BTB Distribusi secara manual. Submit di sini hanya
 * mencatat dokumen retur (BtbDistribusi + items) berstatus draft/waiting
 * check; stock TIDAK berpindah pada langkah ini. Stock baru berpindah
 * (Sales Stock -> Warehouse Stock) setelah Admin memeriksa & meng-Apply
 * (Approve) lewat Admin\Distribution\BtbDistribusiController::apply(),
 * sesuai Blueprint #13.13 - "Stock retur tidak boleh langsung dianggap
 * kembali ke Warehouse hanya karena Sales menekan Submit."
 */
class ReturnStockController extends Controller
{
    use ResolvesCurrentSales;

    public function __construct(protected StockService $stockService)
    {
    }

    public function index()
    {
        $sales = $this->currentSales();

        $currentStock = Stock::where('location_type', Stock::LOCATION_SALES)
            ->where('location_id', $sales->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->orderBy('id')
            ->get();

        $returns = BtbDistribusi::where('sales_id', $sales->id)
            ->where('source', BtbDistribusi::SOURCE_RETURN_STOCK)
            ->with('items.product')
            ->orderByDesc('id')
            ->get();

        $activeTask = $this->activeReturnableTask($sales->id);

        return view('sales.return-stock.index', compact('currentStock', 'returns', 'activeTask'));
    }

    public function store(ReturnStockRequest $request)
    {
        $sales = $this->currentSales();
        $data = $request->validated();

        // Saring baris yang benar-benar diisi (quantity > 0); sisanya
        // berarti produk tersebut tidak ikut diretur pada submission ini.
        $items = array_values(array_filter($data['items'], fn ($line) => (float) ($line['quantity'] ?? 0) > 0));

        if (empty($items)) {
            return back()->withInput()->with('error', 'Pilih minimal 1 produk dengan quantity lebih dari 0 untuk diretur.');
        }

        $task = $this->activeReturnableTask($sales->id);

        if (! $task) {
            return back()->with('error', 'Tidak ada Sales Task aktif/selesai yang dapat menjadi rujukan retur. Return Stock hanya bisa dilakukan atas BKB yang sedang/sudah Anda kerjakan.');
        }

        // Validasi quantity tidak melebihi Sales Stock yang benar-benar
        // tersedia saat ini (Blueprint #13.11 - stock sisa).
        foreach ($items as $line) {
            $available = $this->stockService->getQuantity($line['product_id'], Stock::LOCATION_SALES, $sales->id);

            if ((float) $line['quantity'] > $available) {
                $product = Product::find($line['product_id']);

                return back()->withInput()->with('error', sprintf(
                    'Quantity retur %s (%s) melebihi Sales Stock yang tersedia (%s).',
                    $product->name ?? "Produk #{$line['product_id']}",
                    number_format((float) $line['quantity'], 2),
                    number_format($available, 2),
                ));
            }
        }

        $btb = DB::transaction(function () use ($sales, $task, $data, $items) {
            $btb = BtbDistribusi::create([
                'code' => 'TEMP',
                'bkb_distribusi_id' => $task->bkb_distribusi_id,
                'sales_id' => $sales->id,
                'warehouse_id' => $task->bkbDistribusi->warehouse_id,
                'status' => BtbDistribusi::STATUS_DRAFT, // = RETURN_SUBMITTED / WAITING_CHECK
                'source' => BtbDistribusi::SOURCE_RETURN_STOCK,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $btb->update(['code' => DocumentCode::make('BTB', $btb->id)]);

            foreach ($items as $line) {
                $btb->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            // Business Flow Update v3.1 (Blueprint #13.15, #13.17): Return
            // Stock adalah penanda "pekerjaan selesai" -- Sales Task pindah
            // dari WORKING ke COMPLETED pada titik ini. Kalau Task masih di
            // READY_TO_WORK (retur tanpa transaksi sama sekali), tetap
            // ditutup sebagai COMPLETED karena Sales sudah menyerahkan semua
            // stock yang dibawanya.
            if ($task->status !== SalesTask::STATUS_COMPLETED) {
                $task->update(['status' => SalesTask::STATUS_COMPLETED, 'completed_at' => now()]);
            }

            return $btb;
        });

        AuditLogger::log('return_stock_submit', 'Sales', BtbDistribusi::class, $btb->id, null, $btb->load('items')->toArray());

        return redirect()->route('sales.return-stock.index')
            ->with('status', "Return Stock berhasil disubmit sebagai {$btb->code}. Task Anda ditandai selesai, menunggu pemeriksaan Admin.");
    }

    /**
     * Task yang boleh dijadikan rujukan retur: milik Sales ini, untuk
     * HARI INI, dan statusnya sinkron dengan gate EnsureActiveSalesTask
     * (ready_to_work/working) -- supaya begitu Sales bisa membuka
     * halaman ini, pasti ada Task yang valid untuk dijadikan rujukan
     * retur (tidak ada celah antara gate & business logic di sini).
     */
    private function activeReturnableTask(int $salesId): ?SalesTask
    {
        return SalesTask::where('sales_id', $salesId)
            ->whereNotNull('bkb_distribusi_id')
            ->whereDate('task_date', today())
            ->whereIn('status', [SalesTask::STATUS_READY_TO_WORK, SalesTask::STATUS_WORKING])
            ->with('bkbDistribusi')
            ->latest('id')
            ->first();
    }
}
