<?php

namespace Tests\Feature\Finance;

use App\Models\CashLedger;
use App\Services\CashLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Phase 7 - Test untuk CashLedgerService (Income & Expense).
 */
class CashLedgerTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_create_income_entry(): void
    {
        $admin = $this->makeAdminUser();

        $entry = app(CashLedgerService::class)->create([
            'type' => 'income',
            'category' => 'Lain-lain',
            'amount' => 500000,
            'date' => now()->toDateString(),
            'description' => 'Sewa gudang lama',
        ], $admin->id);

        $this->assertEquals(CashLedger::TYPE_INCOME, $entry->type);
        $this->assertStringStartsWith('INC-', $entry->code);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'document_type' => CashLedger::class,
            'user_id' => $admin->id,
        ]);
    }

    public function test_create_expense_entry(): void
    {
        $admin = $this->makeAdminUser();

        $entry = app(CashLedgerService::class)->create([
            'type' => 'expense',
            'category' => 'ATK',
            'amount' => 150000,
            'date' => now()->toDateString(),
        ], $admin->id);

        $this->assertEquals(CashLedger::TYPE_EXPENSE, $entry->type);
        $this->assertStringStartsWith('EXP-', $entry->code);
    }

    public function test_delete_entry_removes_it_and_logs_audit(): void
    {
        $admin = $this->makeAdminUser();
        $service = app(CashLedgerService::class);

        $entry = $service->create([
            'type' => 'expense', 'category' => 'Listrik', 'amount' => 200000, 'date' => now()->toDateString(),
        ], $admin->id);

        $service->delete($entry, $admin->id);

        $this->assertDatabaseMissing('cash_ledgers', ['id' => $entry->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'delete', 'document_type' => CashLedger::class]);
    }
}
