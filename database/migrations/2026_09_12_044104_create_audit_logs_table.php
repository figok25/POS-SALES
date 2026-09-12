<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Struktur mengikuti Blueprint #45 Audit Log:
     * User, Action, Module, Document, Before, After, Timestamp, IP.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // login, create, update, apply, cancel, payment, settlement, stock_movement, reversal, permission_change, dll
            $table->string('module', 100)->nullable(); // mis. "Inventory", "BKB", "Sales Transaction"
            $table->string('document_type', 100)->nullable(); // mis. model class atau nama dokumen bisnis
            $table->unsignedBigInteger('document_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'document_type', 'document_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
