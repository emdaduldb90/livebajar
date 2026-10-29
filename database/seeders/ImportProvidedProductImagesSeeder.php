<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ImportProvidedProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'DEMO-IU-CAMERA-01' => [
                'name' => 'Smart WiFi Camera',
                'slug' => 'smart-wifi-camera',
                'images' => ['products/smart-wifi-camera/01.png'],
            ],
            'DEMO-IU-JHURY-10' => [
                'name' => 'Multipurpose Jhury 10 Inch',
                'slug' => 'multipurpose-jhury-10-inch',
                'images' => [
                    'products/multipurpose-jhury-10-inch/01.png',
                    'products/multipurpose-jhury-10-inch/02.png',
                    'products/multipurpose-jhury-10-inch/03.png',
                    'products/multipurpose-jhury-10-inch/04.png',
                    'products/multipurpose-jhury-10-inch/05.png',
                ],
            ],
            'DEMO-IU-JHURY-02' => [
                'name' => 'Multipurpose Jhury 8 Inch',
                'slug' => 'multipurpose-jhury-8-inch',
                'images' => [
                    'products/multipurpose-jhury-8-inch/01.png',
                    'products/multipurpose-jhury-8-inch/02.png',
                    'products/multipurpose-jhury-8-inch/03.png',
                    'products/multipurpose-jhury-8-inch/04.png',
                    'products/multipurpose-jhury-8-inch/05.png',
                ],
            ],
            'DEMO-IU-STOOL-15' => [
                'name' => 'Comfort Folding Tool 15 Inch',
                'slug' => 'comfort-folding-tool-15-inch',
                'images' => [
                    'products/comfort-folding-tool-15-inch/01.png',
                    'products/comfort-folding-tool-15-inch/02.png',
                    'products/comfort-folding-tool-15-inch/03.png',
                    'products/comfort-folding-tool-15-inch/04.png',
                ],
            ],
            'DEMO-IU-BP-MONITOR' => [
                'name' => 'Wrist Blood Pressure Monitor',
                'slug' => 'wrist-blood-pressure-monitor',
                'images' => [
                    'products/wrist-blood-pressure-monitor/01.png',
                    'products/wrist-blood-pressure-monitor/02.png',
                    'products/wrist-blood-pressure-monitor/03.png',
                ],
            ],
            'DEMO-IU-GPS-07' => [
                'name' => 'GPS 07 Mini GPS Tracker',
                'slug' => 'gps-07-mini-gps-tracker',
                'images' => [
                    'products/gps-07-mini-gps-tracker/01.png',
                    'products/gps-07-mini-gps-tracker/02.png',
                    'products/gps-07-mini-gps-tracker/03.png',
                ],
            ],
        ];

        foreach ($products as $sku => $data) {
            $product = Product::query()
                ->where('sku', $sku)
                ->orWhere('slug', $data['slug'])
                ->firstOrFail();
            $product->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'image_url' => $data['images'][0],
            ]);

            $product->images()->delete();
            foreach ($data['images'] as $sortOrder => $path) {
                $product->images()->create([
                    'path' => $path,
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }
}
