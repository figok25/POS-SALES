<?php

namespace Tests\Feature\Sales;

use App\Http\Middleware\ResolveBranchContext;
use App\Models\Customer;
use App\Models\KpiPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Pilihan Depo Super Admin diingat (session): tidak kembali ke "Semua Depo"
 * setelah detail/edit/hapus/simpan, sampai ia memilih "Semua Depo" sendiri.
 */
class SuperAdminDepoStickyTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private const KEY = ResolveBranchContext::SESSION_KEY;

    private function depos(): array
    {
        return [$this->defaultBranch(), $this->makeBranch('CBG-S2', 'Depo Dua')];
    }

    public function test_choosing_a_depo_is_remembered_in_session(): void
    {
        [$a] = $this->depos();

        $this->actingAs($this->makeSuperAdminUser())
            ->get(route('admin.master.customers.index', ['branch' => $a->id]))
            ->assertOk()
            ->assertSessionHas(self::KEY, (string) $a->id);
    }

    public function test_remembered_depo_scopes_pages_opened_without_branch_parameter(): void
    {
        [$a, $b] = $this->depos();
        $inA = $this->makeCustomer(null, $a);
        $inB = $this->makeCustomer(null, $b);

        $this->actingAs($this->makeSuperAdminUser())
            ->withSession([self::KEY => (string) $a->id])
            ->get(route('admin.master.customers.index'))
            ->assertOk()
            ->assertSee($inA->name)
            ->assertDontSee($inB->name);
    }

    public function test_choosing_all_depo_clears_the_memory(): void
    {
        [$a] = $this->depos();

        $this->actingAs($this->makeSuperAdminUser())
            ->withSession([self::KEY => (string) $a->id])
            ->get(route('admin.master.customers.index', ['branch' => 'all']))
            ->assertOk()
            ->assertSessionHas(self::KEY, 'all');
    }

    public function test_no_memory_means_all_depo_as_before(): void
    {
        [$a, $b] = $this->depos();
        $inA = $this->makeCustomer(null, $a);
        $inB = $this->makeCustomer(null, $b);

        $this->actingAs($this->makeSuperAdminUser())
            ->get(route('admin.master.customers.index'))
            ->assertOk()->assertSee($inA->name)->assertSee($inB->name);
    }

    public function test_invalid_remembered_depo_falls_back_to_all_and_is_forgotten(): void
    {
        [$a, $b] = $this->depos();
        $inA = $this->makeCustomer(null, $a);
        $inB = $this->makeCustomer(null, $b);

        $this->actingAs($this->makeSuperAdminUser())
            ->withSession([self::KEY => '999999'])
            ->get(route('admin.master.customers.index'))
            ->assertOk()->assertSee($inA->name)->assertSee($inB->name)
            ->assertSessionMissing(self::KEY);
    }

    public function test_write_actions_without_branch_parameter_keep_using_remembered_depo(): void
    {
        [$a] = $this->depos();

        // Tanpa ?branch= pada POST, tetap dianggap Depo tersimpan (bukan "Semua Depo"), jadi tidak butuh pilih Depo lagi.
        $this->actingAs($this->makeSuperAdminUser())
            ->withSession([self::KEY => (string) $a->id])
            ->post(route('admin.sales.kpi.periods.store'), ['name' => 'P1', 'start_date' => '2026-10-19', 'end_date' => '2026-10-24'])
            ->assertSessionHasNoErrors();

        $this->assertSame($a->id, KpiPeriod::firstOrFail()->branch_id);
    }

    public function test_delete_customer_keeps_the_chosen_depo_for_the_next_page(): void
    {
        [$a, $b] = $this->depos();
        $first = $this->makeCustomer(null, $a);
        $other = $this->makeCustomer(null, $b);
        $keep = $this->makeCustomer(null, $a);
        $super = $this->makeSuperAdminUser();

        $this->actingAs($super)->withSession([self::KEY => (string) $a->id])
            ->delete(route('admin.master.customers.destroy', $first))
            ->assertRedirect(route('admin.master.customers.index'));
        $this->assertNull(Customer::find($first->id));

        // Halaman tujuan setelah hapus tetap Depo A (session yang sama).
        $this->actingAs($super)->withSession([self::KEY => (string) $a->id])
            ->get(route('admin.master.customers.index'))
            ->assertSee($keep->name)->assertDontSee($other->name);
    }

    public function test_regular_admin_is_not_affected_by_session_memory(): void
    {
        [$a, $b] = $this->depos();
        $mine = $this->makeCustomer(null, $a);
        $foreign = $this->makeCustomer(null, $b);
        $admin = $this->makeAdminUser($a);

        $this->actingAs($admin)
            ->withSession([self::KEY => (string) $b->id])
            ->get(route('admin.master.customers.index'))
            ->assertOk()->assertSee($mine->name)->assertDontSee($foreign->name);
    }

    public function test_pages_without_depo_filter_show_notice_with_reset_link(): void
    {
        [$a] = $this->depos();

        $this->actingAs($this->makeSuperAdminUser())
            ->withSession([self::KEY => (string) $a->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tampilkan semua Depo')
            ->assertSee('branch=all', false);
    }
}
