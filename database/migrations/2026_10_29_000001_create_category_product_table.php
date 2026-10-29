<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'category_id']);
            $table->index('category_id');
        });

        DB::table('products')
            ->select(['id', 'category_id'])
            ->orderBy('id')
            ->chunkById(500, function ($products): void {
                $categoryLinks = $products->map(fn (object $product): array => [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                ])->all();

                if ($categoryLinks !== []) {
                    DB::table('category_product')->insertOrIgnore($categoryLinks);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
