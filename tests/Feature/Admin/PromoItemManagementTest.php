<?php

namespace Tests\Feature\Admin;

use App\Models\PromoItem;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Master Data > Item Promosi / POSM: Admin boleh menambah, mengubah, dan
 * menonaktifkan item (tanpa hapus). Item nonaktif tidak muncul di kunjungan
 * baru, tetapi jawaban di kunjungan lama tetap utuh.
 */
class PromoItemManagementTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_default_items_are_seeded_by_the_migration(): void
    {
        $names = PromoItem::active()->ordered()->pluck('name')->all();

        $this->assertSame(['Program Promosi', 'POSM', 'Banner', 'Fasilitas Pendukung Lain'], $names);
    }

    public function test_admin_can_list_items_and_menu_link_exists(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.master.promo-items.index'))
            ->assertOk()
            ->assertSee('Item Promosi / POSM')
            ->assertSee('POSM')
            ->assertSee('Banner');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee(route('admin.master.promo-items.index'), false);
    }

    public function test_sales_cannot_manage_items(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)->get(route('admin.master.promo-items.index'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('admin.master.promo-items.store'), ['name' => 'Rak Display'])
            ->assertForbidden();

        $this->assertDatabaseMissing('promo_items', ['name' => 'Rak Display']);
    }

    public function test_admin_can_add_an_item_placed_last_by_default(): void
    {
        $admin = $this->makeAdminUser();
        $lastOrder = (int) PromoItem::max('sort_order');

        $this->actingAs($admin)
            ->post(route('admin.master.promo-items.store'), ['name' => 'Rak Display', 'sort_order' => '', 'is_active' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.master.promo-items.index'));

        $item = PromoItem::where('name', 'Rak Display')->firstOrFail();
        $this->assertTrue($item->is_active);
        $this->assertSame($lastOrder + 10, $item->sort_order);
        $this->assertSame('Rak Display', PromoItem::active()->ordered()->get()->last()->name);
    }

    public function test_item_name_must_be_unique_and_required(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->post(route('admin.master.promo-items.store'), ['name' => 'POSM'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('admin.master.promo-items.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, PromoItem::where('name', 'POSM')->count());
    }

    public function test_admin_can_rename_reorder_and_keep_same_name_on_update(): void
    {
        $admin = $this->makeAdminUser();
        $item = PromoItem::where('name', 'Banner')->firstOrFail();

        // Menyimpan dengan nama yang sama tidak dianggap duplikat.
        $this->actingAs($admin)
            ->put(route('admin.master.promo-items.update', $item), ['name' => 'Banner', 'sort_order' => 5, 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame(5, $item->fresh()->sort_order);

        $this->actingAs($admin)
            ->put(route('admin.master.promo-items.update', $item), ['name' => 'Banner Depan Toko', 'sort_order' => '', 'is_active' => '1'])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('Banner Depan Toko', $item->name);
        $this->assertSame(5, $item->sort_order, 'Urutan kosong berarti tidak diubah.');
    }

    public function test_deactivated_item_leaves_new_visits_but_old_answers_stay(): void
    {
        $admin = $this->makeAdminUser();
        [$user, $sales] = $this->makeSalesUser();
        $this->makeSalesTask($sales);

        // Dua toko berbeda: satu toko hanya boleh dikunjungi sekali per hari.
        $customer = $this->makeCustomer($sales->id);
        $customer->forceFill(['latitude' => -7.9797, 'longitude' => 112.6304])->save();
        $customer2 = $this->makeCustomer($sales->id);
        $customer2->forceFill(['latitude' => -7.9797, 'longitude' => 112.6304])->save();

        $banner = PromoItem::where('name', 'Banner')->firstOrFail();
        $posm = PromoItem::where('name', 'POSM')->firstOrFail();

        $checkIn = fn (array $ticked, $store) => $this->actingAs($user)->post(route('sales.visits.store'), [
            'customer_id' => $store->id,
            'latitude' => -7.9797,
            'longitude' => 112.6304,
            'condition' => Visit::CONDITION_NORMAL,
            'promo_items' => $ticked,
        ])->assertSessionHasNoErrors();

        // Kunjungan pertama (semua item aktif, Banner dicentang), lalu selesai.
        $checkIn([$banner->id], $customer);
        $oldVisit = Visit::first();
        $this->actingAs($user)->post(route('sales.visits.check-out', $oldVisit), ['notes' => 'Tidak order, stok masih ada.']);

        // Admin menonaktifkan Banner (is_active=0 dari hidden input).
        $this->actingAs($admin)
            ->put(route('admin.master.promo-items.update', $banner), ['name' => 'Banner', 'is_active' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($banner->fresh()->is_active);

        // Kunjungan baru tidak lagi mencatat Banner (form hanya menampilkan item aktif)...
        $checkIn([$posm->id], $customer2);
        $newVisit = Visit::latest('id')->first();
        $this->assertNotSame($oldVisit->id, $newVisit->id);
        $this->assertFalse($newVisit->promoItems->contains($banner->id));
        $this->assertTrue($newVisit->promoItems->contains($posm->id));

        // ...tetapi kunjungan lama tetap menyimpan jawaban Banner.
        $this->assertTrue($oldVisit->fresh()->promoItems->contains($banner->id));
        $this->actingAs($admin)
            ->get(route('admin.sales.visits.show', $oldVisit))
            ->assertOk()
            ->assertSee('Banner');
    }

    public function test_there_is_no_delete_route_for_items(): void
    {
        $admin = $this->makeAdminUser();
        $item = PromoItem::first();

        $this->actingAs($admin)
            ->delete(route('admin.master.promo-items.update', $item))
            ->assertStatus(405);

        $this->assertDatabaseHas('promo_items', ['id' => $item->id]);
    }
}
