<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Section 2: Customer/Toko -- fondasi yang dipertahankan
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
