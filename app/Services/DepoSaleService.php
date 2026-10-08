<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Payment;
use App\Models\Price;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Support\DocumentCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Penjualan langsung Depo (toko Depo / kasir): Admin menjual ke konsumen umum
 * (bukan Customer/Outlet) langsung dari Gudang Depo, harga kategori Konsumen. Berdiri sendiri -- tanpa Sales, tanpa
 * Delivery Order, tidak masuk KPI/Settlement Sales. Stok dipotong dari
 * Gudang, invoice terbit, dan pembayaran (bila diisi) masuk Buku Kas Depo.
 */
class DepoSaleService
{
    public function __construct(
        protected StockService $stockService,
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService,
    ) {
    }

    /**
     * @param  array{consumer_name?:?string, notes?:?string, items: array<int, array{product_id:int, quantity:float}>, pay_amount?:mixed, pay_method?:?string}  $data
     */
    public function create(int $branchId, int $warehouseId, ?int $userId, array $data): SalesTransaction
    {
        $warehouse = Warehouse::find($warehouseId);

        if (! $warehouse || ! $warehouse->is_active || (int) $warehouse->branch_id !== $branchId) {
            throw ValidationException::withMessages(['warehouse_id' => 'Gudang tidak valid untuk Depo ini.']);
        }

        // Konsumen umum (seperti kasir): harga selalu kategori Konsumen.
        $priceType = Price::TYPE_CONSUMER;
        $consumerName = isset($data['consumer_name']) ? trim((string) $data['consumer_name']) : null;
        $consumerName = $consumerName === '' ? null : mb_substr($consumerName, 0, 120);

        if (empty($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Transaksi harus memiliki minimal 1 produk.']);
        }

        $priceLabel = Price::typeLabel($priceType);

        // Gabungkan baris produk yang sama agar pengecekan stok akurat.
        $merged = [];
        foreach ($data['items'] as $line) {
            $merged[(int) $line['product_id']] = ($merged[(int) $line['product_id']] ?? 0) + (float) $line['quantity'];
        }

        $lines = [];
        foreach ($merged as $productId => $quantity) {
            $product = Product::find($productId);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages(['items' => "Produk #{$productId} tidak ditemukan atau sudah nonaktif."]);
            }

            if ($quantity <= 0) {
                throw ValidationException::withMessages(['items' => "Quantity untuk produk {$product->name} harus lebih besar dari 0."]);
            }

            $price = Price::where('product_id', $product->id)
                ->where('price_type', $priceType)
                ->where('is_active', true)
                ->latest('id')
                ->first();

            if (! $price) {
                throw ValidationException::withMessages([
                    'items' => "Harga {$priceLabel} aktif untuk produk {$product->name} belum diatur.",
                ]);
            }

            $lines[] = [
                'product' => $product,
                'price' => (float) $price->amount,
                'quantity' => $quantity,
                'subtotal' => round((float) $price->amount * $quantity, 2),
            ];
        }

        $total = round(array_sum(array_column($lines, 'subtotal')), 2);
        $payAmount = round((float) ($data['pay_amount'] ?? 0), 2);
        $payMethod = $data['pay_method'] ?? Payment::METHOD_CASH;

        if ($payAmount < 0 || $payAmount > $total + 0.01) {
            throw ValidationException::withMessages(['pay_amount' => 'Jumlah pembayaran tidak boleh melebihi total transaksi.']);
        }

        try {
            return DB::transaction(function () use ($branchId, $warehouse, $userId, $consumerName, $lines, $data, $priceType, $total, $payAmount, $payMethod) {
                $trx = SalesTransaction::create([
                    'code' => 'TEMP',
                    'sales_id' => null,
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouse->id,
                    'customer_id' => null,
                    'consumer_name' => $consumerName,
                    'subtotal' => $total,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $total,
                    'status' => SalesTransaction::STATUS_COMPLETED,
                    'source' => SalesTransaction::SOURCE_ADMIN,
                    'price_type' => $priceType,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $userId,
                ]);

                $trx->update(['code' => DocumentCode::make('SO', $trx->id)]);

                foreach ($lines as $line) {
                    $this->stockService->decrease(
                        $line['product']->id,
                        Stock::LOCATION_WAREHOUSE,
                        $warehouse->id,
                        $line['quantity'],
                        'depo_sale',
                        SalesTransaction::class,
                        $trx->id,
                        "Penjualan Depo {$trx->code}",
                    );

                    $trx->items()->create([
                        'product_id' => $line['product']->id,
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                $invoice = $this->invoiceService->generateFromSalesTransaction($trx->fresh('items'));

                if ($payAmount > 0) {
                    $this->paymentService->create($invoice, [
                        'amount' => $payAmount,
                        'method' => $payMethod,
                        'paid_at' => now()->toDateString(),
                        'notes' => 'Dibayar saat transaksi',
                    ], $userId);
                }

                $result = $trx->fresh(['items.product', 'invoice', 'warehouse']);

                AuditLogger::log(
                    action: 'create',
                    module: 'Sales Transaction',
                    documentType: SalesTransaction::class,
                    documentId: $result->id,
                    after: $result->toArray(),
                    userId: $userId,
                );

                return $result;
            });
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }
    }
}
