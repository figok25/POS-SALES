<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Sales Task / Penugasan (Blueprint #14).
 * Gate utama sebelum Sales dapat mengakses fitur operasional:
 *
 * draft -> applied -> document_available -> stock_verification
 *       -> ready_to_work -> working -> completed  (atau cancelled dari draft)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('task_date');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['sales_id', 'task_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_tasks');
    }
};
