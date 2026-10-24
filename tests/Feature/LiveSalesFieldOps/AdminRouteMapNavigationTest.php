<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\SalesVisitPlan;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Admin > Sales > Rute Toko per Sales: navigasi minggu sebelumnya/
 * selanjutnya dan pilih tanggal bebas, supaya Admin bisa melihat rute &
 * status kunjungan (dikunjungi / Toko Tutup / belum) di tanggal-tanggal
 * yang sudah lewat, bukan cuma minggu berjalan.
 */
class AdminRouteMapNavigationTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_week_navigation_links_point_seven_days_back_and_forward(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);

        $anchor = Carbon::parse('2026-10-05'); // Senin
        SalesVisitPlan::create(['sales_id' => $sales->id, 'day_of_week' => $anchor->dayOfWeekIso, 'customer_id' => $customer->id, 'sequence' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.sales.route-map.index', [
            'sales_id' => $sales->id,
            'date' => $anchor->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Minggu Lalu');
        $response->assertSee('Minggu Depan');
        $response->assertSee($anchor->copy()->subWeek()->toDateString(), false);
        $response->assertSee($anchor->copy()->addWeek()->toDateString(), false);
    }

    public function test_date_picker_jumps_to_a_past_date_and_shows_visit_status(): void
    {
        $admin = $this->makeAdminUser();
        [$salesUser, $sales] = $this->makeSalesUser();
        $visited = $this->makeCustomer($sales->id);
        $visited->update(['name' => 'Toko Sudah Dikunjungi']);
        $closed = $this->makeCustomer($sales->id);
        $closed->update(['name' => 'Toko Tutup Waktu Itu']);
        $notVisited = $this->makeCustomer($sales->id);
        $notVisited->update(['name' => 'Toko Belum Dikunjungi']);

        $pastDate = now()->subWeeks(2)->startOfWeek();
        foreach ([$visited, $closed, $notVisited] as $c) {
            SalesVisitPlan::create(['sales_id' => $sales->id, 'day_of_week' => $pastDate->dayOfWeekIso, 'customer_id' => $c->id, 'sequence' => 1]);
        }

        Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $visited->id,
            'check_in_at' => $pastDate->copy()->setTime(9, 0),
            'check_in_condition' => Visit::CONDITION_NORMAL,
            'status' => Visit::STATUS_COMPLETED,
        ]);
        Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $closed->id,
            'check_in_at' => $pastDate->copy()->setTime(9, 30),
            'check_in_condition' => Visit::CONDITION_CLOSED,
            'status' => Visit::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sales.route-map.index', [
            'sales_id' => $sales->id,
            'date' => $pastDate->toDateString(),
        ]));

        $response->assertOk()
            ->assertSee('Toko Sudah Dikunjungi')
            ->assertSee('Toko Tutup Waktu Itu')
            ->assertSee('Toko Belum Dikunjungi')
            ->assertSee('Toko Tutup')
            ->assertSee('Call Meet')
            ->assertSee('Belum Dikunjungi');
    }

    public function test_navigation_preserves_selected_sales(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);

        $anchor = Carbon::parse('2026-10-05');
        SalesVisitPlan::create(['sales_id' => $sales->id, 'day_of_week' => $anchor->dayOfWeekIso, 'customer_id' => $customer->id, 'sequence' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.sales.route-map.index', [
            'sales_id' => $sales->id,
            'date' => $anchor->toDateString(),
        ]));

        // Tautan navigasi minggu & form date-picker ikut menyimpan sales_id
        // yang sedang dipilih, bukan reset ke "pilih Sales lagi".
        $response->assertSee('sales_id='.$sales->id, false);
        $response->assertSee('name="sales_id" value="'.$sales->id.'"', false);
    }
}
