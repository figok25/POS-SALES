<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Task Documents (Blueprint #13.5).
 * Dokumen (Surat Jalan, Barang Keluar, Daftar Stock, dll) yang dirilis ke
 * Sales bersamaan dengan Apply Task. reference_type/reference_id opsional
 * menghubungkan ke dokumen sumber (mis. BkbDistribusi, DeliveryOrder) bila
 * ada, agar tidak menduplikasi data dokumen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_task_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_task_id')->constrained('sales_tasks')->cascadeOnDelete();
            $table->string('type'); // surat_jalan | barang_keluar | daftar_stock | other
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->nullableMorphs('reference');
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_task_documents');
    }
};
