<?php

namespace Tests\Feature\MultiBranch;

use App\Models\Branch;
use App\Models\CashLedger;
use App\Services\CashLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Multi Branch/Depo - Cash Ledger (Income & Expense) per-Depo.
 */
class CashLedgerBranchTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function entry(Branch $branch, string $category, string $type = 'expense', float $amount = 100000): CashLedger
    {
        static $n = 0;
        $n++;

        return CashLedger::create([
            'branch_id' => $branch->id,
            'code' => "CL-TEST-{$n}",
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'date' => now()->toDateString(),
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'type' => 'expense',
            'category' => 'ATK',
            'amount' => 5000,
            'date' => now()->toDateString(),
        ], $override);
    }

    public function test_service_defaults_branch_to_creator_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $adminA = $this->makeAdminUser($branchA);

        $entry = app(CashLedgerService::class)->create($this->payload(), $adminA->id);

        $this->assertSame($branchA->id, $entry->branch_id);
    }

    public function test_admin_only_sees_own_branch_entries_and_totals(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->entry($branchA, 'Kategori-Alfa', 'income', 123000);
        $this->entry($branchB, 'Kategori-Beta', 'income', 987000);

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->get(route('admin.finance.cash-ledgers.index'))
            ->assertOk()
            ->assertSee('Kategori-Alfa')
            ->assertDontSee('Kategori-Beta')
            // Ringkasan juga hanya menjumlahkan kas Depo sendiri.
            ->assertSee('123.000')
            ->assertDontSee('987.000');
    }

    public function test_admin_store_forces_own_branch_ignoring_payload(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->post(route('admin.finance.cash-ledgers.store'), $this->payload(['branch_id' => $branchB->id, 'category' => 'Suntik-B']))
            ->assertRedirect(route('admin.finance.cash-ledgers.index'));

        $entry = CashLedger::where('category', 'Suntik-B')->firstOrFail();
        $this->assertSame($branchA->id, $entry->branch_id);
    }

    public function test_super_admin_must_choose_branch_when_storing(): void
    {
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $superAdmin = $this->makeSuperAdminUser();

        $this->actingAs($superAdmin)
            ->post(route('admin.finance.cash-ledgers.store'), $this->payload(['category' => 'Tanpa-Depo']))
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseMissing('cash_ledgers', ['category' => 'Tanpa-Depo']);

        $this->actingAs($superAdmin)
            ->post(route('admin.finance.cash-ledgers.store'), $this->payload(['category' => 'Dengan-Depo', 'branch_id' => $branchB->id]))
            ->assertRedirect(route('admin.finance.cash-ledgers.index'));

        $this->assertSame($branchB->id, CashLedger::where('category', 'Dengan-Depo')->firstOrFail()->branch_id);
    }

    public function test_admin_cannot_delete_other_branch_entry_but_can_delete_own(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $own = $this->entry($branchA, 'Milik-A');
        $other = $this->entry($branchB, 'Milik-B');

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->delete(route('admin.finance.cash-ledgers.destroy', $other))
            ->assertForbidden();
        $this->assertDatabaseHas('cash_ledgers', ['id' => $other->id]);

        $this->actingAs($adminA)
            ->delete(route('admin.finance.cash-ledgers.destroy', $own))
            ->assertRedirect(route('admin.finance.cash-ledgers.index'));
        $this->assertDatabaseMissing('cash_ledgers', ['id' => $own->id]);
    }

    public function test_super_admin_sees_all_branches_and_can_filter_one(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->entry($branchA, 'Kategori-Alfa');
        $this->entry($branchB, 'Kategori-Beta');

        $superAdmin = $this->makeSuperAdminUser();

        $this->actingAs($superAdmin)
            ->get(route('admin.finance.cash-ledgers.index'))
            ->assertOk()
            ->assertSee('Kategori-Alfa')
            ->assertSee('Kategori-Beta');

        $this->actingAs($superAdmin)
            ->get(route('admin.finance.cash-ledgers.index', ['branch' => $branchA->id]))
            ->assertOk()
            ->assertSee('Kategori-Alfa')
            ->assertDontSee('Kategori-Beta');
    }
}
