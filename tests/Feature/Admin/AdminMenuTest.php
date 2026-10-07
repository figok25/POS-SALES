<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Sidebar admin: tiap menu hanya muncul SEKALI (tidak ganda antar grup).
 * Sales, Customer & Vehicle dikelola di Master Data; Invoice di Finance.
 */
class AdminMenuTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function sidebar(): string
    {
        $html = $this->actingAs($this->makeAdminUser())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/<nav class="adm-nav.*?<\/nav>/s', $html, $m), 'Sidebar tidak ditemukan.');

        return $m[0];
    }

    public function test_each_menu_item_appears_only_once_in_the_sidebar(): void
    {
        $nav = $this->sidebar();

        foreach ([
            'admin.master.sales.index',
            'admin.master.customers.index',
            'admin.master.vehicles.index',
            'admin.sales.invoices.index',
        ] as $routeName) {
            $this->assertSame(
                1,
                substr_count($nav, 'href="'.route($routeName).'"'),
                "Menu {$routeName} harus muncul tepat sekali di sidebar."
            );
        }
    }

    public function test_sales_group_no_longer_repeats_master_data_menus(): void
    {
        $nav = $this->sidebar();

        $this->assertStringNotContainsString('Sales Management', $nav);

        // Menu khas modul Sales yang memang harus tetap ada.
        foreach (['Customer Assignment', 'Visit Plan (Rute Kanvas)', 'Tagging Toko', 'Transaksi Penjualan'] as $label) {
            $this->assertStringContainsString($label, $nav);
        }
    }
}
