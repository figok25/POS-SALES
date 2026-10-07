<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\User;
use App\Services\SalesTransactionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Nota/struk/invoice harus mencetak NAMA dan ALAMAT toko dengan jelas di
 * semua format: struk Bluetooth (payload cetak Sales App), struk Admin,
 * nota A4, dan invoice.
 */
class ReceiptStoreInfoTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const ADDRESS = 'Jl. Melati No 7 Surabaya';

    private User $salesUser;

    private Customer $customer;

    private SalesTransaction $trx;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->salesUser, $sales] = $this->makeSalesUser();
        $this->customer = $this->makeCustomer($sales->id);
        $this->customer->update(['address' => self::ADDRESS]);

        $product = $this->makeProduct(5000);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 5, 'bkb_apply');

        $this->trx = app(SalesTransactionService::class)->create($sales->id, $this->salesUser->id, [
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);
    }

    public function test_sales_print_payload_contains_store_name_and_address(): void
    {
        $this->actingAs($this->salesUser)
            ->get(route('sales.transactions.show', $this->trx))
            ->assertOk()
            ->assertSee('customer_name', false)
            ->assertSee('customer_address', false)
            ->assertSee($this->customer->name)
            ->assertSee(self::ADDRESS);
    }

    public function test_sales_thermal_receipt_prints_store_block(): void
    {
        // Kode cetak ESC/POS (JavaScript) memakai blok "Toko:" + alamat.
        $this->actingAs($this->salesUser)
            ->get(route('sales.transactions.show', $this->trx))
            ->assertOk()
            ->assertSee("b.line('Toko:')", false)
            ->assertSee('payload.customer_address', false)
            ->assertSee("normalize('NFD')", false);
    }

    public function test_admin_receipt_struk_shows_store_name_and_address(): void
    {
        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.sales.transactions.print', ['transaction' => $this->trx, 'format' => 'struk']))
            ->assertOk()
            ->assertSee('Toko:')
            ->assertSee($this->customer->name)
            ->assertSee(self::ADDRESS);
    }

    public function test_admin_receipt_a4_shows_store_name_and_address(): void
    {
        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.sales.transactions.print', ['transaction' => $this->trx, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('Toko (Kepada Yth)')
            ->assertSee($this->customer->name)
            ->assertSee(self::ADDRESS);
    }

    public function test_invoice_struk_and_a4_show_store_name_and_address(): void
    {
        $admin = $this->makeAdminUser();
        $invoice = $this->trx->invoice;

        $this->actingAs($admin)
            ->get(route('admin.sales.invoices.print', ['invoice' => $invoice, 'format' => 'struk']))
            ->assertOk()
            ->assertSee('Toko:')
            ->assertSee($this->customer->name)
            ->assertSee(self::ADDRESS);

        $this->actingAs($admin)
            ->get(route('admin.sales.invoices.print', ['invoice' => $invoice, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('Toko (Kepada Yth)')
            ->assertSee($this->customer->name)
            ->assertSee(self::ADDRESS);
    }

    public function test_receipt_still_renders_when_store_has_no_address(): void
    {
        $this->customer->update(['address' => null]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.sales.transactions.print', ['transaction' => $this->trx, 'format' => 'struk']))
            ->assertOk()
            ->assertSee($this->customer->name);
    }
}
