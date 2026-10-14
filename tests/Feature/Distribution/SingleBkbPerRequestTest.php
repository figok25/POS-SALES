<?php

namespace Tests\Feature\Distribution;

use App\Models\BkbDistribusi;
use App\Models\Stock;
use App\Models\StockRequest;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Satu Permintaan Barang hanya boleh dibuatkan satu BKB, dan satu BKB hanya
 * bisa di-Apply satu kali. Mau kirim lagi = buat Permintaan Barang baru.
 */
class SingleBkbPerRequestTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function setUpRequest(string $status = StockRequest::STATUS_SUBMITTED): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $warehouse = $this->makeWarehouse();
        $product = $this->makeProduct();

        app(StockService::class)->increase($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id, 100, 'adjustment_in');

        $request = StockRequest::create([
            'code' => 'REQ-T'.StockRequest::count(),
            'warehouse_id' => $warehouse->id,
            'sales_id' => $sales->id,
            'status' => $status,
        ]);
        $request->items()->create(['product_id' => $product->id, 'quantity' => 10]);

        return [$admin, $sales, $warehouse, $product, $request];
    }

    private function payload($sales, $warehouse, $product, $request, float $qty = 10): array
    {
        return [
            'stock_request_id' => $request->id,
            'warehouse_id' => $warehouse->id,
            'sales_id' => $sales->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ];
    }

    public function test_second_bkb_for_same_request_is_rejected(): void
    {
        [$admin, $sales, $warehouse, $product, $request] = $this->setUpRequest();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, BkbDistribusi::count());

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request))
            ->assertSessionHasErrors('stock_request_id');
        $this->assertSame(1, BkbDistribusi::count());

        // Form pembuatan dari Permintaan yang sudah dipakai diarahkan kembali.
        $this->actingAs($admin)->get(route('admin.distribution.bkb.create', ['stock_request_id' => $request->id]))
            ->assertRedirect(route('admin.distribution.stock-requests.show', $request));
    }

    public function test_request_must_be_submitted(): void
    {
        [$admin, $sales, $warehouse, $product, $request] = $this->setUpRequest(StockRequest::STATUS_DRAFT);

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request))
            ->assertSessionHasErrors('stock_request_id');
        $this->assertSame(0, BkbDistribusi::count());
    }

    public function test_cancelled_bkb_frees_the_request_for_a_new_bkb(): void
    {
        [$admin, $sales, $warehouse, $product, $request] = $this->setUpRequest();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request));
        $first = BkbDistribusi::firstOrFail();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.cancel', $first))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, BkbDistribusi::count());
    }

    public function test_bkb_can_only_be_applied_once(): void
    {
        [$admin, $sales, $warehouse, $product, $request] = $this->setUpRequest();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request));
        $bkb = BkbDistribusi::firstOrFail();
        $stocks = app(StockService::class);

        $this->actingAs($admin)->post(route('admin.distribution.bkb.apply', $bkb))->assertSessionHas('status');
        $this->assertEquals(90, $stocks->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertEquals(10, $stocks->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));

        $this->actingAs($admin)->post(route('admin.distribution.bkb.apply', $bkb))->assertSessionHas('error');
        $this->assertEquals(90, $stocks->getQuantity($product->id, Stock::LOCATION_WAREHOUSE, $warehouse->id));
        $this->assertEquals(10, $stocks->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_second_draft_cannot_be_applied_when_request_already_has_applied_bkb(): void
    {
        [$admin, $sales, $warehouse, $product, $request] = $this->setUpRequest();

        // Data lama: dua BKB draft dari Permintaan yang sama.
        $make = fn () => tap(BkbDistribusi::create([
            'code' => 'BKB-T'.BkbDistribusi::count(), 'stock_request_id' => $request->id,
            'warehouse_id' => $warehouse->id, 'sales_id' => $sales->id, 'status' => BkbDistribusi::STATUS_DRAFT,
        ]), fn ($b) => $b->items()->create(['product_id' => $product->id, 'quantity' => 5]));
        $a = $make();
        $b = $make();

        $this->actingAs($admin)->post(route('admin.distribution.bkb.apply', $a))->assertSessionHas('status');
        $this->actingAs($admin)->post(route('admin.distribution.bkb.apply', $b))->assertSessionHas('error');

        $this->assertEquals(5, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
        $this->assertTrue($b->fresh()->isDraft());
    }

    public function test_stock_request_page_links_to_existing_bkb_instead_of_create_button(): void
    {
        [$admin, $sales, $warehouse, $product, $request] = $this->setUpRequest();

        $this->actingAs($admin)->get(route('admin.distribution.stock-requests.show', $request))
            ->assertOk()->assertSee('Buat BKB Distribusi');

        $this->actingAs($admin)->post(route('admin.distribution.bkb.store'), $this->payload($sales, $warehouse, $product, $request));

        $this->actingAs($admin)->get(route('admin.distribution.stock-requests.show', $request))
            ->assertOk()->assertDontSee('Buat BKB Distribusi')->assertSee(BkbDistribusi::firstOrFail()->code);
    }
}
