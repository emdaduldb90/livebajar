<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512)->unique();
            $table->string('original_name', 255);
            $table->string('alt_text', 255)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->decimal('purchase_price', 12, 2)->nullable()->after('compare_at_price');
            $table->foreignId('image_asset_id')->nullable()->after('image_url')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('media_asset_id')->nullable()->after('product_id')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('purchase_price');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_asset_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('image_asset_id');
            $table->dropColumn('purchase_price');
        });

        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('brands');
    }
};
