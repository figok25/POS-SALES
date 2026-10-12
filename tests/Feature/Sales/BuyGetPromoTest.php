<?php

namespace Tests\Feature\Sales;

use App\Models\BuyGetPromo;
use App\Models\Stock;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Program "Beli X gratis Y": barang gratis otomatis memotong stok Sales,
 * berlaku kelipatan, dan tidak diberikan bila stok barang gratis habis.
 */
class BuyGetPromoTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function setUpSale(float $stockA = 10, ?float $stockB = 5): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $rokok = $this->makeProduct(10000);
        $kopi = $this->makeProduct(5000);

        $stocks = app(StockService::class);
        if ($stockA) {
            $stocks->increase($rokok->id, Stock::LOCATION_SALES, $sales->id, $stockA, 'bkb_apply');
        }
        if ($stockB) {
            $stocks->increase($kopi->id, Stock::LOCATION_SALES, $sales->id, $stockB, 'bkb_apply');
        }

        return [$user, $sales, $customer, $rokok, $kopi];
    }

    private function promo($buy, $free, float $buyQty = 1, float $freeQty = 1, array $extra = []): BuyGetPromo
    {
        return BuyGetPromo::create(array_merge([
            'name' => 'Promo Test',
            'buy_product_id' => $buy->id,
            'buy_qty' => $buyQty,
            'free_product_id' => $free->id,
            'free_qty' => $freeQty,
            'is_active' => true,
        ], $extra));
    }

    private function sell($user, $sales, $customer, $product, float $qty)
    {
        return app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ]);
    }

    private function qty($product, $sales): float
    {
        return app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id);
    }

    public function test_free_item_given_per_multiple_and_deducts_sales_stock(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, 5);
        $this->promo($rokok, $kopi);

        $trx = $this->sell($user, $sales, $customer, $rokok, 3);

        $this->assertEquals(7, $this->qty($rokok, $sales));
        $this->assertEquals(2, $this->qty($kopi, $sales)); // 5 - 3 gratis

        $this->assertCount(2, $trx->items);
        $free = $trx->items->firstWhere('is_free', true);
        $this->assertSame($kopi->id, $free->product_id);
        $this->assertEquals(3, $free->quantity);
        $this->assertEquals(0, $free->price);
        $this->assertEquals(0, $free->subtotal);

        // Total hanya barang berbayar; invoice ikut menandai item gratis.
        $this->assertEquals(30000, $trx->total);
        $this->assertEquals(30000, $trx->invoice->grand_total);
        $this->assertTrue((bool) $trx->invoice->items->firstWhere('product_id', $kopi->id)->is_free);

        // Item gratis ikut tercatat di Delivery Order (barang memang dikirim).
        $this->assertCount(2, $trx->deliveryOrder->items);
    }

    public function test_multiples_apply_with_larger_buy_quantity(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, 5);
        $this->promo($rokok, $kopi, 2, 1); // beli 2 gratis 1

        $trx = $this->sell($user, $sales, $customer, $rokok, 5); // 2 kelipatan penuh

        $this->assertEquals(2, $trx->items->firstWhere('is_free', true)->quantity);
        $this->assertEquals(3, $this->qty($kopi, $sales));
    }

    public function test_free_quantity_is_capped_by_remaining_free_stock(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, 2);
        $this->promo($rokok, $kopi);

        $trx = $this->sell($user, $sales, $customer, $rokok, 5); // berhak 5, stok kopi hanya 2

        $this->assertEquals(2, $trx->items->firstWhere('is_free', true)->quantity);
        $this->assertEquals(0, $this->qty($kopi, $sales));
        $this->assertEquals(50000, $trx->total);
    }

    public function test_no_free_item_when_free_stock_is_empty_but_sale_still_succeeds(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, null);
        $this->promo($rokok, $kopi);

        $trx = $this->sell($user, $sales, $customer, $rokok, 3);

        $this->assertCount(1, $trx->items);
        $this->assertNull($trx->items->firstWhere('is_free', true));
        $this->assertEquals(7, $this->qty($rokok, $sales));
        $this->assertEquals(30000, $trx->total);
    }

    public function test_inactive_or_expired_programs_give_nothing(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, 5);
        $this->promo($rokok, $kopi, 1, 1, ['is_active' => false]);
        $this->promo($rokok, $kopi, 1, 1, ['ends_on' => now()->subDay()->toDateString()]);
        $this->promo($rokok, $kopi, 1, 1, ['starts_on' => now()->addDay()->toDateString()]);

        $trx = $this->sell($user, $sales, $customer, $rokok, 2);

        $this->assertCount(1, $trx->items);
        $this->assertEquals(5, $this->qty($kopi, $sales));
    }

    public function test_free_product_that_is_also_bought_uses_only_remaining_stock(): void
    {
        [$user, $sales, $customer, $rokok] = $this->setUpSale(3, null);
        $this->promo($rokok, $rokok); // beli 1 gratis 1 (produk sama)

        $trx = $this->sell($user, $sales, $customer, $rokok, 2); // sisa stok 1 -> gratis maksimal 1

        $this->assertEquals(1, $trx->items->firstWhere('is_free', true)->quantity);
        $this->assertEquals(0, $this->qty($rokok, $sales));
    }

    public function test_leftover_program_goods_can_still_be_sold_normally(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(0, 4);

        $trx = $this->sell($user, $sales, $customer, $kopi, 3);

        $this->assertEquals(15000, $trx->total);
        $this->assertEquals(1, $this->qty($kopi, $sales));
    }

    public function test_admin_can_create_and_deactivate_program(): void
    {
        $admin = $this->makeAdminUser();
        $rokok = $this->makeProduct();
        $kopi = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.master.buy-get-promos.store'), [
            'name' => 'Slim Vanila + Kopi',
            'buy_product_id' => $rokok->id,
            'buy_qty' => 1,
            'free_product_id' => $kopi->id,
            'free_qty' => 1,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $promo = BuyGetPromo::firstOrFail();
        $this->assertTrue($promo->is_active);

        $this->actingAs($admin)->get(route('admin.master.buy-get-promos.index'))
            ->assertOk()->assertSee('Slim Vanila + Kopi');

        $this->actingAs($admin)->put(route('admin.master.buy-get-promos.update', $promo), [
            'name' => 'Slim Vanila + Kopi',
            'buy_product_id' => $rokok->id,
            'buy_qty' => 2,
            'free_product_id' => $kopi->id,
            'free_qty' => 1,
        ])->assertSessionHasNoErrors();

        $promo->refresh();
        $this->assertFalse($promo->is_active);
        $this->assertEquals(2, $promo->buy_qty);
    }
}
