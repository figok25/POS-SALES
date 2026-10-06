<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * RevisiMinor #5 + #6 - Check-in/Check-out wajib keterangan, kondisi outlet
 * terstruktur (Normal / Toko Tutup / Kendala Lain), dan info Promosi/POSM
 * yang hanya diamati saat check-in pada outlet yang normal/buka.
 */
class VisitConditionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -7.9797;
    private const LNG = 112.6304;

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
            'notes' => 'Toko buka, pemilik ada.',
            'has_promo' => '1',
            'has_posm' => '0',
            'has_banner' => '1',
            'facility_notes' => 'Banner promo terpasang di depan.',
        ], $override);
    }

    public function test_check_in_requires_condition_and_notes(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['condition' => '', 'notes' => '']))
            ->assertSessionHasErrors(['condition', 'notes']);

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_check_in_rejects_unknown_condition(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer, ['condition' => 'liburan']))
            ->assertSessionHasErrors('condition');
    }

    public function test_normal_outlet_requires_promo_posm_banner_answers(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $payload = $this->payload($customer);
        unset($payload['has_promo'], $payload['has_posm'], $payload['has_banner']);

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $payload)
            ->assertSessionHasErrors(['has_promo', 'has_posm', 'has_banner']);

        $this->assertDatabaseCount('visits', 0);
    }

    public function test_normal_outlet_check_in_stores_condition_and_facility_info(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)
            ->post(route('sales.visits.store'), $this->payload($customer))
            ->assertRedirect(route('sales.visits.index'));

        $visit = Visit::first();
        $this->assertSame(Visit::CONDITION_NORMAL, $visit->check_in_condition);
        $this->assertSame('Toko buka, pemilik ada.', $visit->notes);
        $this->assertTrue($visit->has_promo);
        $this->assertFalse($visit->has_posm);
        $this->assertTrue($visit->has_banner);
        $this->assertSame('Banner promo terpasang di depan.', $visit->facility_notes);
        $this->assertTrue($visit->hasFacilityInfo());
    }

    public function test_closed_store_check_in_does_not_require_or_store_facility_info(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        // Toko tutup: Sales tidak bisa memeriksa promosi/POSM, jadi tidak
        // diwajibkan -- dan kalau tetap terkirim, tidak boleh tersimpan
        // (NULL = "tidak diamati", bukan "tidak ada").
        $this->actingAs($user)
            ->post(route('sales.visits.store'), [
                'customer_id' => $customer->id,
                'latitude' => self::LAT,
                'longitude' => self::LNG,
                'condition' => Visit::CONDITION_CLOSED,
                'notes' => 'Toko tutup, pemilik sedang keluar kota.',
                'has_promo' => '1',
                'facility_notes' => 'Seharusnya diabaikan',
            ])
            ->assertRedirect(route('sales.visits.index'));

        $visit = Visit::first();
        $this->assertTrue($visit->isOutletClosed());
        $this->assertNull($visit->has_promo);
        $this->assertNull($visit->has_posm);
        $this->assertNull($visit->has_banner);
        $this->assertNull($visit->facility_notes);
        $this->assertFalse($visit->hasFacilityInfo());
    }

    public function test_check_out_requires_notes_and_keeps_check_in_notes(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer));
        $visit = Visit::first();

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => ''])
            ->assertSessionHasErrors('notes');
        $this->assertSame(Visit::STATUS_ONGOING, $visit->fresh()->status);

        $this->actingAs($user)
            ->post(route('sales.visits.check-out', $visit), ['notes' => 'Order diterima, 2 dus.'])
            ->assertRedirect(route('sales.visits.index'));

        $visit->refresh();
        $this->assertSame(Visit::STATUS_COMPLETED, $visit->status);
        $this->assertSame('Order diterima, 2 dus.', $visit->check_out_notes);
        // Keterangan check-in tidak boleh tertimpa oleh check-out.
        $this->assertSame('Toko buka, pemilik ada.', $visit->notes);
    }

    public function test_admin_visit_pages_show_condition_notes_and_facility_info(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();
        $admin = $this->makeAdminUser();

        $this->actingAs($user)->post(route('sales.visits.store'), $this->payload($customer));
        $visit = Visit::first();
        $this->actingAs($user)->post(route('sales.visits.check-out', $visit), ['notes' => 'Selesai, order masuk.']);

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.index'))
            ->assertOk()
            ->assertSee('Kondisi')
            ->assertSee('Normal');

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.show', $visit))
            ->assertOk()
            ->assertSee('Toko buka, pemilik ada.')
            ->assertSee('Selesai, order masuk.')
            ->assertSee('Program promosi')
            ->assertSee('Banner promo terpasang di depan.');
    }

    public function test_admin_visit_detail_marks_closed_store_facility_info_as_not_observed(): void
    {
        [$user, , $customer] = $this->salesWithCustomer();
        $admin = $this->makeAdminUser();

        $this->actingAs($user)->post(route('sales.visits.store'), [
            'customer_id' => $customer->id,
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'condition' => Visit::CONDITION_CLOSED,
            'notes' => 'Toko tutup.',
        ]);
        $visit = Visit::first();

        $this->actingAs($admin)
            ->get(route('admin.sales.visits.show', $visit))
            ->assertOk()
            ->assertSee('Toko Tutup')
            ->assertSee('Tidak diamati pada kunjungan ini');
    }
}
