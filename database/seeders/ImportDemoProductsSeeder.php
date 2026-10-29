<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ImportDemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'electronics' => Category::query()->firstOrCreate(
                ['slug' => 'electronics'],
                ['name' => 'Electronics', 'description' => 'Gadgets & Audio', 'is_active' => true]
            ),
            'home-living' => Category::query()->firstOrCreate(
                ['slug' => 'home-living'],
                ['name' => 'Home & Living', 'description' => 'Decor & Comfort', 'is_active' => true]
            ),
            'health-beauty' => Category::query()->firstOrCreate(
                ['slug' => 'health-beauty'],
                ['name' => 'Health & Beauty', 'description' => 'Daily wellness essentials', 'is_active' => true]
            ),
        ];

        $products = [
            [
                'category' => 'electronics',
                'sku' => 'DEMO-IU-CAMERA-01',
                'name' => 'Smart WiFi Camera',
                'slug' => 'smart-wifi-camera',
                'price' => 750,
                'compare_at_price' => 1850,
                'image_url' => 'products/smart-wifi-camera/01.png',
                'badge' => 'New',
                'short_description' => 'Compact WiFi camera for everyday home and office monitoring.',
                'description' => 'মনোযোগ: বাসা, অফিস বা দোকানের গুরুত্বপূর্ণ জায়গা চোখের আড়ালে থাকলে দুশ্চিন্তা বাড়ে। আগ্রহ: এই কম্প্যাক্ট Smart WiFi Camera সহজ সেটআপের মাধ্যমে দৈনন্দিন জায়গা পর্যবেক্ষণে সহায়তা করে। আকাঙ্ক্ষা: ছোট আকারে সহজে রাখা যায় এবং প্রয়োজনের সময়ে নিজের জায়গার দিকে নজর রাখার সুবিধা দেয়। পদক্ষেপ: আজই আপনার নিরাপত্তা setup-এ এই camera যোগ করুন।',
            ],
            [
                'category' => 'home-living',
                'sku' => 'DEMO-IU-JHURY-10',
                'name' => 'Multipurpose Jhury 10 Inch',
                'slug' => 'multipurpose-jhury-10-inch',
                'price' => 850,
                'compare_at_price' => 2000,
                'image_url' => 'products/multipurpose-jhury-10-inch/01.png',
                'badge' => 'Popular',
                'short_description' => 'A spacious 10-inch utility basket for everyday home organization.',
                'description' => 'মনোযোগ: রান্নাঘর, বাথরুম বা dressing table-এ ছোট জিনিস ছড়িয়ে থাকলে জায়গা অগোছালো দেখায়। আগ্রহ: 10-inch Multipurpose Jhury প্রয়োজনীয় জিনিস এক জায়গায় গুছিয়ে রাখতে সাহায্য করে। আকাঙ্ক্ষা: এর খোলা utility design দৈনন্দিন ব্যবহারকে সহজ ও পরিপাটি রাখে। পদক্ষেপ: ঘরের ছোটখাটো storage সাজাতে এখনই এই Jhury বেছে নিন।',
            ],
            [
                'category' => 'home-living',
                'sku' => 'DEMO-IU-JHURY-02',
                'name' => 'Multipurpose Jhury',
                'slug' => 'multipurpose-jhury',
                'price' => 750,
                'compare_at_price' => 2700,
                'image_url' => 'products/multipurpose-jhury-8-inch/01.png',
                'badge' => 'Best Seller',
                'short_description' => 'A compact utility basket for neat, accessible home storage.',
                'description' => 'মনোযোগ: প্রয়োজনের জিনিস খুঁজতে সময় নষ্ট হওয়া খুবই বিরক্তিকর। আগ্রহ: এই Multipurpose Jhury ছোট accessories, toiletries বা kitchen essentials আলাদা করে রাখতে সহায়তা করে। আকাঙ্ক্ষা: হালকা ও ব্যবহারবান্ধব design আপনার প্রতিদিনের organization routine সহজ করে। পদক্ষেপ: আজই একটি Jhury যোগ করে নিজের জায়গাটি আরও পরিপাটি করুন।',
            ],
            [
                'category' => 'home-living',
                'sku' => 'DEMO-IU-STOOL-15',
                'name' => 'Comfort Folding Stool 15 Inch',
                'slug' => 'comfort-folding-stool-15-inch',
                'price' => 990,
                'compare_at_price' => 1190,
                'image_url' => 'products/comfort-folding-tool-15-inch/01.png',
                'badge' => 'Sale',
                'short_description' => 'A space-saving 15-inch folding stool for handy everyday use.',
                'description' => 'মনোযোগ: উঁচু তাক বা দৈনন্দিন ছোট কাজের সময় একটি নির্ভরযোগ্য অতিরিক্ত step দরকার হয়। আগ্রহ: Comfort Folding Stool 15 Inch ব্যবহার শেষে ভাঁজ করে কম জায়গায় রাখা যায়। আকাঙ্ক্ষা: ঘর, রান্নাঘর বা কাজের জায়গায় প্রয়োজনের সময়ে সহজে কাছে পাওয়া যায়। পদক্ষেপ: দৈনন্দিন সুবিধার জন্য আজই এই folding stool যুক্ত করুন।',
            ],
            [
                'category' => 'health-beauty',
                'sku' => 'DEMO-IU-BP-MONITOR',
                'name' => 'Wrist Blood Pressure Monitor',
                'slug' => 'wrist-blood-pressure-monitor',
                'price' => 1190,
                'compare_at_price' => 2250,
                'image_url' => 'products/wrist-blood-pressure-monitor/01.png',
                'badge' => 'Health Pick',
                'short_description' => 'A compact wrist monitor for convenient at-home blood pressure checks.',
                'description' => 'মনোযোগ: নিয়মিত health reading নোট করা কঠিন মনে হতে পারে, বিশেষ করে ব্যস্ত সময়ে। আগ্রহ: এই compact Wrist Blood Pressure Monitor বাসায় সুবিধামতো reading দেখতে সহায়তা করে। আকাঙ্ক্ষা: সহজে বহনযোগ্য design-এর কারণে নিজের routine-এ monitoring অভ্যাস গড়ে তোলা আরও সুবিধাজনক হয়। পদক্ষেপ: আজই নিজের wellness kit-এ এটি যোগ করুন। গুরুত্বপূর্ণ: এটি চিকিৎসকের পরামর্শ বা পেশাদার diagnosis-এর বিকল্প নয়।',
            ],
            [
                'category' => 'electronics',
                'sku' => 'DEMO-IU-GPS-07',
                'name' => 'GPS 07 Mini GPS Tracker',
                'slug' => 'gps-07-mini-gps-tracker',
                'price' => 890,
                'compare_at_price' => 2200,
                'image_url' => 'products/gps-07-mini-gps-tracker/01.png',
                'badge' => 'Hot',
                'short_description' => 'A compact tracker for keeping tabs on your own vehicle or belongings.',
                'description' => 'মনোযোগ: নিজের গাড়ি বা মূল্যবান জিনিসের অবস্থান নিয়ে অনিশ্চয়তা দুশ্চিন্তার কারণ হতে পারে। আগ্রহ: GPS 07 Mini GPS Tracker ছোট আকারে নিজের অনুমোদিত সম্পদের অবস্থান নজরদারির একটি সুবিধাজনক সমাধান। আকাঙ্ক্ষা: compact form factor-এর কারণে প্রয়োজনমতো নিজের vehicle বা belongings-এর সঙ্গে ব্যবহার করা সহজ। পদক্ষেপ: নিজের সম্পদ ব্যবস্থাপনায় আজই এই tracker যুক্ত করুন। শুধুমাত্র আইনসম্মত ও সম্মতিপূর্ণ ব্যবহারের জন্য।',
            ],
        ];

        foreach ($products as $product) {
            $category = $categories[$product['category']];
            unset($product['category']);

            $savedProduct = Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                $product + [
                    'category_id' => $category->id,
                    'stock_quantity' => 50,
                    'rating' => 0,
                    'reviews_count' => 0,
                    'is_featured' => true,
                    'is_active' => true,
                ]
            );
            $savedProduct->categories()->syncWithoutDetaching([$category->id]);
        }
    }
}
