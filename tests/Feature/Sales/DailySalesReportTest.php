<?php

namespace Tests\Feature\Sales;

use App\Models\BuyGetPromo;
use App\Models\Payment;
use App\Models\SalesTransaction;
use App\Models\Stock;
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

    public function test_report_contains_only_own_transactions_for_the_day(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        [$otherUser, $otherSales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $otherCustomer = $this->makeCustomer($otherSales->id);
        $product = $this->makeProduct(10000);
        $this->stock($sales, $product, 20);
        $this->stock($otherSales, $product, 20);

        $mine = $this->sell($user, $sales, $customer, $product, 3);
        $this->sell($otherUser, $otherSales, $otherCustomer, $product, 5);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertSee($mine->code)
            ->assertViewHas('report', function (array $r) use ($mine) {
                return $r['summary']['transactions'] === 1
                    && (float) $r['summary']['total_sales'] === 30000.0
                    && $r['transactions'][0]['code'] === $mine->code
                    && (float) $r['products'][0]['sold'] === 3.0;
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
        ]);

        $this->sell($user, $sales, $customer, $rokok, 2);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertSee('(GRATIS)')
            ->assertViewHas('report', function (array $r) {
                return (float) $r['summary']['total_sales'] === 20000.0
                    && (float) $r['summary']['free_qty'] === 2.0;
            });
    }

    public function test_other_dates_are_excluded_and_date_filter_works(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(10000);
        $this->stock($sales, $product, 10);

        $old = $this->sell($user, $sales, $customer, $product, 1);
        $old->forceFill(['created_at' => now()->subDays(2)])->save();
        $today = $this->sell($user, $sales, $customer, $product, 2);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => $r['summary']['transactions'] === 1
                && $r['transactions'][0]['code'] === $today->code);

        $this->actingAs($user)->get(route('sales.transactions.daily', ['date' => now()->subDays(2)->toDateString()]))
            ->assertViewHas('report', fn (array $r) => $r['summary']['transactions'] === 1
                && $r['transactions'][0]['code'] === $old->code);
    }

    public function test_payments_received_today_are_included(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(10000);
        $this->stock($sales, $product, 10);

        $trx = $this->sell($user, $sales, $customer, $product, 2);

        Payment::create([
            'code' => 'PAY-TEST-1',
            'invoice_id' => $trx->invoice->id,
            'amount' => 15000,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now()->toDateString(),
            'received_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertViewHas('report', fn (array $r) => (float) $r['summary']['received'] === 15000.0
                && $r['payments_by_method'][0]['method'] === 'Tunai');
    }

    public function test_empty_day_renders_without_error(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->get(route('sales.transactions.daily'))
            ->assertOk()
            ->assertSee('Belum ada transaksi');
    }
}
