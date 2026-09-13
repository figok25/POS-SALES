<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Services\CustomerAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 6 Hardening - Test untuk CustomerAssignmentService
 * (Blueprint baris #729: customer_assignments).
 */
class CustomerAssignmentTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_assign_sets_customer_sales_id_and_creates_history_row(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer();

        $assignment = app(CustomerAssignmentService::class)->assign($customer, $sales->id, $admin->id, 'Assignment awal');

        $customer->refresh();
        $this->assertEquals($sales->id, $customer->sales_id);
        $this->assertEquals($sales->id, $assignment->sales_id);
        $this->assertNull($assignment->unassigned_at);
        $this->assertEquals($admin->id, $assignment->assigned_by);

        $this->assertDatabaseCount('customer_assignments', 1);
    }

    public function test_reassign_closes_previous_assignment_and_opens_new_one(): void
    {
        $admin = $this->makeAdminUser();
        [, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $customer = $this->makeCustomer();
        $service = app(CustomerAssignmentService::class);

        $first = $service->assign($customer, $salesA->id, $admin->id, 'Assignment awal');
        $second = $service->assign($customer, $salesB->id, $admin->id, 'Sales A resign');

        $customer->refresh();
        $first->refresh();

        $this->assertEquals($salesB->id, $customer->sales_id);
        $this->assertNotNull($first->unassigned_at, 'Assignment lama harus ditutup (unassigned_at terisi)');
        $this->assertNull($second->unassigned_at);
        $this->assertDatabaseCount('customer_assignments', 2);
    }

    public function test_reassigning_to_the_same_sales_is_a_no_op(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer();
        $service = app(CustomerAssignmentService::class);

        $service->assign($customer, $sales->id, $admin->id);
        $service->assign($customer, $sales->id, $admin->id);

        // Tidak boleh membuat baris riwayat baru kalau Sales-nya sama.
        $this->assertDatabaseCount('customer_assignments', 1);
    }

    public function test_unassign_clears_sales_id_and_closes_current_assignment(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer();
        $service = app(CustomerAssignmentService::class);

        $assignment = $service->assign($customer, $sales->id, $admin->id);
        $service->unassign($customer, $admin->id, 'Sales resign, pengganti belum ada');

        $customer->refresh();
        $assignment->refresh();

        $this->assertNull($customer->sales_id);
        $this->assertNotNull($assignment->unassigned_at);
    }

    public function test_assign_writes_audit_log_entry(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer();

        app(CustomerAssignmentService::class)->assign($customer, $sales->id, $admin->id, 'Assignment awal');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assign',
            'module' => 'Sales',
            'document_type' => Customer::class,
            'document_id' => $customer->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_view_customer_assignment_index_and_edit_pages(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer();

        $this->actingAs($admin)
            ->get(route('admin.sales.customer-assignments.index'))
            ->assertOk()
            ->assertSee($customer->name);

        $this->actingAs($admin)
            ->get(route('admin.sales.customer-assignments.edit', $customer))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.sales.customer-assignments.update', $customer), [
                'sales_id' => $sales->id,
                'reason' => 'Test reassign lewat HTTP',
            ])
            ->assertRedirect(route('admin.sales.customer-assignments.index'));

        $this->assertEquals($sales->id, $customer->fresh()->sales_id);

        // Halaman detail Customer (Master Data) & halaman edit assignment
        // yang menampilkan riwayat juga harus render tanpa error.
        $this->actingAs($admin)
            ->get(route('admin.master.customers.show', $customer))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.sales.customer-assignments.edit', $customer))
            ->assertOk()
            ->assertSee('Test reassign lewat HTTP');
    }
}
