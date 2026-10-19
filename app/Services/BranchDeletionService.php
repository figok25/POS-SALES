<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sales;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hapus Branch (Depo) beserta SELURUH data yang terikat padanya.
 *
 * Dipakai untuk membuang Depo percobaan/tes yang gagal dihapus biasa karena
 * sudah ada data lain yang menempel (Warehouse, Sales, Customer, stok,
 * transaksi, dsb -- banyak di antaranya memakai constraint RESTRICT supaya
 * tidak bisa hilang diam-diam).
 *
 * Urutan hapus mengikuti rantai foreign key (anak dulu baru induk) supaya
 * tidak menabrak constraint RESTRICT. Semua dibungkus 1 transaksi: kalau ada
 * langkah yang gagal, semuanya dibatalkan (branch tetap utuh).
 *
 * TIDAK disentuh (data global, bukan milik Depo): Product, Price, Category,
 * Unit, Company, Vehicle, Supplier, Employee, Route (area pengiriman umum),
 * BuyGetPromo (dilepas dari Depo lewat branch_buy_get_promo, bukan dihapus).
 */
class BranchDeletionService
{
    /**
     * @return array<string, int> ringkasan jumlah baris yang dihapus per kategori
     */
    public function purge(Branch $branch, string $confirmCode): array
    {
        if (trim($confirmCode) !== $branch->code) {
            throw ValidationException::withMessages([
                'confirm_code' => 'Kode Depo yang diketik tidak cocok. Ketik persis: '.$branch->code,
            ]);
        }

        return DB::transaction(function () use ($branch) {
            $branchId = $branch->id;

            $warehouseIds = Warehouse::where('branch_id', $branchId)->pluck('id');
            $salesIds = Sales::where('branch_id', $branchId)->pluck('id');
            $customerIds = Customer::where('branch_id', $branchId)->pluck('id');
            $userIds = User::where('branch_id', $branchId)->pluck('id');

            $before = [
                'branch' => $branch->toArray(),
                'warehouses' => $warehouseIds->count(),
                'sales' => $salesIds->count(),
                'customers' => $customerIds->count(),
                'users' => $userIds->count(),
            ];

            $bySalesOrCustomer = fn ($q) => $q
                ->whereIn('sales_id', $salesIds)
                ->orWhereIn('customer_id', $customerIds);

            // ---- 1. Kunjungan (Visit) ----
            $visitIds = DB::table('visits')
                ->where($bySalesOrCustomer)
                ->pluck('id');
            DB::table('visit_promo_items')->whereIn('visit_id', $visitIds)->delete();
            DB::table('visits')->whereIn('id', $visitIds)->delete();

            // ---- 2. Transaksi, Invoice, Delivery Order, Payment ----
            $trxIds = DB::table('sales_transactions')
                ->where(function ($q) use ($salesIds, $customerIds, $branchId) {
                    $q->whereIn('sales_id', $salesIds)
                        ->orWhereIn('customer_id', $customerIds)
                        ->orWhere('branch_id', $branchId);
                })
                ->pluck('id');

            DB::table('delivery_orders')->whereIn('sales_transaction_id', $trxIds)->delete();

            $invoiceIds = DB::table('invoices')
                ->where(function ($q) use ($trxIds, $customerIds, $salesIds) {
                    $q->whereIn('sales_transaction_id', $trxIds)
                        ->orWhereIn('customer_id', $customerIds)
                        ->orWhereIn('sales_id', $salesIds);
                })
                ->pluck('id');

            DB::table('payments')->whereIn('invoice_id', $invoiceIds)->delete();
            DB::table('invoices')->whereIn('id', $invoiceIds)->delete();
            DB::table('sales_transactions')->whereIn('id', $trxIds)->delete();

            // ---- 3. Customer tagging, assignment, rencana kunjungan ----
            DB::table('customer_taggings')->where($bySalesOrCustomer)->delete();
            DB::table('customer_assignments')->where($bySalesOrCustomer)->delete();
            DB::table('sales_visit_plans')->where($bySalesOrCustomer)->delete();

            // ---- 4. Rute Sales (route_stops ikut lewat cascade DB) ----
            DB::table('sales_routes')->whereIn('sales_id', $salesIds)->delete();

            // ---- 5. Task harian Sales (dokumen/stok task ikut lewat cascade DB) ----
            $taskIds = DB::table('sales_tasks')
                ->where(function ($q) use ($salesIds, $branchId) {
                    $q->whereIn('sales_id', $salesIds)->orWhere('branch_id', $branchId);
                })
                ->pluck('id');
            DB::table('sales_tasks')->whereIn('id', $taskIds)->delete();

            // ---- 6. Distribusi: BKB, BTB, Settlement, Stock Request, Transfer antar Depo ----
            $byWarehouseOrSales = fn ($q) => $q
                ->whereIn('warehouse_id', $warehouseIds)
                ->orWhereIn('sales_id', $salesIds);

            DB::table('bkb_distribusi')->where($byWarehouseOrSales)->delete();
            DB::table('btb_distribusi')->where($byWarehouseOrSales)->delete();
            DB::table('settlements')->where($byWarehouseOrSales)->delete();
            DB::table('stock_requests')->where($byWarehouseOrSales)->delete();
            DB::table('branch_transfers')
                ->whereIn('from_warehouse_id', $warehouseIds)
                ->orWhereIn('to_warehouse_id', $warehouseIds)
                ->delete();

            // ---- 7. Pelacakan lapangan (tracking, lokasi terkini, riwayat lokasi, istirahat, device) ----
            $byBranchOrSales = fn ($q) => $q
                ->whereIn('sales_id', $salesIds)
                ->orWhere('branch_id', $branchId);

            DB::table('sales_tracking_sessions')->where($byBranchOrSales)->delete();
            DB::table('sales_current_locations')->where($byBranchOrSales)->delete();
            DB::table('sales_location_histories')->where($byBranchOrSales)->delete();
            DB::table('sales_breaks')->whereIn('sales_id', $salesIds)->delete();
            DB::table('sales_devices')->whereIn('sales_id', $salesIds)->delete();

            // ---- 8. Stok (polymorphic location_type/location_id, tanpa FK langsung) ----
            $purgeStockTable = function (string $table) use ($warehouseIds, $salesIds) {
                DB::table($table)->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds)->delete();
                DB::table($table)->where('location_type', 'sales')->whereIn('location_id', $salesIds)->delete();
            };
            $purgeStockTable('stocks');
            $purgeStockTable('stock_movements');
            $purgeStockTable('stock_adjustments');

            // ---- 9. Customer & Sales ----
            DB::table('customers')->whereIn('id', $customerIds)->delete();
            Sales::whereIn('id', $salesIds)->delete();

            // ---- 10. User login (Admin/Sales) milik Depo ini ----
            User::whereIn('id', $userIds)->get()->each(fn (User $u) => $u->delete());

            // ---- 11. Pembukuan & log aktivitas milik Depo ----
            DB::table('cash_ledgers')->where('branch_id', $branchId)->delete();
            DB::table('audit_logs')->where('branch_id', $branchId)->delete();

            // ---- 12. Warehouse, lalu Branch itu sendiri ----
            // (kpi_periods/kpi_targets & branch_buy_get_promo ikut lewat cascade DB saat Branch dihapus)
            Warehouse::whereIn('id', $warehouseIds)->delete();
            $branch->delete();

            $summary = [
                'warehouses' => $before['warehouses'],
                'sales' => $before['sales'],
                'customers' => $before['customers'],
                'users' => $before['users'],
                'sales_transactions' => $trxIds->count(),
                'invoices' => $invoiceIds->count(),
                'visits' => $visitIds->count(),
            ];

            AuditLogger::log(
                action: 'delete',
                module: 'Master Data',
                documentType: Branch::class,
                documentId: $branchId,
                before: $before,
                after: ['purged' => $summary],
                branchId: null,
            );

            return $summary;
        });
    }
}
