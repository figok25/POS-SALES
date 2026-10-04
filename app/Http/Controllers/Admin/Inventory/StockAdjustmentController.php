<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\Sales;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 3 - Stock Adjustment. Create -> Draft -> Apply (Blueprint #8).
 * Draft tidak mengubah stock; hanya Apply yang memanggil StockService.
 *
 * Dua jenis input (field `mode` di StockAdjustmentRequest):
 *  - single : satu produk -> satu draft tunggal (batch_code NULL).
 *  - batch  : "paket" berisi beberapa produk -> satu draft per produk,
 *             semuanya ditandai `batch_code` yang sama.
 *
 * Apply bisa dilakukan per draft (apply), per paket (applyBatch), atau
 * massal atas pilihan checkbox (bulkApply). Semua jalur memakai
 * applyOne() + applyMany(): tiap baris diproses dalam try/catch TERPISAH
 * (bukan satu DB::transaction besar) supaya 1 baris gagal (mis. stok tidak
 * cukup untuk tipe "out") tidak membatalkan baris lain yang valid.
 */
class StockAdjustmentController extends Controller
{
    public function __construct(protected StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $branchContext = BranchContext::current();

        $items = $branchContext->applyToLocation(
            StockAdjustment::query()->with(['product'])
        )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $draftCount = $branchContext->applyToLocation(
            StockAdjustment::where('status', StockAdjustment::STATUS_DRAFT)
        )->count();

        // Ringkasan tiap paket yang muncul di halaman ini (total produk &
        // jumlah yang masih Draft) -- dihitung atas SELURUH baris paket,
        // bukan hanya yang kebetulan tampil di halaman ini.
        $batchCodes = $items->getCollection()->pluck('batch_code')->filter()->unique()->values();
        $batchStats = $batchCodes->isEmpty()
            ? collect()
            : $branchContext->applyToLocation(StockAdjustment::query()->whereIn('batch_code', $batchCodes))
                ->selectRaw('batch_code, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as drafts', [StockAdjustment::STATUS_DRAFT])
                ->groupBy('batch_code')
                ->get()
                ->keyBy('batch_code');

        // Dipakai dropdown Product/Warehouse/Sales di modal "Buat draft" -
        // Warehouse & Sales dibatasi ke Branch yang diizinkan supaya Admin
        // tidak bisa membuat adjustment untuk lokasi Branch lain. Product
        // tetap global (lihat audit: produk dipakai lintas Branch).
        $products = Product::orderBy('name')->get();
        $warehouses = $branchContext->applyTo(Warehouse::query())->orderBy('name')->get();
        $salesList = $branchContext->applyTo(Sales::query())->orderBy('name')->get();

        return view('admin.inventory.adjustments.index', compact('items', 'status', 'products', 'warehouses', 'salesList', 'draftCount', 'batchStats'));
    }

    public function store(StockAdjustmentRequest $request)
    {
        $data = $request->validated();

        // Anti-IDOR (Multi Branch/Depo): Admin tidak boleh membuat
        // adjustment untuk Warehouse/Sales milik Branch lain walau tahu
        // ID-nya - divalidasi di sini karena location_type/location_id
        // polimorfik-semu, tidak bisa divalidasi lewat Rule::exists biasa.
        if (! BranchContext::current()->allowsLocation($data['location_type'], (int) $data['location_id'])) {
            return back()->withInput()->withErrors(['location_id' => 'Lokasi yang dipilih berada di Branch lain.']);
        }

        $isBatch = $data['mode'] === 'batch';
        $batchCode = $isBatch ? 'BATCH-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)) : null;

        $createdIds = DB::transaction(function () use ($data, $batchCode) {
            $ids = [];
            foreach ($data['items'] as $line) {
                $item = StockAdjustment::create([
                    'batch_code' => $batchCode,
                    'product_id' => $line['product_id'],
                    'location_type' => $data['location_type'],
                    'location_id' => $data['location_id'],
                    'type' => $data['type'],
                    'quantity' => $line['quantity'],
                    'reason' => $data['reason'] ?? null,
                    'status' => StockAdjustment::STATUS_DRAFT,
                    'created_by' => auth()->id(),
                ]);
                $ids[] = $item->id;

                AuditLogger::log('create', 'Inventory', StockAdjustment::class, $item->id, null, $item->toArray());
            }

            return $ids;
        });

        $message = $isBatch
            ? count($createdIds)." produk berhasil dibuat sebagai 1 paket draft ({$batchCode}). Silakan Apply untuk mengubah stok."
            : 'Draft Stock Adjustment berhasil dibuat. Silakan Apply untuk mengubah stok.';

        return redirect()->route('admin.inventory.adjustments.index')->with('status', $message);
    }

    public function apply(StockAdjustment $item)
    {
        if (! BranchContext::current()->allowsLocation($item->location_type, (int) $item->location_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $item->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat di-Apply.');
        }

        try {
            $this->applyOne($item);

            return redirect()->route('admin.inventory.adjustments.index')->with('status', 'Stock Adjustment berhasil diterapkan.');
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Apply satu PAKET: semua Draft yang berkode batch sama, sekaligus --
     * tidak tergantung baris mana yang kebetulan tampil di halaman ini.
     * Baris yang gagal (mis. stok tidak cukup) dilewati & dilaporkan; baris
     * lain dalam paket tetap diterapkan, dan yang gagal tetap Draft sehingga
     * bisa di-Apply lagi setelah stoknya dikoreksi.
     */
    public function applyBatch(string $batchCode)
    {
        // Anti-IDOR: baris paket di lokasi Branch lain diam-diam disaring.
        $items = BranchContext::current()->applyToLocation(
            StockAdjustment::inBatch($batchCode)
                ->where('status', StockAdjustment::STATUS_DRAFT)
                ->with('product')
        )->get();

        if ($items->isEmpty()) {
            return back()->with('error', "Tidak ada Draft yang bisa di-Apply di paket {$batchCode} (mungkin sudah di-Apply).");
        }

        [$applied, $failed] = $this->applyMany($items);

        return $this->applyResponse("Paket {$batchCode}: {$applied} dari {$items->count()} draft berhasil di-Apply.", $failed);
    }

    /**
     * Apply Massal atas pilihan checkbox (bisa campuran draft tunggal dan
     * draft dari paket mana pun). Mendukung `select_all_draft=1` (semua
     * Draft yang boleh diakses Branch context ini, tidak tergantung centang
     * di halaman mana pun -- pola sama dengan Delivery Order bulkDispatch/
     * bulkComplete) atau `item_ids[]` spesifik dari checkbox.
     */
    public function bulkApply(Request $request)
    {
        $selectAllDraft = $request->boolean('select_all_draft');

        $data = $request->validate([
            'item_ids' => [$selectAllDraft ? 'nullable' : 'required', 'array'],
            'item_ids.*' => ['integer', 'exists:stock_adjustments,id'],
        ]);

        $branchContext = BranchContext::current();

        if ($selectAllDraft) {
            $items = $branchContext->applyToLocation(
                StockAdjustment::where('status', StockAdjustment::STATUS_DRAFT)->with('product')
            )->get();
        } else {
            if (empty($data['item_ids'])) {
                return back()->with('error', 'Pilih minimal 1 Draft dulu.');
            }

            // Anti-IDOR: ID milik Branch lain yang disisipkan ke payload
            // diam-diam disaring lewat applyToLocation(), bukan ditolak
            // keras - konsisten dengan pola select_all_draft.
            $items = $branchContext->applyToLocation(
                StockAdjustment::whereIn('id', $data['item_ids'])
                    ->where('status', StockAdjustment::STATUS_DRAFT)
                    ->with('product')
            )->get();
        }

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada Draft yang valid untuk diproses (mungkin sudah di-Apply pihak lain).');
        }

        [$applied, $failed] = $this->applyMany($items);

        return $this->applyResponse("{$applied} dari {$items->count()} Stock Adjustment berhasil di-Apply sekaligus.", $failed);
    }

    /**
     * Proses banyak baris, tiap baris dalam try/catch sendiri.
     *
     * @return array{0:int, 1:array<int,string>} [jumlah berhasil, daftar pesan gagal]
     */
    private function applyMany(Collection $items): array
    {
        $applied = 0;
        $failed = [];

        foreach ($items as $item) {
            try {
                $this->applyOne($item);
                $applied++;
            } catch (InsufficientStockException $e) {
                $failed[] = "{$item->product->name} ({$item->locationName()}): {$e->getMessage()}";
            }
        }

        return [$applied, $failed];
    }

    private function applyResponse(string $message, array $failed)
    {
        if (empty($failed)) {
            return back()->with('status', $message);
        }

        $message .= ' '.count($failed).' baris gagal (stok tidak cukup) dan tetap berstatus Draft.';

        return back()->with('status', $message)->with('bulkApplyFailures', $failed);
    }

    /**
     * Logika Apply untuk satu baris, dipakai bersama oleh apply() (single),
     * applyBatch() (paket) dan bulkApply() (massal) supaya perilakunya
     * selalu identik.
     */
    private function applyOne(StockAdjustment $item): void
    {
        $movementType = $item->type === StockAdjustment::TYPE_IN ? 'adjustment_in' : 'adjustment_out';

        if ($item->type === StockAdjustment::TYPE_IN) {
            $this->stockService->increase(
                $item->product_id, $item->location_type, $item->location_id,
                (float) $item->quantity, $movementType, StockAdjustment::class, $item->id, $item->reason,
            );
        } else {
            $this->stockService->decrease(
                $item->product_id, $item->location_type, $item->location_id,
                (float) $item->quantity, $movementType, StockAdjustment::class, $item->id, $item->reason,
            );
        }

        $before = $item->toArray();
        $item->update([
            'status' => StockAdjustment::STATUS_APPLIED,
            'applied_by' => auth()->id(),
            'applied_at' => now(),
        ]);

        AuditLogger::log('apply', 'Inventory', StockAdjustment::class, $item->id, $before, $item->toArray());
    }

    public function destroy(StockAdjustment $item)
    {
        if (! BranchContext::current()->allowsLocation($item->location_type, (int) $item->location_id)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        if (! $item->isDraft()) {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }

        $before = $item->toArray();
        $item->delete();

        AuditLogger::log('delete', 'Inventory', StockAdjustment::class, $item->id, $before, null);

        return redirect()->route('admin.inventory.adjustments.index')->with('status', 'Draft berhasil dihapus.');
    }

    /**
     * Hapus satu PAKET: semua baris yang masih Draft di batch tersebut.
     * Baris yang sudah Applied tidak disentuh (stoknya sudah berubah).
     */
    public function destroyBatch(string $batchCode)
    {
        $items = BranchContext::current()->applyToLocation(
            StockAdjustment::inBatch($batchCode)->where('status', StockAdjustment::STATUS_DRAFT)
        )->get();

        if ($items->isEmpty()) {
            return back()->with('error', "Tidak ada Draft yang bisa dihapus di paket {$batchCode}.");
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $before = $item->toArray();
                $item->delete();

                AuditLogger::log('delete', 'Inventory', StockAdjustment::class, $item->id, $before, null);
            }
        });

        return redirect()->route('admin.inventory.adjustments.index')
            ->with('status', "{$items->count()} draft di paket {$batchCode} berhasil dihapus.");
    }
}
