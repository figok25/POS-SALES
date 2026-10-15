<?php

namespace Tests\Feature\System;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\CustomerAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Audit Log: tombol "Pulihkan Data" untuk Customer yang terhapus tidak sengaja.
 */
class AuditRestoreCustomerTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    /** Hapus Customer lewat jalur resmi (menulis audit log 'delete'), kembalikan [log, id, code]. */
    private function deleteCustomerViaUi($admin, Customer $customer): array
    {
        $id = $customer->id;
        $code = $customer->code;

        $this->actingAs($admin)->delete(route('admin.master.customers.destroy', $customer))->assertSessionHasNoErrors();
        $this->assertNull(Customer::find($id));

        $log = AuditLog::where('action', 'delete')->where('document_type', Customer::class)->where('document_id', $id)->firstOrFail();

        return [$log, $id, $code];
    }

    public function test_deleted_customer_can_be_restored_with_same_id_code_and_sales_assignment(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        app(CustomerAssignmentService::class)->assign($customer, $sales->id, $admin->id, 'awal');

        [$log, $id, $code] = $this->deleteCustomerViaUi($admin, $customer);

        $this->actingAs($admin)->get(route('admin.system.audit-log.show', $log))
            ->assertOk()->assertSee('Pulihkan Data');

        $this->actingAs($admin)->post(route('admin.system.audit-log.restore', $log))
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $restored = Customer::findOrFail($id);
        $this->assertSame($code, $restored->code);
        $this->assertSame($sales->id, $restored->sales_id);
        $this->assertTrue($restored->assignments()->current()->where('sales_id', $sales->id)->exists());
        $this->assertTrue(AuditLog::where('action', 'restore')->where('document_id', $id)->exists());
    }

    public function test_restore_cannot_be_done_twice(): void
    {
        $admin = $this->makeAdminUser();
        $customer = $this->makeCustomer();
        [$log, $id] = $this->deleteCustomerViaUi($admin, $customer);

        $this->actingAs($admin)->post(route('admin.system.audit-log.restore', $log))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.system.audit-log.restore', $log))->assertSessionHasErrors('restore');

        $this->assertSame(1, Customer::where('id', $id)->count());
        $this->assertSame(1, AuditLog::where('action', 'restore')->where('document_id', $id)->count());
    }

    public function test_restore_is_blocked_when_code_is_taken_by_another_customer(): void
    {
        $admin = $this->makeAdminUser();
        $customer = $this->makeCustomer();
        [$log, $id, $code] = $this->deleteCustomerViaUi($admin, $customer);

        Customer::create(['branch_id' => $admin->branch_id, 'code' => $code, 'name' => 'Pengganti', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.system.audit-log.restore', $log))->assertSessionHasErrors('restore');
        $this->assertNull(Customer::find($id));
    }

    public function test_other_depo_admin_cannot_restore(): void
    {
        $admin = $this->makeAdminUser();
        $customer = $this->makeCustomer();
        [$log] = $this->deleteCustomerViaUi($admin, $customer);

        $otherAdmin = $this->makeAdminUser($this->makeBranch('CBG-R9', 'Depo R9'));

        $this->actingAs($otherAdmin)->post(route('admin.system.audit-log.restore', $log))->assertForbidden();
    }

    public function test_non_delete_logs_have_no_restore_panel(): void
    {
        $admin = $this->makeAdminUser();
        $log = AuditLog::create([
            'user_id' => $admin->id, 'branch_id' => $admin->branch_id, 'action' => 'update', 'module' => 'Master Data',
            'document_type' => Customer::class, 'document_id' => 1, 'before' => ['name' => 'A'], 'after' => ['name' => 'B'],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.system.audit-log.show', $log))->assertOk()->assertDontSee('Pulihkan Data');
        $this->actingAs($admin)->post(route('admin.system.audit-log.restore', $log))->assertSessionHasErrors('restore');
    }

    public function test_superadmin_can_restore_customer_of_any_depo(): void
    {
        $depo = $this->makeBranch('CBG-R8', 'Depo R8');
        $depoAdmin = $this->makeAdminUser($depo);
        $customer = $this->makeCustomer(null, $depo);
        [$log, $id] = $this->deleteCustomerViaUi($depoAdmin, $customer);

        $this->actingAs($this->makeSuperAdminUser())->post(route('admin.system.audit-log.restore', $log))
            ->assertSessionHasNoErrors();

        $this->assertSame($depo->id, Customer::findOrFail($id)->branch_id);
    }
}
