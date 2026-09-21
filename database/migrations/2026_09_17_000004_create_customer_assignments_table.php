<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Section 2 relasi: Sales -> Customer Assignment -> Customer
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_assignments')) {
            return;
        }

        Schema::create('customer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Sales
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->unsignedInteger('sequence')->default(0); // urutan kunjungan hari itu
            $table->string('status')->default('pending'); // pending | visited | skipped
            $table->timestamps();

            $table->unique(['user_id', 'customer_id', 'assigned_date'], 'uniq_assignment_per_day');
            $table->index(['user_id', 'assigned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_assignments');
    }
};
