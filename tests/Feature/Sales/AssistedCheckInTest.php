<?php

namespace Tests\Feature\Sales;

use App\Models\AuditLog;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Check-in bantuan Admin/Superadmin: tanpa radius GPS, diberi penanda & pelaku,
 * waktu kunjungan terpisah dari waktu dicatat, tidak dobel, tidak menimpa check-in Sales.
 */
class AssistedCheckInTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function setUpData(): array
    {
        $admin = $this->makeAdminUser();
        $sales = $this->makeSales(null, $admin->branch);
        $customer = $this->makeCustomer($sales->id, $admin->branch); // tanpa titik lokasi sama sekali

        return [$admin, $sales, $customer];
    }

    private function payload($sales, $customer, array $extra = []): array
    {
        return array_merge([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'visit_date' => now()->subDay()->toDateString(),
            'visit_time' => '10:30',
            'condition' => 'normal',
            'reason' => 'Sales lupa check-in',
        ], $extra);
    }

    public function test_admin_assisted_check_in_without_gps_is_marked_and_attributed(): void
    {
        [$admin, $sales, $customer] = $this->setUpData();
        $day = now()->subDay()->toDateString();

        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer))
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $visit = Visit::firstOrFail();
        $this->assertSame($sales->id, $visit->sales_id);
        $this->assertTrue($visit->isAssisted());
        $this->assertSame($admin->id, $visit->entered_by);
        $this->assertSame('Check-in by Admin', $visit->assistedLabel());
        $this->assertSame($day.' 10:30', $visit->check_in_at->format('Y-m-d H:i'));   // waktu kunjungan sebenarnya
        $this->assertTrue($visit->recorded_at->isToday());                              // waktu dicatat
        $this->assertSame(Visit::STATUS_COMPLETED, $visit->status);
        $this->assertNull($visit->check_in_latitude);
        $this->assertSame('Sales lupa check-in', $visit->entry_reason);

        $this->actingAs($admin)->get(route('admin.sales.visits.index', ['date_from' => $day, 'date_to' => $day]))
            ->assertOk()->assertSee('Check-in by Admin');
        $this->actingAs($admin)->get(route('admin.sales.visits.show', $visit))
            ->assertOk()->assertSee('Check-in by Admin')->assertSee('Sales lupa check-in');
    }

    public function test_super_admin_gets_superadmin_marker_for_any_depo(): void
    {
        [, $sales, $customer] = $this->setUpData();
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->get(route('admin.sales.visits.create-for-sales', ['sales_id' => $sales->id]))->assertOk();
        $this->actingAs($super)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer))
            ->assertSessionHasNoErrors();

        $this->assertSame('Check-in by Superadmin', Visit::firstOrFail()->assistedLabel());
    }

    public function test_admin_cannot_check_in_for_sales_of_another_depo(): void
    {
        [, $sales, $customer] = $this->setUpData();
        $otherAdmin = $this->makeAdminUser($this->makeBranch('CBG-LAIN', 'Depo Lain'));

        $this->actingAs($otherAdmin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer))->assertForbidden();
        $this->assertSame(0, Visit::count());
    }

    public function test_sales_role_cannot_use_assisted_check_in(): void
    {
        [, $sales, $customer] = $this->setUpData();
        [$salesUser] = $this->makeSalesUser();

        $this->actingAs($salesUser)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer))->assertForbidden();
        $this->assertSame(0, Visit::count());
    }

    public function test_duplicate_same_sales_outlet_day_is_rejected_and_original_untouched(): void
    {
        [$admin, $sales, $customer] = $this->setUpData();
        $day = now()->subDay()->toDateString();

        $own = Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id,
            'check_in_at' => $day.' 09:00:00', 'check_out_at' => $day.' 09:20:00',
            'check_in_condition' => 'normal', 'notes' => 'asli', 'status' => Visit::STATUS_COMPLETED,
        ]);

        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer))
            ->assertSessionHasErrors('customer_id');

        $this->assertSame(1, Visit::count());
        $own->refresh();
        $this->assertFalse($own->isAssisted());
        $this->assertSame('asli', $own->notes);
        $this->assertSame($day.' 09:00', $own->check_in_at->format('Y-m-d H:i'));

        // Dua kali kirim bantuan di hari lain: kedua kalinya ditolak.
        $other = now()->subDays(3)->toDateString();
        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer, ['visit_date' => $other]))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer, ['visit_date' => $other]))->assertSessionHasErrors('customer_id');
        $this->assertSame(2, Visit::count());
    }

    public function test_future_time_and_missing_reason_are_rejected(): void
    {
        [$admin, $sales, $customer] = $this->setUpData();

        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer, ['visit_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('visit_date');
        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer, ['reason' => '']))
            ->assertSessionHasErrors('reason');
        $this->assertSame(0, Visit::count());
    }

    public function test_outlet_of_another_depo_is_rejected(): void
    {
        [$admin, $sales] = $this->setUpData();
        $foreign = $this->makeCustomer(null, $this->makeBranch('CBG-LAIN', 'Depo Lain'));

        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $foreign))
            ->assertSessionHasErrors('customer_id');
        $this->assertSame(0, Visit::count());
    }

    public function test_audit_log_records_actor_and_depo(): void
    {
        [$admin, $sales, $customer] = $this->setUpData();

        $this->actingAs($admin)->post(route('admin.sales.visits.store-for-sales'), $this->payload($sales, $customer))
            ->assertSessionHasNoErrors();

        $visit = Visit::firstOrFail();
        $log = AuditLog::where('action', 'assisted_check_in')->where('document_id', $visit->id)->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame((int) $sales->branch_id, (int) $log->branch_id);
        $this->assertSame($sales->id, $log->after['sales_id']);
        $this->assertSame('admin', $log->after['source']);
    }

    public function test_regular_sales_check_in_is_not_marked_assisted(): void
    {
        [, $sales, $customer] = $this->setUpData();
        $v = Visit::create([
            'sales_id' => $sales->id, 'customer_id' => $customer->id, 'check_in_at' => now(),
            'check_in_condition' => 'normal', 'status' => Visit::STATUS_ONGOING,
        ]);

        $this->assertSame('sales', $v->fresh()->source);
        $this->assertNull($v->fresh()->assistedLabel());
    }
}
