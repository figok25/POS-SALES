<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\PromoItem;
use App\Models\SalesTransaction;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Kunjungan: kondisi outlet + alasan + checklist Promosi/POSM.
 *
 * Aturan bisnis:
 *  - Alasan CHECK-IN wajib HANYA bila bermasalah (toko tutup / kendala lain).
 *  - Promosi/POSM: Sales mencentang item yang ADA (tidak dicentang = Tidak ada),
 *    untuk SEMUA kondisi -- toko tutup pun POSM tetap dicatat.
 *  - Alasan CHECK-OUT wajib HANYA bila outlet normal tetapi belum ada transaksi
 *    (alasan tidak transaksi). Toko tutup / kendala sudah dijelaskan saat check-in.
 *  - Daftar item dikelola Admin (promo_items).
 */
class VisitConditionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -7.9797;
    private const LNG = 112.6304;

    private PromoItem $promo;
    private PromoItem $posm;
    private PromoItem $banner;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar item awal dari migration diganti set yang pasti, supaya test
        // tidak bergantung pada isi seed.
        PromoItem::query()->delete();
        $this->promo = PromoItem::create(['name' => 'Program Promosi', 'sort_order' => 10, 'is_active' => true]);
        $this->posm = PromoItem::create(['name' => 'POSM', 'sort_order' => 20, 'is_active' => true]);
        $this->banner = PromoItem::create(['name' => 'Banner', 'sort_order' => 30, 'is_active' => true]);
    }

    /** @return array{0:\App\Models\User,1:\App\Models\Sales,2:Customer} */
    private function salesWithCustomer(): array
    {
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales);

        $customer = $this->makeCustomer($sales->id);
        $customer->forceFill(['latitude' => self::LAT, 'longitude' => self::LNG])->save();

        return [$user, $sales, $customer];
    }

    private function payload(Customer $customer, array $override = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'condition' => Visit::CONDITION_NORMAL,
            'notes' => '',
            'promo_items' => [$this->promo->id, $this->banner->id],
            'facility_notes' => 'Banner promo terpasang di depan.',
        ], $override);
    }

    private function answer(Visit $visit, PromoItem $item): ?bool
    {
        $row = $visit->promoItems()->where('promo_items.id', $item->id)->first();

        return $row ? (bool) $row->pivot->is_present : null;
    }

    private function makeTransaction($sales, Customer $customer): SalesTransaction
    {
        static $n = 0;
        $n++;

        return SalesTransaction::create([
            'code' => "TRX-VC-{$n}",
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 0,
            'total' => 10000,
            'status' => SalesTransaction::STATUS_COMPLETED,
            'price_type' => 'retail',
        ]);
    }

    // ------------------------------------------------------------ check-in

    public function test_check_in_requires_a_valid_condition(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['condition' => '']))
            ->assertSessionHasErrors('condition');

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['condition' => 'liburan']))
            ->assertSessionHasErrors('condition');

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_normal_outlet_check_in_needs_no_reason(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['notes' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.visits.index'));

        $visit = Visit::first();
        $this->assertSame(Visit::CONDITION_NORMAL, $visit->check_in_condition);
        $this->assertNull($visit->notes);
    }

    public function test_problem_outlet_check_in_requires_a_reason(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        foreach ([Visit::CONDITION_CLOSED, Visit::CONDITION_OTHER] as $condition) {
            $this->actingAs($user)
                ->post(route('sales.visits.store'), $this->payload($customer, ['condition' => $condition, 'notes' => '']))
                ->assertSessionHasErrors('notes');
        }

        $this->assertDatabaseCount('visits', 0);

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, [
                'condition' => Visit::CONDITION_CLOSED,
                'notes' => 'Toko tutup, pemilik sedang keluar kota.',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.visits.index'));

        $visit = Visit::first();
        $this->assertTrue($visit->isOutletClosed());
        $this->assertSame('Toko tutup, pemilik sedang keluar kota.', $visit->notes);
    }

    public function test_ticked_items_are_present_and_unticked_items_are_absent(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer))
            ->assertRedirect(route('sales.visits.index'));

        $visit = Visit::first();
        $this->assertTrue($this->answer($visit, $this->promo));
        $this->assertFalse($this->answer($visit, $this->posm));
        $this->assertTrue($this->answer($visit, $this->banner));
        $this->assertSame('Banner promo terpasang di depan.', $visit->facility_notes);
        $this->assertTrue($visit->fresh()->hasFacilityInfo());
    }

    public function test_nothing_ticked_records_every_active_item_as_absent(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $payload = $this->payload($customer);
        unset($payload['promo_items']);

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $payload)
            ->assertSessionHasNoErrors();

        $visit = Visit::first();
        $this->assertSame(3, $visit->promoItems()->count());
        $this->assertFalse($this->answer($visit, $this->promo));
        $this->assertFalse($this->answer($visit, $this->posm));
        $this->assertFalse($this->answer($visit, $this->banner));
    }

    public function test_closed_store_still_records_posm_answers(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        // Keputusan bisnis: toko tutup tetap mencatat POSM.
        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, [
                'condition' => Visit::CONDITION_CLOSED,
                'notes' => 'Toko tutup.',
                'promo_items' => [$this->posm->id],
            ]))
            ->assertRedirect(route('sales.visits.index'));

        $visit = Visit::first();
        $this->assertTrue($visit->isOutletClosed());
        $this->assertTrue($this->answer($visit, $this->posm));
        $this->assertFalse($this->answer($visit, $this->promo));
        $this->assertTrue($visit->hasFacilityInfo());
    }

    public function test_inactive_or_unknown_items_are_rejected_and_not_recorded(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();
        $this->banner->update(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['promo_items' => [$this->banner->id]]))
            ->assertSessionHasErrors('promo_items.0');

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['promo_items' => [999999]]))
            ->assertSessionHasErrors('promo_items.0');

        $this->assertDatabaseCount('visits', 0);

        // Item nonaktif tidak ikut dicatat pada kunjungan baru.
        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['promo_items' => [$this->posm->id]]))
            ->assertSessionHasNoErrors();

        $visit = Visit::first();
        $this->assertSame(2, $visit->promoItems()->count());
        $this->assertNull($this->answer($visit, $this->banner));
    }

    // ----------------------------------------------------------- check-out

    public function test_normal_visit_without_transaction_requires_no_transaction_reason(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer));
        $visit = Visit::first();

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => ''])
            ->assertSessionHasErrors('notes');
        $this->assertSame(Visit::STATUS_ONGOING, $visit->fresh()->status);

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => 'Stok toko masih banyak.'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.visits.index'));

        $visit->refresh();
        $this->assertSame(Visit::STATUS_COMPLETED, $visit->status);
        $this->assertSame('Stok toko masih banyak.', $visit->check_out_notes);
    }

    public function test_normal_visit_with_transaction_needs_no_check_out_reason(): void
    {
        [$user, $sales, $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer));
        $visit = Visit::first();

        $this->makeTransaction($sales, $customer);

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => ''])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.visits.index'));

        $this->assertSame(Visit::STATUS_COMPLETED, $visit->fresh()->status);
    }

    public function test_cancelled_transaction_does_not_count_as_a_transaction(): void
    {
        [$user, $sales, $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer));
        $visit = Visit::first();

        $this->makeTransaction($sales, $customer)->update(['status' => SalesTransaction::STATUS_CANCELLED]);

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => ''])
            ->assertSessionHasErrors('notes');
    }

    public function test_closed_visit_check_out_needs_no_extra_reason_and_keeps_check_in_reason(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer, [
            'condition' => Visit::CONDITION_CLOSED,
            'notes' => 'Toko tutup, pemilik keluar kota.',
        ]));
        $visit = Visit::first();

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => ''])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.visits.index'));

        $visit->refresh();
        $this->assertSame(Visit::STATUS_COMPLETED, $visit->status);
        $this->assertSame('Toko tutup, pemilik keluar kota.', $visit->notes);
        $this->assertNull($visit->check_out_notes);
    }

    public function test_check_out_notes_do_not_overwrite_check_in_notes(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer, ['notes' => 'Toko buka, pemilik ada.']));
        $visit = Visit::first();

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => 'Pemilik minta datang lagi minggu depan.'])
            ->assertRedirect(route('sales.visits.index'));

        $visit->refresh();
        $this->assertSame('Toko buka, pemilik ada.', $visit->notes);
        $this->assertSame('Pemilik minta datang lagi minggu depan.', $visit->check_out_notes);
    }

    public function test_sales_visit_page_shows_reason_field_as_required_only_when_needed(): void
    {
        [$user, $sales, $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer));

        $this->actingAs($user)
            ->get(route('sales.visits.index'))
            ->assertOk()
            ->assertSee('Alasan Tidak Transaksi')
            ->assertSee('alasan wajib diisi');

        $this->makeTransaction($sales, $customer);

        $this->actingAs($user)
            ->get(route('sales.visits.index'))
            ->assertOk()
            ->assertSee('Catatan Check-out (opsional)')
            ->assertDontSee('Alasan Tidak Transaksi');
    }

    // ---------------------------------------------------- tampilan form check-in

    public function test_check_in_form_has_choice_controls_that_do_not_depend_on_tailwind_build(): void
    {
        [$user] = $this->salesWithCustomer();

        // Tombol pilihan memakai gaya bawaan halaman (:checked), bukan peer-checked:* Tailwind
        // yang hanya ada kalau CSS dibangun ulang dan diunggah ke server.
        $this->actingAs($user)
            ->get(route('sales.visits.create'))
            ->assertOk()
            ->assertSee('name="condition"', false)
            ->assertSee('name="promo_items[]"', false)
            ->assertSee('.vc-choice > input:checked + .vc-box', false)
            ->assertSee('.vc-toggle > input:checked ~ .vc-on', false)
            ->assertDontSee('peer-checked', false);
    }

    // --------------------------------------------------------------- admin

    public function test_admin_visit_pages_show_condition_reasons_and_item_answers(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();
        $admin = $this->makeAdminUser();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer, ['notes' => 'Toko buka, pemilik ada.']));
        $visit = Visit::first();
        $this->actingAs($user)->post(route('sales.visits.check-out', $visit), ['notes' => 'Stok masih banyak, tidak order.']);

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.index'))
            ->assertOk()
            ->assertSee('Kondisi')
            ->assertSee('Normal');

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.show', $visit))
            ->assertOk()
            ->assertSee('Toko buka, pemilik ada.')
            ->assertSee('Stok masih banyak, tidak order.')
            ->assertSee('Program Promosi')
            ->assertSee('POSM')
            ->assertSee('Banner')
            ->assertSee('Ada')
            ->assertSee('Tidak ada')
            ->assertSee('Banner promo terpasang di depan.');
    }

    public function test_admin_visit_detail_shows_posm_for_closed_store(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();
        $admin = $this->makeAdminUser();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer, [
            'condition' => Visit::CONDITION_CLOSED,
            'notes' => 'Toko tutup.',
            'promo_items' => [$this->posm->id],
        ]));
        $visit = Visit::first();

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.show', $visit))
            ->assertOk()
            ->assertSee('Toko Tutup')
            ->assertSee('POSM')
            ->assertDontSee('Tidak ada data promosi/POSM');
    }

    public function test_admin_visit_detail_handles_legacy_visit_without_answers(): void
    {
        [, $sales, $customer] = $this->salesWithCustomer();
        $admin = $this->makeAdminUser();

        $visit = Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => now(),
            'check_in_condition' => Visit::CONDITION_NORMAL,
            'status' => Visit::STATUS_ONGOING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.show', $visit))
            ->assertOk()
            ->assertSee('Tidak ada data promosi/POSM');
    }
}
