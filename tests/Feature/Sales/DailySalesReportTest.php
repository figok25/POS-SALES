<?php

namespace Tests\Feature\Sales;

use App\Models\BuyGetPromo;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Laporan penjualan harian Sales (untuk dicetak): hanya data Sales sendiri,
 * pada tanggal yang dipilih.
 */
class DailySalesReportTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function sell($user, $sales, $customer, $product, float $qty): SalesTransaction
    {
        return app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ]);
    }

    private function stock($sales, $product, float $qty): void
    {
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, $qty, 'bkb_apply');
    }

    private function visit($sales, $customer): void
    {
        Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => now(),
            'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    public function test_report_contains_only_own_product_sales_for_the_day(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        [$otherUser, $otherSales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $otherCustomer = $this->makeCustomer($otherSales->id);
        $product = $this->makeProduct(10000);
        $this->stock($sales, $product, 20);
        $this->stock($otherSales, $product, 20);

        $this->sell($user, $sales, $customer, $product, 3);
        $this->sell($otherUser, $otherSales, $otherCustomer, $product, 5);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertViewHas('report', function (array $r) use ($product) {
                return (float) $r['summary']['total_sales'] === 30000.0
                    && count($r['products']) === 1
                    && $r['products'][0]['name'] === $product->name
                    && (float) $r['products'][0]['sold'] === 3.0
                    && (float) $r['products'][0]['amount'] === 30000.0;
            });
    }

    public function test_free_items_are_listed_but_not_counted_as_sales(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $rokok = $this->makeProduct(10000);
        $kopi = $this->makeProduct(5000);
        $this->stock($sales, $rokok, 10);
        $this->stock($sales, $kopi, 10);
        BuyGetPromo::create([
            'name' => 'Promo', 'buy_product_id' => $rokok->id, 'buy_qty' => 1,
            'free_product_id' => $kopi->id, 'free_qty' => 1, 'is_active' => true,
        ])->branches()->attach($sales->branch_id);

        $this->sell($user, $sales, $customer, $rokok, 2);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertViewHas('report', function (array $r) {
                return (float) $r['summary']['total_sales'] === 20000.0
                    && (float) $r['summary']['free_qty'] === 2.0;
            });
    }

    public function test_date_filter_excludes_other_dates(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(10000);
        $this->stock($sales, $product, 10);

        $old = $this->sell($user, $sales, $customer, $product, 1);
        $old->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->sell($user, $sales, $customer, $product, 2);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => (float) $r['summary']['total_sales'] === 20000.0);

        $this->actingAs($user)->get(route('sales.transactions.daily', ['date' => now()->subDays(2)->toDateString()]))
            ->assertViewHas('report', fn (array $r) => (float) $r['summary']['total_sales'] === 10000.0);
    }

    public function test_report_includes_total_visits_and_ec_split(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $product = $this->makeProduct(10000);
        $this->stock($sales, $product, 20);

        $newStore = $this->makeCustomer($sales->id);
        $repeatStore = $this->makeCustomer($sales->id);
        $visitOnly = $this->makeCustomer($sales->id);

        $old = $this->sell($user, $sales, $repeatStore, $product, 1);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();

        foreach ([$newStore, $repeatStore, $visitOnly] as $c) {
            $this->visit($sales, $c);
        }
        $this->sell($user, $sales, $newStore, $product, 1);
        $this->sell($user, $sales, $repeatStore, $product, 1);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertViewHas('report', fn (array $r) => $r['summary']['visits'] === 3
                && $r['summary']['ec'] === 2
                && $r['summary']['ec_new'] === 1
                && $r['summary']['ec_repeat'] === 1);
    }

    public function test_empty_day_renders_without_error(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertSee('Belum ada penjualan');
    }
}
