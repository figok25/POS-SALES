<?php

namespace Tests\Feature\Master;

use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Harga Distributor: kategori harga baru, KHUSUS transaksi Depo (kasir
 * Admin). Punya baris harga per produk di master seperti Retail/WS/Konsumen,
 * tapi SENGAJA tidak boleh jadi "Jenis Sales" lapangan.
 */
class DistributorPriceTypeTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_distributor_is_a_price_category_but_not_a_sales_type(): void
    {
        $this->assertArrayHasKey(Price::TYPE_DISTRIBUTOR, Price::types());
        $this->assertArrayHasKey(Price::TYPE_DISTRIBUTOR, Price::depoTypes());
        $this->assertArrayNotHasKey(Price::TYPE_DISTRIBUTOR, Price::salesTypes());
        $this->assertSame('Distributor', Price::typeLabel(Price::TYPE_DISTRIBUTOR));
    }

    public function test_admin_can_create_a_distributor_price_in_master(): void
    {
        $admin = $this->makeSuperAdminUser();
        $product = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.master.prices.store'), [
            'product_id' => $product->id,
            'name' => 'Distributor',
            'price_type' => Price::TYPE_DISTRIBUTOR,
            'amount' => 7500,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prices', [
            'product_id' => $product->id,
            'price_type' => Price::TYPE_DISTRIBUTOR,
            'amount' => 7500,
        ]);
    }

    public function test_admin_can_use_distributor_price_for_a_depo_sale(): void
    {
        $admin = $this->makeAdminUser();
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();
        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 10, 'adjustment_in');
        Price::where('product_id', $product->id)->update(['price_type' => Price::TYPE_RETAIL]);
        Price::create(['product_id' => $product->id, 'name' => 'Distributor', 'price_type' => Price::TYPE_DISTRIBUTOR, 'amount' => 7500, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.depo.create', ['warehouse_id' => $warehouse->id, 'price_type' => Price::TYPE_DISTRIBUTOR]))
            ->assertOk()
            ->assertSee('Distributor');

        $this->actingAs($admin)->post(route('admin.depo.store'), [
            'warehouse_id' => $warehouse->id,
            'price_type' => Price::TYPE_DISTRIBUTOR,
            'consumer_name' => 'Toko Agung',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 7500]],
        ])->assertSessionHasNoErrors();

        $trx = SalesTransaction::firstOrFail();
        $this->assertSame(Price::TYPE_DISTRIBUTOR, $trx->price_type);
        $this->assertSame(15000.0, (float) $trx->total);
    }

    public function test_distributor_cannot_be_assigned_as_a_sales_field_type(): void
    {
        $admin = $this->makeSuperAdminUser();
        $branch = $this->makeBranch();

        $this->actingAs($admin)->post(route('admin.master.sales.store'), [
            'branch_id' => $branch->id,
            'code' => 'SLS-DIST-01',
            'name' => 'Sales Uji Distributor',
            'type' => Price::TYPE_DISTRIBUTOR,
            'is_active' => 1,
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('sales', ['code' => 'SLS-DIST-01']);
    }
}
