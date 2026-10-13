<?php

namespace Tests\Feature\Sales;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Admin bisa mengoreksi Settlement (Applied/Draft): kaitkan pembayaran yang
 * dicatat manual di Invoice, lepas yang salah, ubah uang disetor/catatan.
 */
class SettlementCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function invoice($sales, float $total = 324000): Invoice
    {
        static $n = 0;
        $n++;

        $trx = SalesTransaction::create([
            'code' => "TRX-COR-{$n}", 'sales_id' => $sales->id, 'branch_id' => $sales->branch_id,
            'subtotal' => $total, 'discount' => 0, 'tax' => 0, 'total' => $total,
            'status' => SalesTransaction::STATUS_COMPLETED, 'source' => SalesTransaction::SOURCE_SALES,
        ]);

        return Invoice::create([
            'sales_transaction_id' => $trx->id,
            'code' => "INV-COR-{$n}", 'sales_id' => $sales->id, 'branch_id' => $sales->branch_id,
            'consumer_name' => 'Umum', 'date' => now()->toDateString(),
            'subtotal' => $total, 'discount' => 0, 'tax' => 0, 'grand_total' => $total,
            'paid_amount' => 0, 'status' => Invoice::STATUS_UNPAID,
        ]);
    }

    private function payment(Invoice $invoice, float $amount, ?int $settlementId = null, string $method = Payment::METHOD_CASH): Payment
    {
        static $n = 0;
        $n++;

        return Payment::create([
            'code' => "PAY-COR-{$n}", 'invoice_id' => $invoice->id, 'amount' => $amount,
            'method' => $method, 'paid_at' => now()->toDateString(), 'settlement_id' => $settlementId,
        ]);
    }

    private function appliedSettlement($sales, float $expected, float $deposited): Settlement
    {
        static $n = 0;
        $n++;

        return Settlement::create([
            'code' => "STL-COR-{$n}", 'sales_id' => $sales->id, 'warehouse_id' => $this->makeWarehouse($sales->branch)->id,
            'settled_at' => now()->subDay()->toDateString(),
            'cash_expected' => $expected, 'cash_deposited' => $deposited, 'cash_variance' => $deposited - $expected,
            'status' => Settlement::STATUS_APPLIED, 'applied_at' => now(),
        ]);
    }

    public function test_admin_attaches_manual_payment_and_variance_is_recalculated(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);

        // Sales lupa catat; Admin mencatat manual setelah Settlement di-Apply.
        $stl = $this->appliedSettlement($sales, 0, 324000);
        $manual = $this->payment($this->invoice($sales), 324000);

        $this->actingAs($admin)->get(route('admin.finance.settlements.correct', $stl))
            ->assertOk()->assertSee($manual->code);

        $this->actingAs($admin)->post(route('admin.finance.settlements.correct.store', $stl), [
            'cash_deposited' => 324000,
            'notes' => 'Dicatat manual oleh Admin',
            'attach' => [$manual->id],
        ])->assertRedirect(route('admin.finance.settlements.index'));

        $stl->refresh();
        $this->assertEquals(324000, $stl->cash_expected);
        $this->assertEquals(324000, $stl->cash_deposited);
        $this->assertEquals(0, $stl->cash_variance);
        $this->assertSame('Dicatat manual oleh Admin', $stl->notes);
        $this->assertSame(Settlement::STATUS_APPLIED, $stl->status);
        $this->assertSame($stl->id, $manual->fresh()->settlement_id);
    }

    public function test_admin_can_detach_payment_and_edit_deposit(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $stl = $this->appliedSettlement($sales, 50000, 50000);
        $a = $this->payment($this->invoice($sales), 30000, $stl->id);
        $b = $this->payment($this->invoice($sales), 20000, $stl->id);

        $this->actingAs($admin)->post(route('admin.finance.settlements.correct.store', $stl), [
            'cash_deposited' => 30000,
            'detach' => [$b->id],
        ])->assertSessionHasNoErrors();

        $stl->refresh();
        $this->assertEquals(30000, $stl->cash_expected);
        $this->assertEquals(0, $stl->cash_variance);
        $this->assertNull($b->fresh()->settlement_id);
        $this->assertSame($stl->id, $a->fresh()->settlement_id);
    }

    public function test_cannot_attach_payment_of_other_sales_other_settlement_or_non_cash(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $other = $this->makeSales(null, $admin->branch);
        $stl = $this->appliedSettlement($sales, 0, 0);
        $otherStl = $this->appliedSettlement($other, 0, 0);

        $foreign = $this->payment($this->invoice($other), 1000);
        $settled = $this->payment($this->invoice($sales), 1000, $otherStl->id);
        $transfer = $this->payment($this->invoice($sales), 1000, null, Payment::METHOD_TRANSFER);

        foreach ([$foreign, $settled, $transfer] as $bad) {
            $this->actingAs($admin)->post(route('admin.finance.settlements.correct.store', $stl), [
                'cash_deposited' => 0, 'attach' => [$bad->id],
            ])->assertSessionHasErrors('attach');
        }

        $this->assertEquals(0, $stl->fresh()->cash_expected);
    }

    public function test_admin_of_other_branch_cannot_correct(): void
    {
        $branchB = $this->makeBranch('CBG-COR', 'Depo COR');
        $sales = $this->makeSales(null, $branchB);
        $stl = $this->appliedSettlement($sales, 0, 0);

        $admin = $this->makeAdminUser($this->defaultBranch());

        $this->actingAs($admin)->get(route('admin.finance.settlements.correct', $stl))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.finance.settlements.correct.store', $stl), ['cash_deposited' => 5])->assertForbidden();
    }

    public function test_admin_can_generate_draft_for_sales_with_unsettled_cash(): void
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $this->makeWarehouse($admin->branch);
        $pay = $this->payment($this->invoice($sales, 50000), 50000);

        // Muncul di daftar "Menunggu Settlement".
        $res = $this->actingAs($admin)->get(route('admin.finance.settlements.index'))->assertOk();
        $this->assertSame(1, $res->viewData('pending')->count());
        $this->assertEquals(50000, $res->viewData('pending')->first()['cash']);

        $this->actingAs($admin)->post(route('admin.finance.settlements.generate', $sales))
            ->assertRedirect();

        $stl = Settlement::where('sales_id', $sales->id)->firstOrFail();
        $this->assertSame(Settlement::STATUS_DRAFT, $stl->status);
        $this->assertEquals(50000, $stl->cash_expected);
        $this->assertSame($stl->id, $pay->fresh()->settlement_id);

        // Tidak lagi di daftar menunggu; membuat lagi ditolak.
        $res = $this->actingAs($admin)->get(route('admin.finance.settlements.index'))->assertOk();
        $this->assertSame(0, $res->viewData('pending')->count());
        $this->actingAs($admin)->post(route('admin.finance.settlements.generate', $sales))->assertSessionHasErrors('sales_id');
    }

    public function test_generate_is_forbidden_for_other_branch_and_rejected_when_nothing_pending(): void
    {
        $branchB = $this->makeBranch('CBG-GEN', 'Depo GEN');
        $salesB = $this->makeSales(null, $branchB);
        $this->payment($this->invoice($salesB), 1000);
        $admin = $this->makeAdminUser($this->defaultBranch());

        $this->actingAs($admin)->post(route('admin.finance.settlements.generate', $salesB))->assertForbidden();

        $own = $this->makeSales(null, $admin->branch);
        $this->actingAs($admin)->post(route('admin.finance.settlements.generate', $own))->assertSessionHasErrors('sales_id');
    }
}
