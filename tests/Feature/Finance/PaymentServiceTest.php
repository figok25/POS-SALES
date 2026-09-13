<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Stock;
use App\Services\PaymentService;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 7 - Test untuk PaymentService (Blueprint #15).
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    protected function makeInvoice(float $price, float $qty): Invoice
    {
        [$user, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $product = $this->makeProduct($price);

        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, $qty + 10, 'bkb_apply');

        $trx = app(SalesTransactionService::class)->create($sales->id, $user->id, [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ]);

        return $trx->invoice;
    }

    public function test_partial_payment_sets_invoice_status_to_partial(): void
    {
        $invoice = $this->makeInvoice(10000, 10); // grand_total = 100.000
        $admin = $this->makeAdminUser();

        $payment = app(PaymentService::class)->create($invoice, [
            'amount' => 40000,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ], $admin->id);

        $invoice->refresh();
        $this->assertEquals(40000, $invoice->paid_amount);
        $this->assertEquals(Invoice::STATUS_PARTIAL, $invoice->status);
        $this->assertEquals(60000, $invoice->outstanding());
        $this->assertEquals(40000, (float) $payment->amount);
    }

    public function test_full_payment_sets_invoice_status_to_paid(): void
    {
        $invoice = $this->makeInvoice(10000, 10); // 100.000

        app(PaymentService::class)->create($invoice, [
            'amount' => 100000,
            'method' => 'transfer',
        ], null);

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertEquals(0, $invoice->outstanding());
        $this->assertTrue($invoice->isFullyPaid());
    }

    public function test_two_partial_payments_accumulate_to_paid(): void
    {
        $invoice = $this->makeInvoice(10000, 10); // 100.000
        $service = app(PaymentService::class);

        $service->create($invoice, ['amount' => 30000, 'method' => 'cash'], null);
        $service->create($invoice, ['amount' => 70000, 'method' => 'cash'], null);

        $invoice->refresh();
        $this->assertEquals(100000, $invoice->paid_amount);
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_payment_exceeding_outstanding_is_rejected(): void
    {
        $invoice = $this->makeInvoice(10000, 10); // 100.000

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->create($invoice, [
            'amount' => 150000,
            'method' => 'cash',
        ], null);
    }

    public function test_zero_amount_payment_is_rejected(): void
    {
        $invoice = $this->makeInvoice(10000, 10);

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->create($invoice, [
            'amount' => 0,
            'method' => 'cash',
        ], null);
    }

    public function test_payment_writes_audit_log(): void
    {
        $invoice = $this->makeInvoice(10000, 5);
        $admin = $this->makeAdminUser();

        app(PaymentService::class)->create($invoice, ['amount' => 10000, 'method' => 'cash'], $admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'module' => 'Finance',
            'document_type' => Payment::class,
            'user_id' => $admin->id,
        ]);
    }
}
