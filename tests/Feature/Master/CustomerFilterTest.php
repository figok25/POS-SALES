<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Filter lengkap daftar Customer (Master Data): Sales, status
 * Aktif/Nonaktif, dan tanggal Customer dibuat ("tanggal tagging").
 */
class CustomerFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_filters_customer_list_by_sales(): void
    {
        $admin = $this->makeAdminUser();
        [, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $this->makeCustomer($salesA->id)->update(['name' => 'Toko Milik A']);
        $this->makeCustomer($salesB->id)->update(['name' => 'Toko Milik B']);

        $this->actingAs($admin)->get(route('admin.master.customers.index', ['sales_id' => $salesA->id]))
            ->assertOk()
            ->assertSee('Toko Milik A')
            ->assertDontSee('Toko Milik B');
    }

    public function test_filters_customer_list_by_status(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Aktif', 'is_active' => true]);
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Nonaktif', 'is_active' => false]);

        $this->actingAs($admin)->get(route('admin.master.customers.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Toko Aktif')
            ->assertDontSee('Toko Nonaktif');

        $this->actingAs($admin)->get(route('admin.master.customers.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Toko Nonaktif')
            ->assertDontSee('Toko Aktif');
    }

    public function test_filters_customer_list_by_created_date(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();

        $old = $this->makeCustomer($sales->id);
        $old->update(['name' => 'Toko Lama']);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $new = $this->makeCustomer($sales->id);
        $new->update(['name' => 'Toko Baru']);
        $new->forceFill(['created_at' => now()])->save();

        $this->actingAs($admin)->get(route('admin.master.customers.index', ['tag_date' => now()->subDays(10)->toDateString()]))
            ->assertOk()
            ->assertSee('Toko Lama')
            ->assertDontSee('Toko Baru');
    }

    public function test_filters_combine_with_search_and_can_be_reset(): void
    {
        $admin = $this->makeAdminUser();
        [, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $this->makeCustomer($salesA->id)->update(['name' => 'Toko Maju A']);
        $this->makeCustomer($salesB->id)->update(['name' => 'Toko Maju B']);

        // Sales A + kata kunci "maju" -> hanya Toko Maju A.
        $this->actingAs($admin)->get(route('admin.master.customers.index', ['sales_id' => $salesA->id, 'q' => 'maju']))
            ->assertOk()
            ->assertSee('Toko Maju A')
            ->assertDontSee('Toko Maju B');

        // Tanpa filter -> keduanya muncul.
        $this->actingAs($admin)->get(route('admin.master.customers.index'))
            ->assertOk()
            ->assertSee('Toko Maju A')
            ->assertSee('Toko Maju B');
    }
}
