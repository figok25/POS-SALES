<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\Visit;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Aturan kunjungan:
 *  - Kuota: SATU kunjungan per toko per hari. Toko Tutup tidak menghabiskan
 *    kuota: boleh SATU kunjungan ulang bila kunjungan sebelumnya Toko Tutup.
 *  - Transaksi di lapangan wajib sedang Check-in di toko yang sama (toko tutup
 *    tidak bisa bertransaksi).
 *  - Hasil kunjungan: Toko Tutup / Transaksi (EC) / Tanpa Transaksi (Call Made).
 */
class VisitQuotaTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -7.9797;
    private const LNG = 112.6304;

    /** @return array{0:\App\Models\User,1:\App\Models\Sales,2:Customer,3:Customer} */
    private function salesWithTwoStores(): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales);

        $a = $this->makeCustomer($sales->id);
        $a->forceFill(['name' => 'Toko Alfa Makmur', 'latitude' => self::LAT, 'longitude' => self::LNG])->save();

        $b = $this->makeCustomer($sales->id);
        $b->forceFill(['name' => 'Toko Beta Sentosa', 'latitude' => self::LAT, 'longitude' => self::LNG])->save();

        return [$user, $sales, $a, $b];
    }

    private function checkIn($user, Customer $customer, string $condition = Visit::CONDITION_NORMAL)
    {
        return $this->actingAs($user)->post(route('sales.visits.store'), [
            'customer_id' => $customer->id,
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'condition' => $condition,
            'notes' => $condition === Visit::CONDITION_NORMAL ? null : 'Toko tutup, pemilik keluar.',
        ]);
    }

    private function checkOut($user, Visit $visit): void
    {
        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => 'Stok toko masih banyak.'])
            ->assertSessionHasNoErrors();
    }

    private function lastVisit(): Visit
    {
        return Visit::latest('id')->firstOrFail();
    }

    private function stockedProduct($sales)
    {
        $product = $this->makeProduct(10000);
        app(StockService::class)->increase($product->id, Stock::LOCATION_SALES, $sales->id, 10, 'bkb_apply');

        return $product;
    }

    private function postTransaction($user, Customer $customer, $product)
    {
        return $this->actingAs($user)->post(route('sales.transactions.store'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
    }

    // ----------------------------------------------------------------- kuota

    public function test_second_visit_to_same_store_on_same_day_is_blocked(): void
    {
        [$user, , $a] = $this->salesWithTwoStores();

        $this->checkIn($user, $a)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        $this->checkIn($user, $a)->assertSessionHasErrors('customer_id');
        $this->assertSame(1, Visit::count());
    }

    public function test_closed_store_does_not_use_up_the_quota_one_revisit_allowed(): void
    {
        [$user, , $a] = $this->salesWithTwoStores();

        $this->checkIn($user, $a, Visit::CONDITION_CLOSED)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        // Kunjungan ulang setelah Toko Tutup diizinkan...
        $this->checkIn($user, $a)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());
        $this->assertSame(2, Visit::count());

        // ...tetapi hanya satu kali.
        $this->checkIn($user, $a)->assertSessionHasErrors('customer_id');
        $this->assertSame(2, Visit::count());
    }

    public function test_revisit_that_is_closed_again_exhausts_the_quota(): void
    {
        [$user, , $a] = $this->salesWithTwoStores();

        $this->checkIn($user, $a, Visit::CONDITION_CLOSED);
        $this->checkOut($user, $this->lastVisit());
        $this->checkIn($user, $a, Visit::CONDITION_CLOSED)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        $this->checkIn($user, $a)->assertSessionHasErrors('customer_id');
        $this->assertSame(2, Visit::count());
    }

    public function test_other_condition_still_uses_up_the_quota(): void
    {
        [$user, , $a] = $this->salesWithTwoStores();

        // Hanya "Toko Tutup" yang memberi hak kunjungan ulang; Kendala Lain tidak.
        $this->checkIn($user, $a, Visit::CONDITION_OTHER)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        $this->checkIn($user, $a)->assertSessionHasErrors('customer_id');
    }

    public function test_quota_is_per_store_and_resets_the_next_day(): void
    {
        [$user, $sales, $a, $b] = $this->salesWithTwoStores();

        $this->checkIn($user, $a)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        // Toko lain tidak terpengaruh.
        $this->checkIn($user, $b)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        // Kunjungan kemarin tidak menghabiskan kuota hari ini.
        $yesterday = $this->makeCustomer($sales->id);
        $yesterday->forceFill(['latitude' => self::LAT, 'longitude' => self::LNG])->save();
        Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $yesterday->id,
            'check_in_at' => now()->subDay(),
            'check_out_at' => now()->subDay()->addMinutes(10),
            'check_in_condition' => Visit::CONDITION_NORMAL,
            'status' => Visit::STATUS_COMPLETED,
        ]);

        $this->checkIn($user, $yesterday)->assertSessionHasNoErrors();
    }

    public function test_check_in_form_hides_stores_whose_quota_is_used_up(): void
    {
        [$user, , $a, $b] = $this->salesWithTwoStores();

        $this->checkIn($user, $a);
        $this->checkOut($user, $this->lastVisit());

        $this->actingAs($user)
            ->get(route('sales.visits.create'))
            ->assertOk()
            ->assertDontSee('Toko Alfa Makmur')
            ->assertSee('Toko Beta Sentosa')
            ->assertSee('sudah Anda kunjungi hari ini');
    }

    public function test_check_in_form_still_offers_a_closed_store_for_the_one_revisit(): void
    {
        [$user, , $a] = $this->salesWithTwoStores();

        $this->checkIn($user, $a, Visit::CONDITION_CLOSED);
        $this->checkOut($user, $this->lastVisit());

        $this->actingAs($user)
            ->get(route('sales.visits.create'))
            ->assertOk()
            ->assertSee('Toko Alfa Makmur');
    }

    // ------------------------------------------------- wajib check-in transaksi

    public function test_transaction_requires_an_ongoing_visit(): void
    {
        [$user, $sales, $a] = $this->salesWithTwoStores();
        $product = $this->stockedProduct($sales);

        $this->postTransaction($user, $a, $product)->assertSessionHasErrors('customer_id');

        $this->assertSame(0, SalesTransaction::count());
        $this->assertEquals(10, app(StockService::class)->getQuantity($product->id, Stock::LOCATION_SALES, $sales->id));
    }

    public function test_transaction_must_be_for_the_store_being_visited(): void
    {
        [$user, $sales, $a, $b] = $this->salesWithTwoStores();
        $product = $this->stockedProduct($sales);

        $this->checkIn($user, $a)->assertSessionHasNoErrors();

        $this->postTransaction($user, $b, $product)->assertSessionHasErrors('customer_id');
        $this->assertSame(0, SalesTransaction::count());
    }

    public function test_transaction_is_blocked_when_the_visit_is_marked_closed(): void
    {
        [$user, $sales, $a] = $this->salesWithTwoStores();
        $product = $this->stockedProduct($sales);

        $this->checkIn($user, $a, Visit::CONDITION_CLOSED)->assertSessionHasNoErrors();

        $this->postTransaction($user, $a, $product)->assertSessionHasErrors('customer_id');
        $this->assertSame(0, SalesTransaction::count());
    }

    public function test_transaction_is_allowed_during_a_normal_visit(): void
    {
        [$user, $sales, $a] = $this->salesWithTwoStores();
        $product = $this->stockedProduct($sales);

        $this->checkIn($user, $a)->assertSessionHasNoErrors();

        $this->postTransaction($user, $a, $product)->assertSessionHasNoErrors();

        $this->assertSame(1, SalesTransaction::count());
        $this->assertSame($a->id, SalesTransaction::first()->customer_id);
    }

    public function test_transaction_is_blocked_again_after_check_out(): void
    {
        [$user, $sales, $a] = $this->salesWithTwoStores();
        $product = $this->stockedProduct($sales);

        $this->checkIn($user, $a);
        $this->postTransaction($user, $a, $product)->assertSessionHasNoErrors();
        $this->checkOut($user, $this->lastVisit());

        $this->postTransaction($user, $a, $product)->assertSessionHasErrors('customer_id');
        $this->assertSame(1, SalesTransaction::count());
    }

    public function test_transaction_form_guides_sales_to_check_in_first(): void
    {
        [$user, $sales, $a] = $this->salesWithTwoStores();
        $this->stockedProduct($sales);

        $this->actingAs($user)
            ->get(route('sales.transactions.create'))
            ->assertOk()
            ->assertSee('dulu ke toko yang Anda kunjungi');

        $this->checkIn($user, $a);

        $this->actingAs($user)
            ->get(route('sales.transactions.create'))
            ->assertOk()
            ->assertSee('Customer (kunjungan berjalan)')
            ->assertSee('Toko Alfa Makmur')
            ->assertDontSee('dulu ke toko yang Anda kunjungi');
    }

    public function test_transaction_form_explains_when_the_store_is_closed(): void
    {
        [$user, $sales, $a] = $this->salesWithTwoStores();
        $this->stockedProduct($sales);

        $this->checkIn($user, $a, Visit::CONDITION_CLOSED);

        $this->actingAs($user)
            ->get(route('sales.transactions.create'))
            ->assertOk()
            ->assertSee('tercatat tutup');
    }

    // ------------------------------------------------------------------ hasil

    public function test_visit_outcome_reflects_closed_ongoing_transaction_and_no_transaction(): void
    {
        [$user, $sales, $a, $b] = $this->salesWithTwoStores();
        $product = $this->stockedProduct($sales);

        // Toko Tutup
        $this->checkIn($user, $a, Visit::CONDITION_CLOSED);
        $closed = $this->lastVisit();
        $this->assertSame(Visit::OUTCOME_CLOSED, $closed->outcome());
        $this->checkOut($user, $closed);
        $this->assertSame(Visit::OUTCOME_CLOSED, $closed->fresh()->outcome());

        // Berjalan, lalu Transaksi (EC)
        $this->checkIn($user, $b);
        $withTransaction = $this->lastVisit();
        $this->assertSame(Visit::OUTCOME_ONGOING, $withTransaction->outcome());
        $this->postTransaction($user, $b, $product)->assertSessionHasNoErrors();
        $this->checkOut($user, $withTransaction);
        $this->assertSame(Visit::OUTCOME_TRANSACTION, $withTransaction->fresh()->outcome());

        // Tanpa Transaksi (Call Made): kunjungan ulang toko A yang normal.
        $this->checkIn($user, $a);
        $noTransaction = $this->lastVisit();
        $this->checkOut($user, $noTransaction);
        $this->assertSame(Visit::OUTCOME_NO_TRANSACTION, $noTransaction->fresh()->outcome());

        $this->assertSame('Toko Tutup', Visit::outcomeLabel(Visit::OUTCOME_CLOSED));
        $this->assertSame('Transaksi (EC)', Visit::outcomeLabel(Visit::OUTCOME_TRANSACTION));
        $this->assertSame('Tanpa Transaksi (Call Made)', Visit::outcomeLabel(Visit::OUTCOME_NO_TRANSACTION));
    }

    public function test_admin_visit_list_shows_the_outcome_column(): void
    {
        [$user, , $a] = $this->salesWithTwoStores();

        $this->checkIn($user, $a, Visit::CONDITION_CLOSED);
        $this->checkOut($user, $this->lastVisit());

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.sales.visits.index'))
            ->assertOk()
            ->assertSee('Hasil')
            ->assertSee('Toko Tutup');
    }
}
