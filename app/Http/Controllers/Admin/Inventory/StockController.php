<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Support\BranchContext;
use Illuminate\Http\Request;

/**
 * Phase 3 - Stock Monitoring (Blueprint #33). Read-only: stock tidak
 * boleh diubah langsung dari sini (Blueprint #10).
 *
 * Penyederhanaan Tampilan Stok (RevisiMinor #7): halaman ini HANYA
 * menampilkan stok di Warehouse. Stok milik Sales (hasil BKB/transaksi)
 * tetap dicatat & dihitung seperti biasa di tabel `stocks` -- StockService
 * dan proses BTB/retur tetap akurat -- cuma baris lokasi Sales disembunyikan
 * dari tampilan ini supaya Admin tidak perlu melihat detail stok per-Sales.
 */
class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $items = BranchContext::current()->applyToLocation(
            Stock::query()->with('product')->where('location_type', Stock::LOCATION_WAREHOUSE)
        )
            ->when($search, fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->orderBy('product_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory.stock.index', compact('items', 'search'));
    }
}
