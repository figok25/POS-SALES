<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Sales Field Operations - Offline Location Queue & Server/Device
 * Time (Blueprint #25, #26). location_event_id adalah idempotency key yang
 * dibuat client (uuid) agar retry pengiriman dari offline queue tidak
 * membuat duplikasi. received_at mencatat waktu server menerima, terpisah
 * dari recorded_at (waktu GPS direkam device) karena clock device tidak
 * sepenuhnya dipercaya untuk audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_location_histories', function (Blueprint $table) {
            $table->uuid('location_event_id')->nullable()->unique()->after('sales_id');
            $table->timestamp('received_at')->nullable()->after('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_location_histories', function (Blueprint $table) {
            $table->dropColumn(['location_event_id', 'received_at']);
        });
    }
};
