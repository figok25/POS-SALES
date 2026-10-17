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
 * Edit transaksi penjualan: Admin bisa memilih Kategori Harga (Retail / WS-Grosir).
 */
class AdminTransactionPriceTypeEditTest extends TestCase
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

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), [
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'transaction_date' => now()->toDateString(), 'entry_reason' => 'Sales lupa input',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 10000]],
        ])->assertSessionHasNoErrors();

        return [$admin, SalesTransaction::latest('id')->first(), $product];
    }

    private function update($admin, SalesTransaction $trx, $product, array $extra = [], float $price = 10000)
    {
        return $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $extra + [
            'reason' => 'Salah kategori harga',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => $price]],
        ]);
    }

    public function test_edit_page_offers_price_category_with_master_prices(): void
    {
        [$admin, $trx, $product] = $this->world();

        $this->actingAs($admin)->get(route('admin.sales.transactions.edit', $trx))
            ->assertOk()
            ->assertSee('name="price_type"', false)
            ->assertSee('WS / Grosir')
            ->assertViewHas('pricesByType', fn (array $p) => (float) $p['wholesale'][$product->id] === 8000.0);
    }

    public function test_admin_changes_category_to_wholesale_and_total_follows_new_prices(): void
    {
        [$admin, $trx, $product] = $this->world();
        $this->assertSame(Price::TYPE_RETAIL, $trx->price_type);

        $this->update($admin, $trx, $product, ['price_type' => Price::TYPE_WHOLESALE], 8000)->assertSessionHasNoErrors();

        $trx->refresh();
        $this->assertSame(Price::TYPE_WHOLESALE, $trx->price_type);
        $this->assertEquals(16000, $trx->total);
    }

    public function test_without_choice_category_stays_as_is(): void
    {
        [$admin, $trx, $product] = $this->world();

        $this->update($admin, $trx, $product)->assertSessionHasNoErrors();

        $this->assertSame(Price::TYPE_RETAIL, $trx->refresh()->price_type);
    }

    public function test_consumer_category_is_rejected_for_sales_transaction(): void
    {
        [$admin, $trx, $product] = $this->world();

        $this->update($admin, $trx, $product, ['price_type' => Price::TYPE_CONSUMER])->assertSessionHasErrors('price_type');
        $this->assertSame(Price::TYPE_RETAIL, $trx->refresh()->price_type);
    }
}
