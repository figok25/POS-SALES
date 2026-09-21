<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Section 2 relasi: Arrival/Check-In -> Visit -> Check-Out -> Next Customer
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visits')) {
            return;
        }

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Sales
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_assignment_id')->nullable()
                ->constrained('customer_assignments')->nullOnDelete();

            $table->decimal('check_in_latitude', 10, 7);
            $table->decimal('check_in_longitude', 10, 7);
            $table->timestamp('check_in_at');

            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->timestamp('check_out_at')->nullable();

            $table->string('status')->default('in_progress'); // in_progress | completed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'check_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
