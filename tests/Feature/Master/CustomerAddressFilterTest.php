<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Filter "Alamat / Daerah" pada Master Data Customer, termasuk dipakai
 * oleh aksi massal "Pilih semua hasil filter" (pindah Sales sekaligus).
 */
class CustomerAddressFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_filters_customer_list_by_address_case_insensitive(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Dekat', 'address' => 'Jl. Merdeka 5, Sananwetan, Blitar']);
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Jauh', 'address' => 'Jl. Raya Kediri 9, Kediri']);

        $this->actingAs($admin)->get(route('admin.master.customers.index', ['address' => 'SANANWETAN']))
            ->assertOk()
            ->assertSee('Toko Dekat')
            ->assertDontSee('Toko Jauh');
    }

    public function test_address_words_can_be_in_any_order(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Dekat', 'address' => 'Jl. Merdeka 5, Sananwetan, Blitar']);
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Jauh', 'address' => 'Jl. Merdeka 7, Kediri']);

        $this->actingAs($admin)->get(route('admin.master.customers.index', ['address' => 'blitar merdeka']))
            ->assertOk()
            ->assertSee('Toko Dekat')
            ->assertDontSee('Toko Jauh');
    }

    public function test_bulk_reassign_with_select_all_filtered_only_moves_matching_address(): void
    {
        $admin = $this->makeAdminUser();
        [, $from] = $this->makeSalesUser();
        [, $to] = $this->makeSalesUser();

        $inArea1 = $this->makeCustomer($from->id);
        $inArea1->update(['address' => 'Jl. A, Sananwetan']);
        $inArea2 = $this->makeCustomer($from->id);
        $inArea2->update(['address' => 'Jl. B, Sananwetan']);
        $outside = $this->makeCustomer($from->id);
        $outside->update(['address' => 'Jl. C, Kepanjenkidul']);

        $this->actingAs($admin)->post(route('admin.master.customers.bulk-reassign'), [
            'select_all_filtered' => 1,
            'address' => 'sananwetan',
            'target_sales_id' => $to->id,
            'reason' => 'Rute baru',
        ])->assertRedirect();

        $this->assertSame($to->id, $inArea1->fresh()->sales_id);
        $this->assertSame($to->id, $inArea2->fresh()->sales_id);
        $this->assertSame($from->id, $outside->fresh()->sales_id);
    }

    public function test_address_filter_is_passed_to_export(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Dekat', 'address' => 'Sananwetan']);
        $this->makeCustomer($sales->id)->update(['name' => 'Toko Jauh', 'address' => 'Kediri']);

        $response = $this->actingAs($admin)->get(route('admin.master.customers.export', ['address' => 'sananwetan']))
            ->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'cust').'.xlsx';
        file_put_contents($path, $response->getContent());
        $names = array_column(IOFactory::load($path)->getActiveSheet()->toArray(), 1);
        @unlink($path);

        $this->assertContains('Toko Dekat', $names);
        $this->assertNotContains('Toko Jauh', $names);
    }
}
