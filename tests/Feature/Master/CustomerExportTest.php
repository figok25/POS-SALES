<?php

namespace Tests\Feature\Master;

use App\Models\SalesVisitPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Export Master Data > Customer: kolom lengkap (koordinat, link peta, hari
 * kunjungan, dst.) dan aman dari formula injection.
 */
class CustomerExportTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    private function sheetOf($response): Worksheet
    {
        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'cust').'.xlsx';
        file_put_contents($path, $response->getContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $sheet;
    }

    /** @return array<string, int> nama kolom => nomor kolom (1-based), dari baris header (baris 3). */
    private function headers(Worksheet $sheet): array
    {
        $map = [];
        foreach ($sheet->getRowIterator(3, 3) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $map[(string) $cell->getValue()] = $cell->getColumn();
            }
        }

        return $map;
    }

    public function test_export_has_complete_columns_including_coordinates(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        $customer->update([
            'name' => 'Toko Sumber Rejeki',
            'latitude' => -8.0954321,
            'longitude' => 112.1609123,
            'location_accuracy' => 12.5,
            'location_status' => 'verified',
        ]);

        $sheet = $this->sheetOf($this->actingAs($admin)->get(route('admin.master.customers.export')));
        $h = $this->headers($sheet);

        foreach (['Kode', 'Nama', 'Latitude', 'Longitude', 'Akurasi Lokasi (m)', 'Status Lokasi', 'Link Google Maps', 'Hari Kunjungan', 'Sales Sebelumnya', 'Total Kunjungan', 'Kunjungan Terakhir', 'Dibuat'] as $name) {
            $this->assertArrayHasKey($name, $h, "Kolom {$name} tidak ada");
        }

        $this->assertSame('Toko Sumber Rejeki', $sheet->getCell($h['Nama'].'4')->getValue());
        $this->assertEqualsWithDelta(-8.0954321, (float) $sheet->getCell($h['Latitude'].'4')->getValue(), 0.0000001);
        $this->assertEqualsWithDelta(112.1609123, (float) $sheet->getCell($h['Longitude'].'4')->getValue(), 0.0000001);
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell($h['Latitude'].'4')->getDataType());   // angka, bukan teks
        $this->assertSame('Terverifikasi', $sheet->getCell($h['Status Lokasi'].'4')->getValue());
        $this->assertStringStartsWith('https://www.google.com/maps?q=-8.0954321,112.1609123', (string) $sheet->getCell($h['Link Google Maps'].'4')->getValue());
    }

    public function test_customer_without_location_has_blank_coordinates(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id)->update(['latitude' => null, 'longitude' => null, 'location_status' => null]);

        $sheet = $this->sheetOf($this->actingAs($admin)->get(route('admin.master.customers.export')));
        $h = $this->headers($sheet);

        $this->assertNull($sheet->getCell($h['Latitude'].'4')->getValue());
        $this->assertSame('Belum ada lokasi', $sheet->getCell($h['Status Lokasi'].'4')->getValue());
        $this->assertNull($sheet->getCell($h['Link Google Maps'].'4')->getValue());
    }

    public function test_export_lists_visit_plan_days(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $customer = $this->makeCustomer($sales->id);
        foreach ([3, 1] as $day) {
            SalesVisitPlan::create(['sales_id' => $sales->id, 'day_of_week' => $day, 'customer_id' => $customer->id, 'sequence' => 0, 'source' => 'manual']);
        }

        $sheet = $this->sheetOf($this->actingAs($admin)->get(route('admin.master.customers.export')));
        $h = $this->headers($sheet);

        $this->assertSame('Senin, Rabu', $sheet->getCell($h['Hari Kunjungan'].'4')->getValue());
    }

    public function test_export_follows_active_filters(): void
    {
        $admin = $this->makeAdminUser();
        [, $salesA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $this->makeCustomer($salesA->id)->update(['name' => 'Toko Milik A']);
        $this->makeCustomer($salesB->id)->update(['name' => 'Toko Milik B']);

        $sheet = $this->sheetOf($this->actingAs($admin)->get(route('admin.master.customers.export', ['sales_id' => $salesA->id])));
        $h = $this->headers($sheet);

        $this->assertSame('Toko Milik A', $sheet->getCell($h['Nama'].'4')->getValue());
        $this->assertNull($sheet->getCell($h['Nama'].'5')->getValue());   // hanya satu baris data
    }

    public function test_cells_starting_with_formula_characters_are_stored_as_text(): void
    {
        $admin = $this->makeAdminUser();
        [, $sales] = $this->makeSalesUser();
        $this->makeCustomer($sales->id)->update(['name' => '=HYPERLINK("http://evil.example","klik")', 'address' => '@SUM(1+1)']);

        $sheet = $this->sheetOf($this->actingAs($admin)->get(route('admin.master.customers.export')));
        $h = $this->headers($sheet);

        $name = $sheet->getCell($h['Nama'].'4');
        $this->assertSame(DataType::TYPE_STRING, $name->getDataType());
        $this->assertSame('=HYPERLINK("http://evil.example","klik")', $name->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($h['Alamat'].'4')->getDataType());
    }
}
