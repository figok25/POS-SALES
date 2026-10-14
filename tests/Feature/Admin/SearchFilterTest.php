<?php

namespace Tests\Feature\Admin;

use App\Support\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Pencarian tidak peka huruf besar/kecil & spasi, urutan kata bebas, dan
 * tidak menembus filter Depo.
 */
class SearchFilterTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_words_are_trimmed_and_collapsed(): void
    {
        $this->assertSame(['toko', 'maju'], Search::words("  toko    maju \n"));
        $this->assertSame([], Search::words('   '));
        $this->assertSame([], Search::words(null));
    }

    public function test_customer_assignment_search_ignores_case_and_word_order(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $match = $this->makeCustomer($sales->id);
        $match->update(['name' => 'Toko Maju Jaya']);
        $other = $this->makeCustomer($sales->id);
        $other->update(['name' => 'Warung Sejahtera']);

        foreach (['toko maju', 'MAJU TOKO', '  maju   jaya ', 'TOKO MAJU JAYA'] as $q) {
            $this->actingAs($admin)->get(route('admin.sales.customer-assignments.index', ['q' => $q]))
                ->assertOk()->assertSee('Toko Maju Jaya')->assertDontSee('Warung Sejahtera');
        }
    }

    public function test_search_does_not_leak_customers_from_other_depo(): void
    {
        $adminA = $this->makeAdminUser();
        $branchB = $this->makeBranch('CBG-B', 'Depo B');
        [, $salesB] = $this->makeSalesUser($branchB);
        $foreign = $this->makeCustomer($salesB->id, $branchB);
        $foreign->update(['name' => 'Toko Maju Depo B', 'code' => 'ZZ-MAJU-B']);

        $this->actingAs($adminA)->get(route('admin.sales.customer-assignments.index', ['q' => 'maju']))
            ->assertOk()->assertDontSee('Toko Maju Depo B');
    }
}
