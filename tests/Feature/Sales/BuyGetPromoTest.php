<?php

namespace Tests\Feature\Sales;

use App\Models\BuyGetPromo;
use App\Models\Stock;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    private function promo($buy, $free, float $buyQty = 1, float $freeQty = 1, array $extra = [], ?array $branchIds = null): BuyGetPromo
    {
        $promo = BuyGetPromo::create(array_merge([
            'name' => 'Promo Test',
            'buy_product_id' => $buy->id,
            'buy_qty' => $buyQty,
            'free_product_id' => $free->id,
            'free_qty' => $freeQty,
            'is_active' => true,
        ], $extra));

        // Default: aktif di Depo bawaan fixture (Depo Sales di test ini).
        $promo->branches()->attach($branchIds ?? [$this->defaultBranch()->id]);

        return $promo;
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
            'branch_ids' => [$this->defaultBranch()->id],
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
            'branch_ids' => [$this->defaultBranch()->id],
        ])->assertSessionHasNoErrors();

        $promo->refresh();
        $this->assertFalse($promo->is_active);
        $this->assertEquals(2, $promo->buy_qty);
    }

    public function test_program_only_applies_to_depos_it_is_active_in(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, 5);
        $other = $this->makeBranch('CBG-LAIN', 'Depo Lain');
        $this->promo($rokok, $kopi, 1, 1, [], [$other->id]); // hanya Depo lain

        $trx = $this->sell($user, $sales, $customer, $rokok, 3);

        $this->assertCount(1, $trx->items);
        $this->assertEquals(5, $this->qty($kopi, $sales));
    }

    public function test_program_can_be_active_in_several_depos(): void
    {
        [$user, $sales, $customer, $rokok, $kopi] = $this->setUpSale(10, 5);
        $other = $this->makeBranch('CBG-LAIN', 'Depo Lain');
        $this->promo($rokok, $kopi, 1, 1, [], [$other->id, $this->defaultBranch()->id]);

        $trx = $this->sell($user, $sales, $customer, $rokok, 2);

        $this->assertEquals(2, $trx->items->firstWhere('is_free', true)->quantity);
    }

    public function test_program_requires_at_least_one_depo_and_admin_can_change_depos(): void
    {
        $admin = $this->makeAdminUser();
        $rokok = $this->makeProduct();
        $kopi = $this->makeProduct();
        $other = $this->makeBranch('CBG-LAIN', 'Depo Lain');

        $payload = [
            'name' => 'Promo Depo',
            'buy_product_id' => $rokok->id,
            'buy_qty' => 1,
            'free_product_id' => $kopi->id,
            'free_qty' => 1,
            'is_active' => 1,
        ];

        $this->actingAs($admin)->post(route('admin.master.buy-get-promos.store'), $payload)
            ->assertSessionHasErrors('branch_ids');
        $this->assertSame(0, BuyGetPromo::count());

        $this->actingAs($admin)->post(route('admin.master.buy-get-promos.store'), $payload + ['branch_ids' => [$other->id]])
            ->assertSessionHasNoErrors();

        $promo = BuyGetPromo::firstOrFail();
        $this->assertEquals([$other->id], $promo->branches()->pluck('branches.id')->all());

        $this->actingAs($admin)->put(route('admin.master.buy-get-promos.update', $promo), $payload + [
            'branch_ids' => [$other->id, $this->defaultBranch()->id],
        ])->assertSessionHasNoErrors();

        $this->assertCount(2, $promo->fresh()->branches);
    }

    private function shellPromo($shellProduct, $free, float $rate = 8, float $freeQty = 1, ?array $branchIds = null): BuyGetPromo
    {
        return $this->promo($shellProduct, $free, $rate, $freeQty, ['type' => BuyGetPromo::TYPE_SHELL, 'name' => 'Tukar Cangkang'], $branchIds);
    }

    private function exchange($user, $sales, $customer, $promo, float $shells, array $items = [])
    {
        return app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => $items,
            'shells' => [['promo_id' => $promo->id, 'quantity' => $shells]],
        ]);
    }

    public function test_shell_exchange_gives_free_item_per_multiple_and_records_shells(): void
    {
        [$user, $sales, $customer, $kretek] = $this->setUpSale(10, null);
        $promo = $this->shellPromo($kretek, $kretek, 8, 1); // 8 cangkang = gratis 1

        $trx = $this->exchange($user, $sales, $customer, $promo, 17); // 2 kelipatan

        $free = $trx->items->firstWhere('is_free', true);
        $this->assertEquals(2, $free->quantity);
        $this->assertEquals(17, $free->shell_qty);
        $this->assertEquals(0, $free->subtotal);
        $this->assertEquals(8, $this->qty($kretek, $sales));
        $this->assertEquals(0, $trx->total);
        $this->assertSame('paid', $trx->invoice->status);
    }

    public function test_shell_exchange_can_be_combined_with_a_purchase(): void
    {
        [$user, $sales, $customer, $kretek, $kopi] = $this->setUpSale(10, 5);
        $promo = $this->shellPromo($kretek, $kretek, 5, 1);

        $trx = $this->exchange($user, $sales, $customer, $promo, 10, [['product_id' => $kopi->id, 'quantity' => 2]]);

        $this->assertEquals(10000, $trx->total);
        $this->assertEquals(2, $trx->items->firstWhere('is_free', true)->quantity);
        $this->assertEquals(8, $this->qty($kretek, $sales));
        $this->assertEquals(3, $this->qty($kopi, $sales));
    }

    public function test_shell_exchange_below_rate_is_rejected(): void
    {
        [$user, $sales, $customer, $kretek] = $this->setUpSale(10, null);
        $promo = $this->shellPromo($kretek, $kretek, 8, 1);

        $this->expectException(ValidationException::class);
        $this->exchange($user, $sales, $customer, $promo, 7);
    }

    public function test_shell_exchange_is_rejected_when_free_stock_is_empty(): void
    {
        [$user, $sales, $customer, $kretek, $kopi] = $this->setUpSale(0, 5);
        $promo = $this->shellPromo($kretek, $kretek, 8, 1);

        try {
            $this->exchange($user, $sales, $customer, $promo, 8, [['product_id' => $kopi->id, 'quantity' => 1]]);
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shells', $e->errors());
        }

        $this->assertEquals(5, $this->qty($kopi, $sales));
    }

    public function test_shell_exchange_only_works_in_depos_where_it_is_active(): void
    {
        [$user, $sales, $customer, $kretek] = $this->setUpSale(10, null);
        $other = $this->makeBranch('CBG-LAIN', 'Depo Lain');
        $promo = $this->shellPromo($kretek, $kretek, 8, 1, [$other->id]);

        $this->expectException(ValidationException::class);
        $this->exchange($user, $sales, $customer, $promo, 8);
    }

    public function test_shell_exchange_program_does_not_trigger_on_purchase(): void
    {
        [$user, $sales, $customer, $kretek] = $this->setUpSale(10, null);
        $this->shellPromo($kretek, $kretek, 1, 1);

        $trx = $this->sell($user, $sales, $customer, $kretek, 3);

        $this->assertCount(1, $trx->items);
        $this->assertEquals(7, $this->qty($kretek, $sales));
    }

    public function test_admin_can_create_shell_exchange_program(): void
    {
        $admin = $this->makeAdminUser();
        $kretek = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.master.buy-get-promos.store'), [
            'name' => 'Tukar Cangkang Ori Kretek',
            'type' => 'shell_exchange',
            'buy_product_id' => $kretek->id,
            'buy_qty' => 8,
            'free_product_id' => $kretek->id,
            'free_qty' => 1,
            'is_active' => 1,
            'branch_ids' => [$this->defaultBranch()->id],
        ])->assertSessionHasNoErrors();

        $promo = BuyGetPromo::firstOrFail();
        $this->assertSame('shell_exchange', $promo->type);

        $this->actingAs($admin)->get(route('admin.master.buy-get-promos.index'))
            ->assertOk()->assertSee('Tukar Cangkang');
    }
}
