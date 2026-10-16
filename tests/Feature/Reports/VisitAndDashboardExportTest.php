<?php

namespace Tests\Feature\Reports;

use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Export Excel/PDF untuk halaman Visit (mengikuti filter) dan Dashboard.
 */
class VisitAndDashboardExportTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function makeVisit(\App\Models\Sales $sales, \App\Models\Customer $customer, string $at, string $condition = 'normal'): Visit
    {
        return Visit::create([
            'sales_id' => $sales->id,
            'customer_id' => $customer->id,
            'check_in_at' => $at,
            'check_out_at' => \Illuminate\Support\Carbon::parse($at)->addMinutes(20),
            'check_in_latitude' => -6.2,
            'check_in_longitude' => 106.8,
            'check_in_condition' => $condition,
            'status' => Visit::STATUS_COMPLETED,
        ]);
    }

    private function xlsxRows(string $content): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($tmp, $content);
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getSheet(0);
        @unlink($tmp);

        return $sheet->toArray(null, true, true, false);
    }

    public function test_visit_export_xlsx_follows_filters(): void
    {
        $admin = $this->makeAdminUser();
        [, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $custA = $this->makeCustomer($salesA->id);
        $custB = $this->makeCustomer($salesB->id);

        $this->makeVisit($salesA, $custA, '2026-10-05 09:00:00');
        $this->makeVisit($salesB, $custB, '2026-10-06 09:00:00');
        $this->makeVisit($salesA, $custA, '2026-09-01 09:00:00');

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.export', [
            'format' => 'xlsx', 'sales_id' => $salesA->id, 'date_from' => '2026-10-01', 'date_to' => '2026-10-31',
        ]));

        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $res->headers->get('Content-Disposition'));

        $rows = $this->xlsxRows($res->getContent());
        $flat = json_encode($rows);
        $this->assertStringContainsString($custA->name, $flat);
        $this->assertStringNotContainsString($custB->name, $flat, 'Sales lain harus tersaring.');
        // 1 kunjungan cocok filter (yang September di luar periode).
        $dataRows = array_filter($rows, fn ($r) => is_int($r[0] ?? null) || (is_numeric($r[0] ?? null) && ($r[2] ?? '') !== ''));
        $this->assertCount(1, $dataRows);
    }

    public function test_visit_export_pdf_returns_pdf_file(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $cust = $this->makeCustomer($sales->id);
        $this->makeVisit($sales, $cust, '2026-10-05 09:00:00');

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.export', ['format' => 'pdf']));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_visit_export_respects_depo_scope_for_admin(): void
    {
        $depoB = $this->makeBranch('CBG-EXP-B', 'Depo Lain');
        $admin = $this->makeAdminUser(); // Depo default
        [, $salesOther] = $this->makeSalesUser($depoB);
        $custOther = $this->makeCustomer($salesOther->id, $depoB);
        $this->makeVisit($salesOther, $custOther, '2026-10-05 09:00:00');

        $res = $this->actingAs($admin)->get(route('admin.sales.visits.export', ['format' => 'xlsx']));

        $res->assertOk();
        $this->assertStringNotContainsString($custOther->name, json_encode($this->xlsxRows($res->getContent())));
    }

    public function test_visit_export_requires_permission(): void
    {
        [$salesUser] = $this->makeSalesUser();

        $this->actingAs($salesUser)->get(route('admin.sales.visits.export', ['format' => 'xlsx']))->assertForbidden();
    }

    public function test_dashboard_export_xlsx_and_pdf(): void
    {
        $admin = $this->makeAdminUser();

        $xlsx = $this->actingAs($admin)->get(route('admin.dashboard.export', ['format' => 'xlsx']));
        $xlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('Content-Type'));
        $this->assertSame('Ringkasan', \PhpOffice\PhpSpreadsheet\IOFactory::load($this->tmp($xlsx->getContent()))->getSheet(0)->getTitle());

        $pdf = $this->actingAs($admin)->get(route('admin.dashboard.export', ['format' => 'pdf']));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_dashboard_page_shows_export_choices(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)->get(route('admin.dashboard', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Excel (.xlsx)')
            ->assertSee('PDF (.pdf)')
            ->assertSee('dashboard/export?date_from=2026-10-01', false);
    }

    private function tmp(string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($tmp, $content);

        return $tmp;
    }
}
