<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\AuditLogger;
use App\Services\StockService;
use App\Support\BranchContext;
use Illuminate\Http\Request;

/**
 * Phase 3 - Stock Monitoring (Blueprint #33). Admin biasa read-only.
 * Jumlah stok HANYA bisa dikoreksi oleh Super Admin (update()), lewat
 * StockService::correctTo() sehingga tetap tercatat di Stock Movement &
 * Audit Log lengkap dengan alasan.
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

    /**
     * Koreksi jumlah stok Warehouse -- khusus Super Admin (route dikunci
     * role:super_admin). Wajib isi alasan; dicatat sebagai movement
     * `stock_correction` + audit log.
     */
    public function update(Request $request, Stock $stock, StockService $stocks)
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        // Hanya baris Warehouse yang masuk cabang aktif yang boleh diubah.
        $row = BranchContext::current()->applyToLocation(
            Stock::query()->where('location_type', Stock::LOCATION_WAREHOUSE)
        )->findOrFail($stock->id);

        $before = (float) $row->quantity;
        $movement = $stocks->correctTo(
            $row->product_id, $row->location_type, $row->location_id,
            (float) $data['quantity'], 'Koreksi Super Admin: '.$data['reason'],
        );

        if (! $movement) {
            return back()->with('status', 'Jumlah stok tidak berubah.');
        }

        AuditLogger::log('stock_correction', 'Inventory', Stock::class, $row->id,
            ['quantity' => $before], ['quantity' => (float) $data['quantity'], 'reason' => $data['reason']]);

        return back()->with('status', 'Stok berhasil dikoreksi dari '.number_format($before, 2).' menjadi '.number_format((float) $data['quantity'], 2).'.');
    }
}
