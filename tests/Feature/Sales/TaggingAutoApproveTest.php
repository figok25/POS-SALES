<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\CustomerTagging;
use App\Models\SalesVisitPlan;
use App\Services\CustomerTaggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Tagging Toko otomatis: tidak duplikat -> langsung jadi Customer; hanya yang
 * terindikasi duplikat yang ditahan (pending) menunggu Admin.
 *
 * Duplikat = telepon sama persis (+62 = 0) di Depo yang sama / tagging pending
 * lain, ATAU nama mirip (>= 85%) dan berjarak <= 100 m dari Customer di Depo
 * yang sama.
 */
class TaggingAutoApproveTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const LAT = -7.9797;
    private const LNG = 112.6304;

    private $user;
    private $sales;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->user, $this->sales] = $this->makeSalesUser();
        $this->makeSalesTask($this->sales);
    }

    private function submit(array $override = [])
    {
        return $this->actingAs($this->user)->post(route('sales.tagging.store'), array_merge([
            'name' => 'Toko Baru Jaya',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No 1',
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'customer_type' => 'warung',
        ], $override));
    }

    private function existingCustomer(array $attributes = [], $branch = null): Customer
    {
        $customer = $this->makeCustomer($branch ? null : $this->sales->id, $branch);
        $customer->forceFill($attributes)->save();

        return $customer;
    }

    // ---------------------------------------------------------- otomatis

    public function test_non_duplicate_tagging_becomes_a_customer_immediately(): void
    {
        $this->submit()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sales.tagging.index'))
            ->assertSessionHas('status', fn ($msg) => str_contains($msg, 'langsung masuk'));

        $tagging = CustomerTagging::firstOrFail();
        $customer = Customer::where('name', 'Toko Baru Jaya')->firstOrFail();

        $this->assertSame(CustomerTagging::STATUS_APPROVED, $tagging->status);
        $this->assertTrue($tagging->auto_approved);
        $this->assertSame($customer->id, $tagging->customer_id);
        $this->assertNull($tagging->reviewed_by, 'Disetujui sistem, bukan oleh user.');
        $this->assertNull($tagging->duplicate_reason);

        $this->assertSame($this->sales->id, $customer->sales_id);
        $this->assertSame($this->sales->branch_id, $customer->branch_id);
        $this->assertEquals(self::LAT, (float) $customer->latitude);
        $this->assertTrue(SalesVisitPlan::where('customer_id', $customer->id)->where('sales_id', $this->sales->id)->exists());
    }

    public function test_tagging_without_phone_or_location_is_not_a_duplicate(): void
    {
        $this->submit(['phone' => null, 'latitude' => null, 'longitude' => null])->assertSessionHasNoErrors();

        $this->assertSame(CustomerTagging::STATUS_APPROVED, CustomerTagging::firstOrFail()->status);
    }

    // ------------------------------------------------------- duplikat: telepon

    public function test_same_phone_as_existing_customer_is_held_even_with_country_code(): void
    {
        $existing = $this->existingCustomer(['phone' => '0812-3456-7890']);
        $before = Customer::count();

        $this->submit(['phone' => '+62 812 3456 7890'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($msg) => str_contains($msg, 'duplikat'));

        $tagging = CustomerTagging::firstOrFail();
        $this->assertSame(CustomerTagging::STATUS_PENDING, $tagging->status);
        $this->assertFalse($tagging->auto_approved);
        $this->assertSame($existing->id, $tagging->duplicate_customer_id);
        $this->assertStringContainsString($existing->code, $tagging->duplicate_reason);
        $this->assertNull($tagging->customer_id);
        $this->assertSame($before, Customer::count(), 'Tidak boleh membuat Customer baru.');
    }

    public function test_same_phone_in_another_branch_is_not_a_duplicate(): void
    {
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->existingCustomer(['phone' => '081234567890'], $branchB);

        $this->submit()->assertSessionHasNoErrors();

        $this->assertSame(CustomerTagging::STATUS_APPROVED, CustomerTagging::firstOrFail()->status);
    }

    public function test_same_phone_as_another_pending_tagging_is_held(): void
    {
        $other = CustomerTagging::create([
            'sales_id' => $this->sales->id,
            'name' => 'Toko Lain Menunggu',
            'phone' => '0812 3456 7890',
            'status' => CustomerTagging::STATUS_PENDING,
            'tagged_at' => now(),
        ]);

        $this->submit()->assertSessionHasNoErrors();

        $new = CustomerTagging::where('id', '!=', $other->id)->firstOrFail();
        $this->assertSame(CustomerTagging::STATUS_PENDING, $new->status);
        $this->assertStringContainsString('tagging lain', $new->duplicate_reason);
        $this->assertNull($new->duplicate_customer_id);
    }

    public function test_very_short_phone_numbers_are_ignored(): void
    {
        $this->existingCustomer(['phone' => '12345']);

        $this->submit(['phone' => '12345'])->assertSessionHasNoErrors();

        $this->assertSame(CustomerTagging::STATUS_APPROVED, CustomerTagging::firstOrFail()->status);
    }

    // -------------------------------------------------- duplikat: nama + jarak

    public function test_similar_name_within_100_meters_is_held(): void
    {
        $existing = $this->existingCustomer(['name' => 'Toko Maju Jaya', 'latitude' => self::LAT, 'longitude' => self::LNG]);

        // ~33 m dari toko yang sudah ada; "Toko" diabaikan saat membandingkan nama.
        $this->submit(['name' => 'Maju Jaya', 'phone' => null, 'latitude' => self::LAT + 0.0003])
            ->assertSessionHasNoErrors();

        $tagging = CustomerTagging::firstOrFail();
        $this->assertSame(CustomerTagging::STATUS_PENDING, $tagging->status);
        $this->assertSame($existing->id, $tagging->duplicate_customer_id);
        $this->assertStringContainsString('Nama mirip', $tagging->duplicate_reason);
    }

    public function test_same_name_far_away_is_not_a_duplicate(): void
    {
        $this->existingCustomer(['name' => 'Toko Maju Jaya', 'latitude' => self::LAT, 'longitude' => self::LNG]);

        // ~5,5 km: toko berbeda dengan nama umum yang sama.
        $this->submit(['name' => 'Toko Maju Jaya', 'phone' => null, 'latitude' => self::LAT + 0.05])
            ->assertSessionHasNoErrors();

        $this->assertSame(CustomerTagging::STATUS_APPROVED, CustomerTagging::firstOrFail()->status);
    }

    public function test_different_name_nearby_is_not_a_duplicate(): void
    {
        $this->existingCustomer(['name' => 'Toko Maju Jaya', 'latitude' => self::LAT, 'longitude' => self::LNG]);

        $this->submit(['name' => 'Warung Sumber Rejeki', 'phone' => null, 'latitude' => self::LAT + 0.0003])
            ->assertSessionHasNoErrors();

        $this->assertSame(CustomerTagging::STATUS_APPROVED, CustomerTagging::firstOrFail()->status);
    }

    // ------------------------------------------------------------ Admin

    public function test_admin_can_still_approve_a_held_duplicate_and_it_is_not_marked_automatic(): void
    {
        $this->existingCustomer(['phone' => '081234567890']);
        $this->submit()->assertSessionHasNoErrors();

        $tagging = CustomerTagging::firstOrFail();
        $admin = $this->makeAdminUser();

        $approved = app(CustomerTaggingService::class)->approve($tagging, $admin->id, 'Toko berbeda, nomor dipakai bersama.');

        $this->assertSame(CustomerTagging::STATUS_APPROVED, $approved->status);
        $this->assertFalse($approved->auto_approved);
        $this->assertSame($admin->id, $approved->reviewed_by);
        $this->assertNotNull($approved->customer_id);
    }

    public function test_admin_sees_why_a_tagging_is_held(): void
    {
        $this->existingCustomer(['phone' => '081234567890']);
        $this->submit()->assertSessionHasNoErrors();

        $tagging = CustomerTagging::firstOrFail();

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.sales.customer-taggings.show', $tagging))
            ->assertOk()
            ->assertSee('Ditahan karena terindikasi duplikat')
            ->assertSee('Nomor telepon sama dengan');
    }

    public function test_sales_sees_status_and_reason_in_tagging_history(): void
    {
        $this->existingCustomer(['phone' => '081234567890']);
        $this->submit(); // ditahan
        $this->submit(['name' => 'Toko Lain Lagi', 'phone' => '085500001111']); // otomatis

        $this->actingAs($this->user)
            ->get(route('sales.tagging.index'))
            ->assertOk()
            ->assertSee('Menunggu Admin')
            ->assertSee('terindikasi duplikat')
            ->assertSee('Otomatis disetujui');
    }

    // ---------------------------------------------------- pending lama (command)

    private function oldPending(string $name, ?string $phone): CustomerTagging
    {
        return CustomerTagging::create([
            'sales_id' => $this->sales->id,
            'name' => $name,
            'phone' => $phone,
            'status' => CustomerTagging::STATUS_PENDING,
            'tagged_at' => now()->subDays(3),
        ]);
    }

    public function test_command_approves_non_duplicates_and_holds_duplicates_oldest_first(): void
    {
        $existing = $this->existingCustomer(['phone' => '081322222222']);

        $unique = $this->oldPending('Toko Alfa', '081311111111');
        $dupOfExisting = $this->oldPending('Toko Beta', '081322222222');
        $dupOfUnique = $this->oldPending('Toko Alfa Dua', '0813-1111-1111'); // lebih baru dari $unique

        $this->artisan('tagging:process-pending')->assertExitCode(0);

        $unique->refresh();
        $this->assertSame(CustomerTagging::STATUS_APPROVED, $unique->status);
        $this->assertTrue($unique->auto_approved);
        $this->assertNotNull($unique->customer_id);

        $dupOfExisting->refresh();
        $this->assertSame(CustomerTagging::STATUS_PENDING, $dupOfExisting->status);
        $this->assertSame($existing->id, $dupOfExisting->duplicate_customer_id);

        // Yang lebih baru ditahan sebagai duplikat dari yang lebih lama (yang sudah jadi Customer).
        $dupOfUnique->refresh();
        $this->assertSame(CustomerTagging::STATUS_PENDING, $dupOfUnique->status);
        $this->assertSame($unique->customer_id, $dupOfUnique->duplicate_customer_id);
    }

    public function test_command_dry_run_changes_nothing(): void
    {
        $pending = $this->oldPending('Toko Alfa', '081311111111');
        $customers = Customer::count();

        $this->artisan('tagging:process-pending', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(CustomerTagging::STATUS_PENDING, $pending->fresh()->status);
        $this->assertSame($customers, Customer::count());
    }

    public function test_command_is_safe_to_run_twice(): void
    {
        $this->existingCustomer(['phone' => '081322222222']);
        $this->oldPending('Toko Alfa', '081311111111');
        $this->oldPending('Toko Beta', '081322222222');

        $this->artisan('tagging:process-pending')->assertExitCode(0);
        $customersAfterFirst = Customer::count();

        $this->artisan('tagging:process-pending')->assertExitCode(0);

        $this->assertSame($customersAfterFirst, Customer::count());
    }
}
