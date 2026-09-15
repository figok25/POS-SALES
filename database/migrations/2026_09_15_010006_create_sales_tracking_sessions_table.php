<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Tracking Session (Blueprint #21).
 * Dibuat saat Sales melakukan Start Work/Start Tracking, terkait dengan
 * Sales Task yang aktif (Blueprint #13.7) agar histori perjalanan dapat
 * ditelusuri berasal dari pekerjaan yang mana.
 *
 * Lifecycle: active -> completed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('sales_task_id')->nullable()->constrained('sales_tasks')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('sales_devices')->nullOnDelete();

            $table->timestamp('started_at');
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();

            $table->timestamp('ended_at')->nullable();
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();

            $table->string('status')->default('active'); // active | completed

            $table->timestamps();

            $table->index(['sales_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_tracking_sessions');
    }
};
