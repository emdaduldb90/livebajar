<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->json('key_features')->nullable()->after('description');
            $table->json('specifications')->nullable()->after('key_features');
            $table->json('faqs')->nullable()->after('specifications');
            $table->json('customer_reviews')->nullable()->after('faqs');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['key_features', 'specifications', 'faqs', 'customer_reviews']);
        });
    }
};
