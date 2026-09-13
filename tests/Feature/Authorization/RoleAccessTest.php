<?php

namespace Tests\Feature\Authorization;

use App\Models\Stock;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 6 Hardening - Test Authorization (Blueprint #42 Definition of
 * Done: "Testing wajib untuk ... authorization"; Blueprint #38: Sales
 * hanya boleh mengakses data miliknya sendiri).
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_guest_is_redirected_to_login_for_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_sales_cannot_access_admin_dashboard(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_sales_dashboard(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->get(route('sales.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_redirects_admin_to_admin_dashboard(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_redirects_sales_to_sales_dashboard(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('sales.dashboard'));
    }

    public function test_sales_cannot_view_another_sales_transaction(): void
    {
        [$userA, $salesA] = $this->makeSalesUser();
        [$userB, $salesB] = $this->makeSalesUser();
        $customer = $this->makeCustomer($salesB->id);
        $product = $this->makeProduct();

        app(StockService::class)->increase(
            $product->id, Stock::LOCATION_SALES, $salesB->id, 10, 'bkb_apply'
        );

        $trx = app(SalesTransactionService::class)->create($salesB->id, $userB->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        // Sales A mencoba lihat transaksi milik Sales B -> harus 403.
        $this->actingAs($userA)
            ->get(route('sales.transactions.show', $trx))
            ->assertForbidden();
    }

    public function test_customer_assignment_page_requires_permission(): void
    {
        [$user] = $this->makeSalesUser();

        // Sales tidak punya permission customer-assignment.view/manage,
        // jadi rute admin ini harus tertutup untuknya juga lewat role:admin.
        $this->actingAs($user)
            ->get(route('admin.sales.customer-assignments.index'))
            ->assertForbidden();
    }
}
