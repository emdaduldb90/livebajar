<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('tracking_checkout_id')->nullable()->unique()->after('order_number');
            $table->uuid('tracking_purchase_event_id')->nullable()->unique()->after('tracking_checkout_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['tracking_checkout_id']);
            $table->dropUnique(['tracking_purchase_event_id']);
            $table->dropColumn(['tracking_checkout_id', 'tracking_purchase_event_id']);
        });
    }
};
