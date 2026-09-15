<?php

namespace Tests\Feature\LiveSalesFieldOps;

use App\Models\SalesTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpSalesFixtures;
use Tests\TestCase;

/**
 * Live Sales Field Operations - Test halaman Task Gate (Blueprint #14,
 * Fase 3 WebView Sales).
 */
class TaskGatePageTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpSalesFixtures;

    public function test_page_shows_no_task_message_when_none_exists(): void
    {
        [$user] = $this->makeSalesUser();

        $this->actingAs($user)
            ->get(route('sales.task.show'))
            ->assertOk()
            ->assertSee('Belum Ada Tugas Hari Ini');
    }

    public function test_page_renders_task_with_documents_and_stocks(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $product = $this->makeProduct();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_DOCUMENT_AVAILABLE);
        $task->documents()->create(['type' => 'surat_jalan', 'title' => 'Surat Jalan']);
        $task->taskStocks()->create(['product_id' => $product->id, 'quantity_assigned' => 20]);

        $this->actingAs($user)
            ->get(route('sales.task.show'))
            ->assertOk()
            ->assertSee($task->code)
            ->assertSee('Surat Jalan');
    }

    public function test_full_gate_flow_download_verify_start_work(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $product = $this->makeProduct();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_DOCUMENT_AVAILABLE);
        $document = $task->documents()->create(['type' => 'surat_jalan', 'title' => 'Surat Jalan']);
        $stock = $task->taskStocks()->create(['product_id' => $product->id, 'quantity_assigned' => 20]);

        // 1. Tandai dokumen diterima.
        $this->actingAs($user)
            ->postJson(route('api.sales.tasks.documents.download', [$task, $document]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($document->fresh()->downloaded_at);

        // 2. Submit verifikasi stock (lengkap, tanpa selisih).
        $this->actingAs($user)
            ->postJson(route('api.sales.tasks.verify-stock', $task), [
                'items' => [
                    ['sales_task_stock_id' => $stock->id, 'quantity_verified' => 20],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', SalesTask::STATUS_READY_TO_WORK);

        // 3. Start Work.
        $this->actingAs($user)
            ->postJson(route('api.sales.tasks.start-work', $task))
            ->assertOk()
            ->assertJsonPath('data.status', SalesTask::STATUS_WORKING);

        $this->assertEquals(SalesTask::STATUS_WORKING, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->started_at);
    }

    public function test_partial_stock_verification_keeps_status_stock_verification(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_DOCUMENT_AVAILABLE);
        $stockA = $task->taskStocks()->create(['product_id' => $productA->id, 'quantity_assigned' => 20]);
        $task->taskStocks()->create(['product_id' => $productB->id, 'quantity_assigned' => 10]);

        // Hanya verifikasi 1 dari 2 produk.
        $this->actingAs($user)
            ->postJson(route('api.sales.tasks.verify-stock', $task), [
                'items' => [
                    ['sales_task_stock_id' => $stockA->id, 'quantity_verified' => 20],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', SalesTask::STATUS_STOCK_VERIFICATION);
    }

    public function test_sales_cannot_access_other_sales_task(): void
    {
        [$userA] = $this->makeSalesUser();
        [, $salesB] = $this->makeSalesUser();
        $taskB = $this->makeSalesTask($salesB, SalesTask::STATUS_DOCUMENT_AVAILABLE);

        $this->actingAs($userA)
            ->getJson(route('api.sales.tasks.documents', $taskB))
            ->assertForbidden();
    }

    public function test_start_work_rejected_when_not_ready(): void
    {
        [$user, $sales] = $this->makeSalesUser();
        $task = $this->makeSalesTask($sales, SalesTask::STATUS_STOCK_VERIFICATION);

        $this->actingAs($user)
            ->postJson(route('api.sales.tasks.start-work', $task))
            ->assertForbidden();
    }
}
