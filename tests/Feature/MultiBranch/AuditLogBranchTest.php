<?php

namespace Tests\Feature\MultiBranch;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Multi Branch/Depo - Audit Log per-Depo (branch_id diisi otomatis oleh
 * AuditLogger; tampilan System > Audit Log dan Reports > Audit di-scope).
 */
class AuditLogBranchTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function log(?Branch $branch, string $module): AuditLog
    {
        return AuditLog::create([
            'user_id' => null,
            'branch_id' => $branch?->id,
            'action' => 'create',
            'module' => $module,
            'created_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------
    // AuditLogger: penentuan branch_id otomatis
    // ---------------------------------------------------------------

    public function test_logger_uses_actor_branch_by_default(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $adminA = $this->makeAdminUser($branchA);

        $log = AuditLogger::log('create', 'Modul-X', null, null, null, null, $adminA->id);

        $this->assertSame($branchA->id, $log->branch_id);
    }

    public function test_logger_prefers_document_branch_over_actor(): void
    {
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $superAdmin = $this->makeSuperAdminUser();

        // Super Admin (tanpa Depo) mengubah dokumen milik Depo B.
        $log = AuditLogger::log('update', 'Modul-X', null, null, ['branch_id' => $branchB->id], ['branch_id' => $branchB->id], $superAdmin->id);

        $this->assertSame($branchB->id, $log->branch_id);
    }

    public function test_logger_is_global_for_super_admin_without_branch_document(): void
    {
        $superAdmin = $this->makeSuperAdminUser();

        $log = AuditLogger::log('login', 'Auth', null, null, null, null, $superAdmin->id);

        $this->assertNull($log->branch_id);
    }

    public function test_logger_explicit_branch_wins(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $adminA = $this->makeAdminUser($branchA);

        $log = AuditLogger::log('create', 'Modul-X', null, null, null, null, $adminA->id, $branchB->id);

        $this->assertSame($branchB->id, $log->branch_id);
    }

    // ---------------------------------------------------------------
    // System > Audit Log
    // ---------------------------------------------------------------

    public function test_admin_only_sees_own_branch_logs_in_system_audit_log(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->log($branchA, 'Modul-Alfa');
        $this->log($branchB, 'Modul-Beta');
        $this->log(null, 'Modul-Global');

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->get(route('admin.system.audit-log.index'))
            ->assertOk()
            ->assertSee('Modul-Alfa')
            ->assertDontSee('Modul-Beta')
            ->assertDontSee('Modul-Global');
    }

    public function test_admin_cannot_open_other_branch_or_global_log_detail(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $own = $this->log($branchA, 'Modul-Alfa');
        $other = $this->log($branchB, 'Modul-Beta');
        $global = $this->log(null, 'Modul-Global');

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)->get(route('admin.system.audit-log.show', $own))->assertOk();
        $this->actingAs($adminA)->get(route('admin.system.audit-log.show', $other))->assertForbidden();
        $this->actingAs($adminA)->get(route('admin.system.audit-log.show', $global))->assertForbidden();
    }

    public function test_super_admin_sees_all_logs_including_global_and_can_filter_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->log($branchA, 'Modul-Alfa');
        $this->log($branchB, 'Modul-Beta');
        $this->log(null, 'Modul-Global');

        $superAdmin = $this->makeSuperAdminUser();

        $this->actingAs($superAdmin)
            ->get(route('admin.system.audit-log.index'))
            ->assertOk()
            ->assertSee('Modul-Alfa')
            ->assertSee('Modul-Beta')
            ->assertSee('Modul-Global');

        $this->actingAs($superAdmin)
            ->get(route('admin.system.audit-log.index', ['branch' => $branchA->id]))
            ->assertOk()
            ->assertSee('Modul-Alfa')
            ->assertDontSee('Modul-Beta')
            ->assertDontSee('Modul-Global');
    }

    // ---------------------------------------------------------------
    // Reports > Audit (tampilan & export)
    // ---------------------------------------------------------------

    public function test_admin_report_audit_is_scoped_to_own_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->log($branchA, 'Modul-Alfa');
        $this->log($branchB, 'Modul-Beta');
        $this->log(null, 'Modul-Global');

        $adminA = $this->makeAdminUser($branchA);

        $this->actingAs($adminA)
            ->get(route('admin.reports.audit'))
            ->assertOk()
            ->assertSee('Modul-Alfa')
            ->assertDontSee('Modul-Beta')
            ->assertDontSee('Modul-Global');
    }

    public function test_admin_report_audit_export_only_contains_own_branch(): void
    {
        $branchA = $this->makeBranch('CBG-A', 'Cabang A');
        $branchB = $this->makeBranch('CBG-B', 'Cabang B');
        $this->log($branchA, 'Modul-Alfa');
        $this->log($branchB, 'Modul-Beta');

        $adminA = $this->makeAdminUser($branchA);

        $content = $this->actingAs($adminA)
            ->get(route('admin.reports.audit', ['export' => 'json']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Modul-Alfa', $content);
        $this->assertStringNotContainsString('Modul-Beta', $content);
    }
}
