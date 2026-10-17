<?php

namespace Tests\Feature\Sales;

use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Transaksi atas nama Sales: Admin bisa memilih Kategori Harga (Retail / WS-Grosir).
 */
class AdminTransactionPriceTypeTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function world(): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(10000);
        Price::where('product_id', $product->id)->update(['price_type' => Price::TYPE_RETAIL]);
        Price::create(['product_id' => $product->id, 'name' => 'Grosir', 'price_type' => Price::TYPE_WHOLESALE, 'amount' => 8000, 'is_active' => true]);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 20, 'bkb_apply');

        return [$admin, $sales, $customer, $product];
    }

    private function enter($admin, $sales, $customer, $product, array $extra = [], float $price = 8000)
    {
        return $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), $extra + [
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'transaction_date' => now()->toDateString(),
            'entry_reason' => 'Toko telepon minta tambahan',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => $price]],
        ]);
    }

    public function test_form_offers_retail_and_wholesale_with_both_price_lists(): void
    {
        [$admin, $sales, , $product] = $this->world();

        $this->actingAs($admin)->get(route('admin.sales.transactions.create-for-sales', ['sales_id' => $sales->id]))
            ->assertOk()
            ->assertSee('name="price_type"', false)
            ->assertSee('Retail')
            ->assertSee('WS / Grosir')
            ->assertViewHas('pricesByType', fn (array $p) => (float) $p['retail'][$product->id] === 10000.0
                && (float) $p['wholesale'][$product->id] === 8000.0);
    }

    public function test_admin_can_record_the_transaction_with_wholesale_category(): void
    {
        [$admin, $sales, $customer, $product] = $this->world();   // Sales berjenis retail

        $this->enter($admin, $sales, $customer, $product, ['price_type' => Price::TYPE_WHOLESALE])->assertSessionHasNoErrors();

        $trx = SalesTransaction::latest('id')->first();
        $this->assertSame(Price::TYPE_WHOLESALE, $trx->price_type);
        $this->assertEquals(16000, $trx->total);
    }

    public function test_without_choice_category_follows_sales_type(): void
    {
        [$admin, $sales, $customer, $product] = $this->world();

        $this->enter($admin, $sales, $customer, $product, [], 10000)->assertSessionHasNoErrors();

        $this->assertSame(Price::TYPE_RETAIL, SalesTransaction::latest('id')->first()->price_type);
    }

    public function test_consumer_or_unknown_category_is_rejected(): void
    {
        [$admin, $sales, $customer, $product] = $this->world();

        $this->enter($admin, $sales, $customer, $product, ['price_type' => Price::TYPE_CONSUMER])->assertSessionHasErrors('price_type');
        $this->enter($admin, $sales, $customer, $product, ['price_type' => 'tester'])->assertSessionHasErrors('price_type');
        $this->assertSame(0, SalesTransaction::count());
    }
}
