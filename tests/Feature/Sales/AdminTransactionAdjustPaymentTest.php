<?php

namespace Tests\Feature\Sales;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Price;
use App\Models\SalesTransaction;
use App\Models\Settlement;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Edit transaksi: total baru < pembayaran masuk -> opsi "Sesuaikan pembayaran ke total baru".
 */
class AdminTransactionAdjustPaymentTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function world(): array
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct(10000);
        Price::where('product_id', $product->id)->update(['price_type' => Price::TYPE_RETAIL]);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 20, 'bkb_apply');

        $this->actingAs($admin)->post(route('admin.sales.transactions.store-for-sales'), [
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'transaction_date' => now()->toDateString(), 'entry_reason' => 'Sales lupa input',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 10000]],
        ])->assertSessionHasNoErrors();

        $trx = SalesTransaction::latest('id')->first();
        $invoice = Invoice::where('sales_transaction_id', $trx->id)->firstOrFail();

        return [$admin, $trx, $product, $invoice];
    }

    private function pay(Invoice $invoice, string $code, float $amount, ?int $settlementId = null): Payment
    {
        $p = Payment::create([
            'code' => $code, 'invoice_id' => $invoice->id, 'amount' => $amount,
            'method' => Payment::METHOD_CASH, 'paid_at' => now()->toDateString(), 'settlement_id' => $settlementId,
        ]);
        $invoice->update(['paid_amount' => (float) $invoice->paid_amount + $amount]);

        return $p;
    }

    private function update($admin, SalesTransaction $trx, $product, array $extra = [])
    {
        return $this->actingAs($admin)->put(route('admin.sales.transactions.update', $trx), $extra + [
            'reason' => 'Harga salah',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 7300]],
        ]);
    }

    public function test_blocked_without_the_option(): void
    {
        [$admin, $trx, $product, $invoice] = $this->world();
        $this->pay($invoice, 'PAY-A1', 20000);

        $this->update($admin, $trx, $product)->assertSessionHasErrors('items');

        $this->assertEquals(20000, $invoice->fresh()->paid_amount);
        $this->assertEquals(20000, $trx->fresh()->total);
    }

    public function test_option_shrinks_newest_payment_first_and_logs_it(): void
    {
        [$admin, $trx, $product, $invoice] = $this->world();
        $old = $this->pay($invoice, 'PAY-B1', 15000);
        $new = $this->pay($invoice, 'PAY-B2', 5000);

        // total baru 14.600, terbayar 20.000 -> kurangi 5.400: B2 (5.000) habis, B1 15.000 -> 14.600
        $this->update($admin, $trx, $product, ['adjust_payment' => 1])->assertSessionHasNoErrors();

        $this->assertEquals(14600, $trx->fresh()->total);
        $invoice->refresh();
        $this->assertEquals(14600, $invoice->paid_amount);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertNull(Payment::find($new->id));
        $this->assertEquals(14600, Payment::find($old->id)->amount);

        $log = AuditLog::where('action', 'revise')->latest('id')->first();
        $this->assertNotEmpty($log->after['payment_adjustment']);
        $this->assertSame('Harga salah', $log->after['reason']);
    }

    public function test_settled_payment_is_never_touched(): void
    {
        [$admin, $trx, $product, $invoice] = $this->world();
        [, $sales] = [null, $trx->sales];
        $stl = Settlement::create([
            'code' => 'STL-ADJ-1', 'sales_id' => $trx->sales_id, 'warehouse_id' => $this->makeWarehouse($sales->branch)->id,
            'settled_at' => now()->toDateString(), 'cash_expected' => 20000, 'cash_deposited' => 20000, 'cash_variance' => 0,
            'status' => Settlement::STATUS_APPLIED, 'applied_at' => now(),
        ]);
        $settled = $this->pay($invoice, 'PAY-C1', 20000, $stl->id);

        $this->update($admin, $trx, $product, ['adjust_payment' => 1])->assertSessionHasErrors('items');

        $this->assertEquals(20000, $settled->fresh()->amount);
        $this->assertEquals(20000, $trx->fresh()->total);
    }

    public function test_edit_page_shows_option_when_payments_exist(): void
    {
        [$admin, $trx, , $invoice] = $this->world();
        $this->pay($invoice, 'PAY-D1', 5000);

        $this->actingAs($admin)->get(route('admin.sales.transactions.edit', $trx))
            ->assertOk()->assertSee('name="adjust_payment"', false);
    }
}
