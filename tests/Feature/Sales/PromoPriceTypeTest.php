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
 * Harga Promo: kategori harga baru. Bisa dipilih Admin (master harga, kasir
 * Depo, input/edit transaksi Sales) tapi TIDAK boleh jadi "Jenis Sales".
 */
class PromoPriceTypeTest extends TestCase
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
        Price::create(['product_id' => $product->id, 'name' => 'Promo', 'price_type' => Price::TYPE_PROMO, 'amount' => 7000, 'is_active' => true]);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 20, 'bkb_apply');

        return [$admin, $sales, $customer, $product];
    }

    public function test_promo_is_a_price_category_but_not_a_sales_type(): void
    {
        $this->assertArrayHasKey(Price::TYPE_PROMO, Price::types());
        $this->assertArrayHasKey(Price::TYPE_PROMO, Price::depoTypes());
        $this->assertArrayHasKey(Price::TYPE_PROMO, Price::adminSalesTypes());
        $this->assertArrayNotHasKey(Price::TYPE_PROMO, Price::salesTypes());
        $this->assertSame('Promo', Price::typeLabel(Price::TYPE_PROMO));
    }

    public function test_admin_can_create_a_promo_price_in_master(): void
    {
        $admin = $this->makeSuperAdminUser();
        $product = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.master.prices.store'), [
            'product_id' => $product->id,
            'name' => 'Promo',
            'price_type' => Price::TYPE_PROMO,
            'amount' => 7000,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prices', ['product_id' => $product->id, 'price_type' => Price::TYPE_PROMO, 'amount' => 7000]);
    }

    public function test_sales_form_offers_promo_with_its_price_list(): void
    {
        [$admin, $sales, , $product] = $this->world();

        $this->actingAs($admin)->get(route('admin.sales.transactions.create-for-sales', ['sales_id' => $sales->id]))
            ->assertOk()
            ->assertSee('Promo')
            ->assertViewHas('pricesByType', fn (array $p) => (float) $p['promo'][$product->id] === 7000.0);
    }

    public function test_admin_can_record_a_sales_transaction_with_promo_price(): void
    {
        [$admin, $sales, $customer, $product] = $this->world();   // Sales berjenis retail

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), [
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'transaction_date' => now()->toDateString(),
            'entry_reason' => 'Promo sempat habis sebelum diperpanjang',
            'price_type' => Price::TYPE_PROMO,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 7000]],
        ])->assertSessionHasNoErrors();

        $trx = SalesTransaction::latest('id')->first();
        $this->assertSame(Price::TYPE_PROMO, $trx->price_type);
        $this->assertEquals(14000, $trx->total);
    }

    public function test_admin_can_use_promo_price_for_a_depo_sale(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'adjustment_in');
        Price::where('product_id', $product->id)->update(['price_type' => Price::TYPE_RETAIL]);
        Price::create(['product_id' => $product->id, 'name' => 'Promo', 'price_type' => Price::TYPE_PROMO, 'amount' => 7000, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.depo.store'), [
            'warehouse_id' => $warehouse->id,
            'price_type' => Price::TYPE_PROMO,
            'consumer_name' => 'Pembeli Promo',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 7000]],
        ])->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame(Price::TYPE_PROMO, $trx->price_type);
        $this->assertSame(14000.0, (float) $trx->total);
    }

    public function test_promo_cannot_be_assigned_as_a_sales_field_type(): void
    {
        $admin = $this->makeSuperAdminUser();
        $branch = $this->makeBranch();

        $this->actingAs($admin)->post(route('admin.master.sales.store'), [
            'branch_id' => $branch->id,
            'code' => 'SLS-PROMO-01',
            'name' => 'Sales Uji Promo',
            'type' => Price::TYPE_PROMO,
            'is_active' => 1,
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('sales', ['code' => 'SLS-PROMO-01']);
    }
}
