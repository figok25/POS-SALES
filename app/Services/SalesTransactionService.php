<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Services\AuditLogger;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Price;
use App\Models\Product;
use App\Models\Sales;
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
    public function __construct(
        protected StockService $stockService,
        protected InvoiceService $invoiceService,
        protected PromoService $promoService,
    ) {}

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

        // Multi Branch/Depo: pengecekan di atas hanya menangkap Customer yang
        // SUDAH ditugaskan ke Sales lain. Customer tanpa Sales dari Depo lain
        // juga tidak boleh dipakai transaksi (stok & invoice-nya akan masuk
        // Depo yang salah). Customer lama tanpa Depo (branch_id NULL) tetap
        // diizinkan agar data lama tidak terblokir.
        $salesBranchId = Sales::query()->whereKey($salesId)->value('branch_id');

        if ($customer->branch_id !== null && $salesBranchId !== null
            && (int) $customer->branch_id !== (int) $salesBranchId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer ini terdaftar di Depo lain. Anda tidak dapat membuat transaksi untuknya.',
            ]);
        }

        $shells = $data['shells'] ?? [];
        $hasShells = collect($shells)->contains(fn ($s) => (float) ($s['quantity'] ?? 0) > 0);

        if (empty($data['items']) && ! $hasShells) {
            throw ValidationException::withMessages([
                'items' => 'Transaksi harus memiliki minimal 1 produk.',
            ]);
        }

        // Kategori harga mengikuti jenis Sales (Retail / WS-Grosir).
        $sales = Sales::findOrFail($salesId);
        $priceType = $sales->priceType();
        $priceTypeLabel = Price::typeLabel($priceType);

        // Validasi Product & Price per baris (Blueprint #22 - Validate
        // Product, Validate Price) dilakukan sebelum masuk DB transaction
        // supaya pesan error jelas per baris.
        // Input Admin/Superadmin atas nama Sales: harga & diskon boleh disesuaikan,
        // tanggal transaksi sebenarnya bisa dipilih. Jalur Sales biasa tidak
        // pernah memakai ini (flag hanya diset oleh form Admin).
        $adminEntry = (bool) ($data['admin_entry'] ?? false);

        $lines = [];
        foreach ($data['items'] ?? [] as $line) {
            $product = Product::find($line['product_id']);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Produk #{$line['product_id']} tidak ditemukan atau sudah nonaktif.",
                ]);
            }

            $price = Price::where('product_id', $product->id)
                ->where('price_type', $priceType)
                ->where('is_active', true)
                ->latest('id')
                ->first();

            $override = $adminEntry && isset($line['price']) && $line['price'] !== '' ? round((float) $line['price'], 2) : null;

            if (! $price && $override === null) {
                throw ValidationException::withMessages([
                    'items' => "Harga {$priceTypeLabel} aktif untuk produk {$product->name} belum diatur. Hubungi Admin.",
                ]);
            }

            if ($override !== null && $override < 0) {
                throw ValidationException::withMessages(['items' => "Harga {$product->name} tidak boleh negatif."]);
            }

            $unitPrice = $override ?? (float) $price->amount;
            $quantity = (float) $line['quantity'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => "Quantity untuk produk {$product->name} harus lebih besar dari 0.",
                ]);
            }

            $lines[] = [
                'product' => $product,
                'price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => round($unitPrice * $quantity, 2),
            ];
        }

        $discount = 0.0;
        $entryDate = null;

        if ($adminEntry) {
            $subtotalPreview = round(array_sum(array_column($lines, 'subtotal')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount < 0 || $discount > $subtotalPreview + 0.001) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh negatif atau melebihi subtotal.']);
            }

            $entryDate = ! empty($data['transaction_date']) ? \Illuminate\Support\Carbon::parse($data['transaction_date'])->startOfDay() : now()->startOfDay();

            if ($entryDate->isFuture()) {
                throw ValidationException::withMessages(['transaction_date' => 'Tanggal transaksi tidak boleh di masa depan.']);
            }

            // Cegah transaksi yang sama tercatat dua kali (Sales sudah mencatat, Admin mencatat lagi).
            if (! ($data['allow_duplicate'] ?? false)) {
                $this->assertNotDuplicate($salesId, $customer->id, $entryDate->toDateString(), $lines);
            }
        }

        // Program Beli X Gratis Y: hitung barang gratis (dibatasi stok Sales).
        $freeLines = $this->promoService->freeLinesFor($salesId, $lines, $shells);

        try {
            return DB::transaction(function () use ($salesId, $createdByUserId, $customer, $lines, $freeLines, $data, $priceType, $adminEntry, $discount, $entryDate) {
                $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);

                $trx = SalesTransaction::create([
                    'code' => 'TEMP',
                    'sales_id' => $salesId,
                    'customer_id' => $customer->id,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => 0,
                    'total' => round($subtotal - $discount, 2),
                    'status' => SalesTransaction::STATUS_COMPLETED,
                    'price_type' => $priceType,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $createdByUserId,
                    // Admin input atas nama Sales: sumber 'admin', pembuat = Admin,
                    // tanggal transaksi = tanggal sebenarnya, recorded_at = waktu input.
                    'source' => $adminEntry ? SalesTransaction::SOURCE_ADMIN : SalesTransaction::SOURCE_SALES,
                    'recorded_at' => $adminEntry ? now() : null,
                    'entry_reason' => $adminEntry ? ($data['entry_reason'] ?? null) : null,
                ]);

                if ($adminEntry) {
                    // Hari ini: pakai jam sekarang. Hari lampau: jam tengah hari agar tidak bergeser zona.
                    $at = $entryDate->isToday() ? now() : $entryDate->copy()->setTime(12, 0);
                    $trx->created_at = $at;
                    $trx->updated_at = $at;
                    $trx->save();
                }

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

                // Barang gratis program: stok Sales berkurang, item berharga 0.
                foreach ($freeLines as $free) {
                    $this->stockService->decrease(
                        $free['product_id'],
                        Stock::LOCATION_SALES,
                        $salesId,
                        $free['quantity'],
                        'sales_transaction',
                        SalesTransaction::class,
                        $trx->id,
                        (isset($free['shells']) ? 'Tukar cangkang: ' : 'Gratis program: ')."{$free['promo']->name} ({$trx->code})",
                    );

                    $trx->items()->create([
                        'product_id' => $free['product_id'],
                        'quantity' => $free['quantity'],
                        'price' => 0,
                        'subtotal' => 0,
                        'is_free' => true,
                        'buy_get_promo_id' => $free['promo']->id,
                        'shell_qty' => $free['shells'] ?? null,
                    ]);
                }

                // Generate dokumen lanjutan (Blueprint #12.5 - "Generate
                // dokumen lanjutan jika diperlukan"; Blueprint #23 relasi
                // Sales -> Invoice).
                $invoice = $this->invoiceService->generateFromSalesTransaction($trx->fresh('items'));

                if ($adminEntry) {
                    $invoice->update(['date' => $trx->created_at->toDateString()]);
                }

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

    /**
     * Transaksi yang sama = Sales + Customer + tanggal + komposisi barang (produk & qty) yang sama.
     *
     * @param  array<int, array{product: \App\Models\Product, quantity: float}>  $lines
     */
    private function assertNotDuplicate(int $salesId, int $customerId, string $day, array $lines): void
    {
        $signature = [];
        foreach ($lines as $line) {
            $signature[$line['product']->id] = round(($signature[$line['product']->id] ?? 0) + $line['quantity'], 2);
        }
        ksort($signature);

        $existing = SalesTransaction::query()
            ->where('sales_id', $salesId)
            ->where('customer_id', $customerId)
            ->where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereDate('created_at', $day)
            ->with('items')
            ->get();

        foreach ($existing as $trx) {
            $other = [];
            foreach ($trx->items->where('is_free', false) as $item) {
                $other[$item->product_id] = round(($other[$item->product_id] ?? 0) + (float) $item->quantity, 2);
            }
            ksort($other);

            if ($other === $signature) {
                throw ValidationException::withMessages([
                    'duplicate' => "Transaksi yang sama (Sales, Customer, tanggal, dan barang) sudah tercatat: {$trx->code}. Centang \"Tetap simpan\" bila ini memang transaksi yang berbeda.",
                ]);
            }
        }
    }
}
