<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class StorefrontCache
{
    public const ACTIVE_CATEGORIES_KEY = 'store.active-categories.v2';
    public const FEATURED_PRODUCTS_KEY = 'store.featured-products.v5';

    public static function forgetHomeData(): void
    {
        Cache::forget(self::ACTIVE_CATEGORIES_KEY);
        Cache::forget(self::FEATURED_PRODUCTS_KEY);
    }

    public static function forgetFeaturedProducts(): void
    {
        Cache::forget(self::FEATURED_PRODUCTS_KEY);
    }
}
