<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Istirahat Sales: selama istirahat (ended_at NULL) Sales tidak dihitung
 * "diam" pada Live Monitoring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedBigInteger('tracking_session_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['sales_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_breaks');
    }
};
