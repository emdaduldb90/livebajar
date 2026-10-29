<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_zone', 30)->default('dhaka')->after('city');
            $table->index(['shipping_zone', 'created_at'], 'orders_shipping_zone_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_shipping_zone_created_idx');
            $table->dropColumn('shipping_zone');
        });
    }
};
