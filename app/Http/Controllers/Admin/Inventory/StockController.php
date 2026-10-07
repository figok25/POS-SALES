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
 * Penyederhanaan Tampilan Stok (RevisiMinor #7): menu ini HANYA menampilkan
 * stok Warehouse (gudang utama). Stok yang sedang dibawa Sales
 * (location_type = sales) sengaja tidak ditampilkan di sini, termasuk
 * setelah Return Stock/BTB: barang yang kembali muncul sebagai tambahan
 * stok Warehouse. Data stok Sales tetap tersimpan dan tetap dipakai proses
 * lain (transaksi, Task, BTB, Settlement, StockService) -- hanya tampilan
 * menu Stock Admin yang disederhanakan. Halaman Stok di aplikasi Sales
 * tidak berubah.
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
