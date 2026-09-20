<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Price;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Support\DocumentCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6 - Sales Transaction Backend (Blueprint #22).
 *
 * Flow (persis mengikuti Blueprint #22):
 *   Validate Customer -> Validate Product -> Validate Price ->
 *   Validate Stock -> Create Sales -> Create Sales Items ->
 *   Decrease Sales Stock -> Create Stock Movement -> Commit.
 * Seluruh proses berjalan dalam satu DB transaction; gagal di mana pun
 * akan ROLLBACK bersih (termasuk saat stock tidak cukup, ditangani lewat
 * InsufficientStockException dari StockService).
 */
class SalesTransactionService
{
    public function __construct(protected StockService $stockService, protected InvoiceService $invoiceService) {}

    /**
     * @param  array{customer_id:int, notes?:string, items: array<int, array{product_id:int, quantity:float}>}  $data
     */
    public function create(int $salesId, ?int $createdByUserId, array $data): SalesTransaction
    {
        // Validasi Customer (Blueprint #22 - Validate Customer).
        $customer = Customer::find($data['customer_id']);

        if (! $customer || ! $customer->is_active) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer tidak ditemukan atau sudah nonaktif.',
            ]);
        }

        if ($customer->sales_id && (int) $customer->sales_id !== $salesId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer ini terdaftar pada Sales lain. Anda tidak dapat membuat transaksi untuknya.',
            ]);
        }

        if (empty($data['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Transaksi harus memiliki minimal 1 produk.',
            ]);
        }

        // Validasi Product & Price per baris (Blueprint #22 - Validate
        // Product, Validate Price) dilakukan sebelum masuk DB transaction
        // supaya pesan error jelas per baris.
        $lines = [];
        foreach ($data['items'] as $line) {
            $product = Product::find($line['product_id']);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Produk #{$line['product_id']} tidak ditemukan atau sudah nonaktif.",
                ]);
            }

            $price = Price::where('product_id', $product->id)->where('is_active', true)->first();

            if (! $price) {
                throw ValidationException::withMessages([
                    'items' => "Harga aktif untuk produk {$product->name} belum diatur. Hubungi Admin.",
                ]);
            }

            $quantity = (float) $line['quantity'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => "Quantity untuk produk {$product->name} harus lebih besar dari 0.",
                ]);
            }

            $lines[] = [
                'product' => $product,
                'price' => (float) $price->amount,
                'quantity' => $quantity,
                'subtotal' => round((float) $price->amount * $quantity, 2),
            ];
        }

        try {
            return DB::transaction(function () use ($salesId, $createdByUserId, $customer, $lines, $data) {
                $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);

                $trx = SalesTransaction::create([
                    'code' => 'TEMP',
                    'sales_id' => $salesId,
                    'customer_id' => $customer->id,
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $subtotal,
                    'status' => SalesTransaction::STATUS_COMPLETED,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $createdByUserId,
                ]);

                $trx->update(['code' => DocumentCode::make('SO', $trx->id)]);

                foreach ($lines as $line) {
                    // Validasi Stock (Blueprint #22 - Validate Stock) dan
                    // Decrease Sales Stock + Create Stock Movement
                    // dilakukan bersamaan lewat StockService::decrease,
                    // yang melempar InsufficientStockException bila
                    // Sales Stock tidak cukup (default: transaksi ditolak,
                    // Blueprint #12.5).
                    $this->stockService->decrease(
                        $line['product']->id,
                        Stock::LOCATION_SALES,
                        $salesId,
                        $line['quantity'],
                        'sales_transaction',
                        SalesTransaction::class,
                        $trx->id,
                        "Sales Transaction {$trx->code}",
                    );

                    $trx->items()->create([
                        'product_id' => $line['product']->id,
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                // Generate dokumen lanjutan (Blueprint #12.5 - "Generate
                // dokumen lanjutan jika diperlukan"; Blueprint #23 relasi
                // Sales -> Invoice).
                $this->invoiceService->generateFromSalesTransaction($trx->fresh('items'));

                // Fitur B.1/B.2: setiap Sales Transaction yang selesai
                // otomatis masuk daftar Draft Delivery Order, tanpa perlu
                // Admin membuat dokumen dari awal satu per satu. Item DO
                // mengikuti persis item transaksi (snapshot, sama seperti
                // pola DeliveryOrderController::store() manual).
                $do = DeliveryOrder::create([
                    'code' => 'TEMP',
                    'sales_transaction_id' => $trx->id,
                    'status' => DeliveryOrder::STATUS_DRAFT,
                    'created_by' => $createdByUserId,
                ]);
                $do->update(['code' => DocumentCode::make('DO', $do->id)]);

                foreach ($trx->items as $line) {
                    $do->items()->create([
                        'product_id' => $line->product_id,
                        'quantity' => $line->quantity,
                    ]);
                }

                $result = $trx->fresh(['items.product', 'customer', 'invoice']);

                // Sales Transaction wajib diaudit (Blueprint #45).
                AuditLogger::log(
                    action: 'create',
                    module: 'Sales Transaction',
                    documentType: SalesTransaction::class,
                    documentId: $result->id,
                    after: $result->toArray(),
                    userId: $createdByUserId,
                );

                return $result;
            });
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }
    }
}
