<?php

namespace Tests\Feature\Master;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sales;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\User;
use App\Models\Visit;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Branch (Depo) percobaan/tes: hapus biasa gagal karena data masih
 * terhubung; "Hapus + Semua Data" (purge) membersihkan semuanya.
 */
class BranchDeletionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function worldWithData(): array
    {
        $branch = $this->makeBranch('CBG-PURGE', 'Depo Tes');
        $superAdmin = $this->makeSuperAdminUser();
        $adminUser = $this->makeAdminUser($branch);
        [$salesUser, $sales] = $this->makeSalesUser($branch);
        $warehouse = $this->makeWarehouse($branch);
        $customer = $this->makeCustomer($sales->id, $branch);
        $product = $this->makeProduct(10000);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');

        Visit::create([
            'source' => Visit::SOURCE_SALES,
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => now(),
            'check_in_condition' => 'normal',
        ]);

        $trx = SalesTransaction::create([
            'code' => 'SO-PURGE-1',
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'status' => SalesTransaction::STATUS_COMPLETED,
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 0,
            'total' => 10000,
        ]);

        $invoice = Invoice::create([
            'code' => 'INV-PURGE-1',
            'sales_transaction_id' => $trx->id,
            'customer_id' => $customer->id,
            'sales_id' => $sales->id,
            'date' => now()->toDateString(),
            'subtotal' => 10000,
            'discount' => 0,
            'grand_total' => 10000,
            'paid_amount' => 10000,
            'status' => Invoice::STATUS_PAID,
        ]);

        Payment::create([
            'code' => 'PAY-PURGE-1', 'invoice_id' => $invoice->id, 'amount' => 10000,
            'method' => Payment::METHOD_CASH, 'paid_at' => now()->toDateString(),
        ]);

        return compact('branch', 'superAdmin', 'adminUser', 'salesUser', 'sales', 'warehouse', 'customer');
    }

    public function test_ordinary_delete_is_blocked_while_data_exists(): void
    {
        ['branch' => $branch, 'superAdmin' => $superAdmin] = $this->worldWithData();

        $this->actingAs($superAdmin)
            ->delete(route('admin.master.branches.destroy', $branch))
            ->assertRedirect(route('admin.master.branches.index'))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull($branch->fresh());
    }

    public function test_purge_requires_exact_code(): void
    {
        $world = $this->worldWithData();
        $branch = $world['branch'];
        $superAdmin = $world['superAdmin'];
        $salesId = $world['sales']->id;

        $this->actingAs($superAdmin)
            ->delete(route('admin.master.branches.purge', $branch), ['confirm_code' => 'SALAH'])
            ->assertSessionHasErrors('confirm_code');

        $this->assertNotNull($branch->fresh());
        $this->assertNotNull(Sales::find($salesId));
    }

    public function test_purge_deletes_branch_and_everything_under_it(): void
    {
        $world = $this->worldWithData();
        $branch = $world['branch'];
        $superAdmin = $world['superAdmin'];
        $salesId = $world['sales']->id;
        $customerId = $world['customer']->id;
        $warehouseId = $world['warehouse']->id;
        $adminUserId = $world['adminUser']->id;
        $salesUserId = $world['salesUser']->id;

        $this->actingAs($superAdmin)
            ->delete(route('admin.master.branches.purge', $branch), ['confirm_code' => 'CBG-PURGE'])
            ->assertRedirect(route('admin.master.branches.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull(Branch::find($branch->id));
        $this->assertNull(Warehouse::find($warehouseId));
        $this->assertNull(Sales::find($salesId));
        $this->assertNull(Customer::find($customerId));
        $this->assertNull(User::find($adminUserId));
        $this->assertNull(User::find($salesUserId));
        $this->assertSame(0, SalesTransaction::where('sales_id', $salesId)->count());
        $this->assertSame(0, Invoice::where('sales_id', $salesId)->count());
        $this->assertSame(0, Payment::query()->whereHas('invoice', fn ($q) => $q->where('sales_id', $salesId))->count());
        $this->assertSame(0, Visit::where('sales_id', $salesId)->count());
        $this->assertSame(0, Stock::where('location_type', Stock::LOCATION_SALES)->where('location_id', $salesId)->count());

        // Super Admin & branch lain tidak ikut terhapus.
        $this->assertNotNull(User::find($superAdmin->id));
    }
}
