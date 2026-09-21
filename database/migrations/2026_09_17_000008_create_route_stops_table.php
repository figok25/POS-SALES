<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Section 94: satu sales_route terdiri dari banyak stop (Customer), berurutan.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('route_stops')) {
            return;
        }

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_route_id')->constrained('sales_routes')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->integer('leg_distance_meters')->nullable(); // jarak dari stop sebelumnya
            $table->integer('leg_duration_seconds')->nullable();
            $table->string('status')->default('pending'); // pending | arrived | skipped
            $table->timestamps();

            $table->index(['sales_route_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
