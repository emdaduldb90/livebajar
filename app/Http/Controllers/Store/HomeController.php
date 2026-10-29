<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Tracking\EcommerceTrackingService;
use App\Support\StorefrontCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(EcommerceTrackingService $tracking): View
    {
        $homepageProductOrder = [
            'balaclava-windproof-full-face-mask-hudi',
            'koetsu-winter-warm-3-in-1-hooded-hat-with-face-mask',
            'lubricant-lube-love-strawberry-gel',
            'smart-wifi-camera',
        ];

        $hasHomepageVisibilityColumn = Schema::hasColumn('categories', 'show_on_homepage');
        $loadHomepageCategories = function () use ($hasHomepageVisibilityColumn) {
            $query = Category::query()->active();
            if ($hasHomepageVisibilityColumn) {
                $query->where('show_on_homepage', true);
            }

            return $query->orderBy('name')->get(['id', 'name', 'slug', 'description']);
        };
        $categories = $hasHomepageVisibilityColumn
            ? Cache::remember(StorefrontCache::ACTIVE_CATEGORIES_KEY, now()->addMinutes(10), $loadHomepageCategories)
            : $loadHomepageCategories();

        $featuredProducts = Cache::remember(StorefrontCache::FEATURED_PRODUCTS_KEY, now()->addMinutes(5), fn () =>
            Product::query()->active()->with(['category:id,name,slug', 'categories:id,name,slug', 'brand:id,name', 'mainImageAsset:id,path,alt_text'])
                ->orderByRaw('CASE slug WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END', $homepageProductOrder)
                ->orderByDesc('is_featured')
                ->orderByDesc('id')
                ->limit(8)
                ->get()
        );

        $trackingContext = [
            'events' => [[
                'type' => 'ecommerce',
                'name' => 'Product List Viewed',
                'properties' => ['products' => $tracking->productList($featuredProducts)],
                'event_id' => 'home_featured_list',
                'visibility_target' => 'featured-product-list',
            ]],
        ];

        $settings = SiteSetting::values();
        $seo = [
            'title' => data_get($settings, 'home_seo.title') ?: 'Live Bajar - Ultimate Paper-Crafted Shopping Experience',
            'description' => data_get($settings, 'home_seo.description') ?: 'Shop curated products at Live Bajar.',
            'keywords' => data_get($settings, 'home_seo.keywords'),
            'canonical' => route('home'),
        ];

        return view('store.home', compact('categories', 'featuredProducts', 'trackingContext', 'settings', 'seo'));
    }
}
