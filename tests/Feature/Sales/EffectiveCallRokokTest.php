<?php

namespace Tests\Feature\Sales;

use App\Models\Category;
use App\Models\KpiPeriod;
use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\Visit;
use App\Services\KpiAchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * EC ditentukan oleh produk rokok: rokok saja = EC, rokok + kopi = EC,
 * kopi saja = BUKAN EC (tetap transaksi biasa).
 */
class EffectiveCallRokokTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const DAY = '2026-10-05';

    private function product(string $categoryCode, bool $ec): Product
    {
        $product = $this->makeProduct();
        $category = Category::firstOrCreate(['code' => $categoryCode], ['name' => $categoryCode, 'counts_as_ec' => $ec]);
        $product->update(['category_id' => $category->id]);

        return $product;
    }

    private function visitAndSale($sales, $customer, array $items): void
    {
        static $n = 0;
        $n++;

        Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'check_in_at' => self::DAY.' 09:00:00', 'check_out_at' => self::DAY.' 09:30:00',
            'check_in_condition' => Visit::CONDITION_NORMAL, 'status' => Visit::STATUS_COMPLETED,
        ]);

        $trx = new SalesTransaction([
            'code' => "TRX-ECR-{$n}", 'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0,
            'status' => SalesTransaction::STATUS_COMPLETED,
        ]);
        $trx->created_at = self::DAY.' 09:15:00';
        $trx->updated_at = self::DAY.' 09:15:00';
        $trx->save();

        foreach ($items as $item) {
            [$product, $qty] = $item;
            SalesTransactionItem::create([
                'sales_transaction_id' => $trx->id, 'product_id' => $product->id,
                'quantity' => $qty, 'price' => 1000, 'subtotal' => 1000 * $qty,
                'is_free' => $item[2] ?? false,
            ]);
        }
    }

    public function test_only_transactions_with_rokok_count_as_ec(): void
    {
        [, $sales] = $this->makeSalesUser();
        $rokok = $this->product('RKO', true);
        $kopi = $this->product('KBO', false);

        $rokokOnly = $this->makeCustomer($sales->id);
        $both = $this->makeCustomer($sales->id);
        $kopiOnly = $this->makeCustomer($sales->id);
        $freeRokok = $this->makeCustomer($sales->id);

        $this->visitAndSale($sales, $rokokOnly, [[$rokok, 2]]);
        $this->visitAndSale($sales, $both, [[$rokok, 1], [$kopi, 3]]);
        $this->visitAndSale($sales, $kopiOnly, [[$kopi, 5]]);
        $this->visitAndSale($sales, $freeRokok, [[$kopi, 5], [$rokok, 1, true]]); // rokok gratis tidak menentukan EC

        $period = KpiPeriod::create([
            'branch_id' => $sales->branch_id, 'name' => 'Minggu 41',
            'start_date' => '2026-10-05', 'end_date' => '2026-10-11',
        ]);

        $row = app(KpiAchievementService::class)->build($period)['rows'][0];

        $this->assertSame(4, $row['actual']['call_made']);
        $this->assertSame(2, $row['actual']['ec']);   // rokok saja + rokok&kopi
    }

    public function test_without_ec_category_every_transaction_still_counts(): void
    {
        [, $sales] = $this->makeSalesUser();
        $kopi = $this->product('KBO', false);
        $this->visitAndSale($sales, $this->makeCustomer($sales->id), [[$kopi, 5]]);

        $period = KpiPeriod::create([
            'branch_id' => $sales->branch_id, 'name' => 'Minggu 41',
            'start_date' => '2026-10-05', 'end_date' => '2026-10-11',
        ]);

        $row = app(KpiAchievementService::class)->build($period)['rows'][0];

        $this->assertSame(1, $row['actual']['ec']);   // belum ada kategori penentu EC: perilaku lama
    }
}
