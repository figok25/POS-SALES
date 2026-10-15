<?php

namespace Tests\Feature\Sales;

use App\Models\CashLedger;
use App\Models\Invoice;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Harga Tester (penjualan Depo): selalu Rp 0, hanya memotong stok Gudang Depo.
 */
class DepoTesterPriceTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function setup3(float $stock = 10): array
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, $stock, 'adjustment_in');

        return [$admin, $warehouse, $product];
    }

    private function payload($warehouse, $product, float $qty, array $extra = []): array
    {
        return array_merge([
            'warehouse_id' => $warehouse->id,
            'price_type' => Price::TYPE_TESTER,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ], $extra);
    }

    public function test_tester_sale_costs_zero_and_only_reduces_depo_stock(): void
    {
        [$admin, $warehouse, $product] = $this->setup3(10);
        // Sengaja TANPA harga Tester di master & tanpa harga lain.

        $this->actingAs($admin)->post(route('admin.depo.store'), $this->payload($warehouse, $product, 3, ['consumer_name' => 'Uji rasa']))
            ->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame(Price::TYPE_TESTER, $trx->price_type);
        $this->assertSame(0.0, (float) $trx->total);
        $this->assertSame(0.0, (float) $trx->items->first()->price);
        $this->assertSame(3.0, (float) $trx->items->first()->quantity);
        $this->assertSame(7.0, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));

        $invoice = $trx->invoice;
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);   // tidak ada tagihan
        $this->assertSame(0, CashLedger::count());                    // tidak ada uang masuk

        $movement = StockMovement::where('movement_type', 'depo_tester')->first();
        $this->assertNotNull($movement);
        $this->assertSame(0, StockMovement::where('movement_type', 'depo_sale')->count());
    }

    public function test_tester_cannot_exceed_depo_stock(): void
    {
        [$admin, $warehouse, $product] = $this->setup3(2);

        $this->actingAs($admin)->post(route('admin.depo.store'), $this->payload($warehouse, $product, 5))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, SalesTransaction::count());
        $this->assertSame(2.0, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
    }

    public function test_tester_cannot_be_credit(): void
    {
        [$admin, $warehouse, $product] = $this->setup3(10);

        $this->actingAs($admin)->post(route('admin.depo.store'), $this->payload($warehouse, $product, 1, [
            'is_credit' => 1, 'consumer_name' => 'Budi',
        ]))->assertSessionHasErrors('is_credit');

        $this->assertSame(0, SalesTransaction::count());
    }

    public function test_depo_form_offers_tester_and_shows_zero_price(): void
    {
        [$admin, $warehouse, $product] = $this->setup3(10);

        $this->actingAs($admin)->get(route('admin.depo.create', ['warehouse_id' => $warehouse->id, 'price_type' => Price::TYPE_TESTER]))
            ->assertOk()
            ->assertSee('Harga Tester')
            ->assertSee($product->name);
    }

    public function test_tester_is_not_a_sales_type_or_master_price_category(): void
    {
        $this->assertArrayNotHasKey(Price::TYPE_TESTER, Price::types());
        $this->assertArrayNotHasKey(Price::TYPE_TESTER, Price::salesTypes());
        $this->assertSame('Tester', Price::typeLabel(Price::TYPE_TESTER));

        $admin = $this->makeSuperAdminUser();
        $product = $this->makeProduct();
        $this->actingAs($admin)->post(route('admin.master.prices.store'), [
            'product_id' => $product->id, 'name' => 'x', 'price_type' => Price::TYPE_TESTER, 'amount' => 100, 'is_active' => 1,
        ])->assertSessionHasErrors('price_type');
    }

    public function test_regular_consumer_sale_still_needs_master_price(): void
    {
        [$admin, $warehouse, $product] = $this->setup3(10);

        $this->actingAs($admin)->post(route('admin.depo.store'), $this->payload($warehouse, $product, 1, ['price_type' => Price::TYPE_CONSUMER]))
            ->assertSessionHasErrors('items');
    }
}
