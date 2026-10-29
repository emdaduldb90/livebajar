<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 180)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('meta_title', 70)->nullable()->after('customer_reviews');
            $table->string('meta_description', 160)->nullable()->after('meta_title');
            $table->string('meta_keywords', 255)->nullable()->after('meta_description');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('meta_title', 70)->nullable()->after('description');
            $table->string('meta_description', 160)->nullable()->after('meta_title');
            $table->string('meta_keywords', 255)->nullable()->after('meta_description');
        });

        $now = now();
        foreach ([
            ['Fast Delivery', 'fast-delivery', 'Fast, careful delivery across Bangladesh.', "## Fast Delivery\n\nWe process confirmed orders quickly and pack every product carefully before dispatch. Delivery charges and estimated timing are shown clearly at checkout.\n\nFor orders over ৳150.00, eligible orders receive free delivery according to the current store policy."],
            ['Secure Checkout', 'secure-checkout', 'Safe checkout and protected order information.', "## Secure Checkout\n\nYour order information is handled through secure checkout practices. We collect only the information needed to process, deliver and support your order.\n\nPlease review your address and phone number before placing an order so our delivery team can reach you easily."],
            ['30-Day Return Policy', '30-day-return-policy', 'Easy return and exchange support within 30 days.', "## 30-Day Return & Exchange Policy\n\nIf you need help with an eligible item, contact our support team within 30 days of delivery. Keep the product, packaging and proof of purchase available so we can review your request.\n\nReturn and exchange eligibility may depend on the product condition and category."],
            ['24/7 Dedicated Support', 'dedicated-support', 'Helpful support whenever you need it.', "## 24/7 Dedicated Support\n\nOur support team is ready to help with product, order and delivery questions. Please share your order number when contacting us so we can assist you faster."],
        ] as [$title, $slug, $excerpt, $content]) {
            DB::table('pages')->insert([
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'meta_title' => $title.' | Live Bajar',
                'meta_description' => Str::limit($excerpt, 160, ''),
                'meta_keywords' => strtolower(str_replace(' ', ',', $title)).',live bajar',
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('site_settings')->insert([
            [
                'key' => 'header_menu',
                'value' => json_encode([
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Shop', 'url' => '/shop'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'key' => 'footer_care_links',
                'value' => json_encode([
                    ['label' => 'Fast delivery', 'url' => '/fast-delivery'],
                    ['label' => 'Secure checkout', 'url' => '/secure-checkout'],
                    ['label' => '30-day return policy', 'url' => '/30-day-return-policy'],
                    ['label' => '24/7 dedicated support', 'url' => '/dedicated-support'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords']));
        Schema::dropIfExists('pages');
        Schema::dropIfExists('site_settings');
    }
};
