<?php

namespace Tests\Feature\System;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Download CSV Audit Log (dari halaman Audit Log), supaya Admin bisa
 * menyimpan jejak suatu kejadian (mis. bandingkan jam Apply Settlement vs
 * Apply BTB) sebagai bukti offline, mengikuti filter yang sedang aktif.
 */
class AuditLogExportTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_admin_can_download_audit_log_csv(): void
    {
        $admin = $this->makeAdminUser();

        AuditLog::create([
            'user_id' => $admin->id,
            'branch_id' => $admin->branch_id,
            'action' => 'apply',
            'module' => 'Finance',
            'document_type' => 'App\\Models\\Settlement',
            'document_id' => 1,
            'before' => ['status' => 'draft'],
            'after' => ['status' => 'applied'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.system.audit-log.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="audit-log-', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Waktu,Depo,User,Modul,Aksi,"Tipe Dokumen","ID Dokumen",IP,Sebelum,Sesudah', $csv);
        $this->assertStringContainsString('Finance', $csv);
        $this->assertStringContainsString('apply', $csv);
        $this->assertStringContainsString('Settlement', $csv);
    }

    public function test_export_respects_module_filter(): void
    {
        $admin = $this->makeAdminUser();

        AuditLog::create([
            'user_id' => $admin->id, 'branch_id' => $admin->branch_id,
            'action' => 'apply', 'module' => 'Finance',
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $admin->id, 'branch_id' => $admin->branch_id,
            'action' => 'create', 'module' => 'Master Data',
            'created_at' => now(),
        ]);

        $csv = $this->actingAs($admin)
            ->get(route('admin.system.audit-log.export', ['module' => 'Finance']))
            ->streamedContent();

        $this->assertStringContainsString('Finance', $csv);
        $this->assertStringNotContainsString('Master Data', $csv);
    }
}
