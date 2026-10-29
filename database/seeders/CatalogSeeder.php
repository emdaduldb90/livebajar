<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets & Audio'],
            ['name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Apparel & Wear'],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'description' => 'Decor & Comfort'],
            ['name' => 'Groceries', 'slug' => 'groceries', 'description' => 'Organic & Fresh'],
        ])->mapWithKeys(fn (array $category) => [
            $category['slug'] => Category::query()->updateOrCreate(['slug' => $category['slug']], $category),
        ]);

        $products = [
            ['sku' => 'LB-EL-001', 'category' => 'electronics', 'name' => 'Premium Wireless Noise-Cancelling Headphones', 'slug' => 'premium-wireless-noise-cancelling-headphones', 'price' => 149.99, 'compare_at_price' => 199.99, 'rating' => 4.8, 'reviews_count' => 124, 'badge' => 'Best Seller', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Wireless+Headphones', 'description' => 'Immerse yourself in pure acoustic bliss with industry-leading noise cancellation, 40-hour battery life, and ultra-comfortable memory foam ear cushions crafted for all-day listening.'],
            ['sku' => 'LB-EL-002', 'category' => 'electronics', 'name' => 'Smart Watch Series 5 Fitness Tracker', 'slug' => 'smart-watch-series-5-fitness-tracker', 'price' => 199.99, 'compare_at_price' => 249.99, 'rating' => 4.7, 'reviews_count' => 98, 'badge' => 'Sale', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Smart+Watch', 'description' => 'Track your health metrics, heart rate, GPS workouts, and smartphone notifications directly from your wrist on a stunning high-resolution OLED touchscreen.'],
            ['sku' => 'LB-FA-001', 'category' => 'fashion', 'name' => 'Minimalist Genuine Leather Jacket', 'slug' => 'minimalist-genuine-leather-jacket', 'price' => 120.00, 'compare_at_price' => 160.00, 'rating' => 4.9, 'reviews_count' => 76, 'badge' => 'Hot', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Leather+Jacket', 'description' => 'Crafted from premium full-grain leather with tailored slim fit lines, durable YKK brass zippers, and smooth satin inner lining for timeless style.'],
            ['sku' => 'LB-FA-002', 'category' => 'fashion', 'name' => 'Classic Slim-Fit Denim Jeans', 'slug' => 'classic-slim-fit-denim-jeans', 'price' => 55.00, 'compare_at_price' => 70.00, 'rating' => 4.5, 'reviews_count' => 210, 'badge' => null, 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Denim+Jeans', 'description' => 'Engineered stretch denim offering exceptional comfort without sacrificing durability or classic vintage wash aesthetics.'],
            ['sku' => 'LB-HO-001', 'category' => 'home-living', 'name' => 'Ergonomic Mesh Office & Gaming Chair', 'slug' => 'ergonomic-mesh-office-gaming-chair', 'price' => 249.99, 'compare_at_price' => 299.99, 'rating' => 4.8, 'reviews_count' => 154, 'badge' => 'Top Rated', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Office+Chair', 'description' => 'Support your posture through long work or gaming sessions with adjustable lumbar support, 3D armrests, and breathable high-tension mesh.'],
            ['sku' => 'LB-HO-002', 'category' => 'home-living', 'name' => 'Handcrafted Ceramic Nordic Vase Set', 'slug' => 'handcrafted-ceramic-nordic-vase-set', 'price' => 45.00, 'compare_at_price' => 60.00, 'rating' => 4.6, 'reviews_count' => 42, 'badge' => null, 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Ceramic+Vase', 'description' => 'Elevate your interior decor with matte textured ceramic vases designed with minimalist Nordic silhouettes suitable for fresh or dried florals.'],
            ['sku' => 'LB-GR-001', 'category' => 'groceries', 'name' => 'Pure Raw Wildflower Artisanal Honey', 'slug' => 'pure-raw-wildflower-artisanal-honey', 'price' => 18.50, 'compare_at_price' => 22.00, 'rating' => 4.9, 'reviews_count' => 89, 'badge' => 'Organic', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Artisanal+Honey', 'description' => 'Unfiltered, unheated raw honey harvested from pristine mountain meadows. Packed with natural antioxidants, enzymes, and rich floral notes.'],
            ['sku' => 'LB-GR-002', 'category' => 'groceries', 'name' => 'Fresh Roasted Ethiopian Coffee Beans', 'slug' => 'fresh-roasted-ethiopian-coffee-beans', 'price' => 22.00, 'compare_at_price' => 26.00, 'rating' => 4.7, 'reviews_count' => 112, 'badge' => 'Fresh', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Coffee+Beans', 'description' => 'Single-origin Arabica coffee beans boasting vibrant notes of blueberry, jasmine, and dark chocolate with medium roast complexity.'],
            ['sku' => 'LB-EL-003', 'category' => 'electronics', 'name' => 'Ultra HD 4K Waterproof Action Camera', 'slug' => 'ultra-hd-4k-waterproof-action-camera', 'price' => 299.00, 'compare_at_price' => 350.00, 'rating' => 4.6, 'reviews_count' => 65, 'badge' => null, 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Action+Camera', 'description' => 'Capture your wildest adventures in crisp 4K 60fps video with advanced image stabilization, dual color screens, and 30m waterproof casing.'],
            ['sku' => 'LB-FA-003', 'category' => 'fashion', 'name' => 'Designer Breathable Knit Sneakers', 'slug' => 'designer-breathable-knit-sneakers', 'price' => 85.00, 'compare_at_price' => 110.00, 'rating' => 4.7, 'reviews_count' => 130, 'badge' => 'Popular', 'image_url' => 'https://placehold.co/600x600/f5f2eb/2d2d2d?text=Knit+Sneakers', 'description' => 'Ultralight cushioned sneakers featuring engineered knit uppers for maximum breathability and responsive cloud-like sole cushioning.'],
        ];

        foreach ($products as $index => $product) {
            $category = $categories->get($product['category']);
            unset($product['category']);
            $product['category_id'] = $category->id;
            $product['short_description'] = $product['description'];
            $product['stock_quantity'] = 100;
            $product['is_featured'] = $index < 4;
            $product['is_active'] = true;

            $savedProduct = Product::query()->updateOrCreate(['sku' => $product['sku']], $product);
            $savedProduct->categories()->syncWithoutDetaching([$category->id]);
        }

        Coupon::query()->updateOrCreate(['code' => 'LIVE10'], [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 0,
            'is_active' => true,
        ]);
    }
}
