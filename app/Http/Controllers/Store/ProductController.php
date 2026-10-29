<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Tracking\EcommerceTrackingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, EcommerceTrackingService $tracking): View|RedirectResponse
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:100'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'sort' => ['nullable', 'in:featured,price_asc,price_desc,rating'],
        ]);

        $categories = Category::query()->active()->orderBy('name')->get(['id', 'name', 'slug', 'description', 'meta_title', 'meta_description', 'meta_keywords']);

        if ($request->routeIs('products.index') && ! empty($filters['category'])) {
            $legacyCategory = $categories->firstWhere('slug', $filters['category']);
            if ($legacyCategory) {
                $queryParameters = $request->query();
                unset($queryParameters['category']);

                return redirect()->route('categories.show', ['category' => $legacyCategory->slug] + $queryParameters, 301);
            }
        }

        $query = Product::query()->active()->with(['category:id,name,slug', 'categories:id,name,slug', 'brand:id,name', 'mainImageAsset:id,path,alt_text']);
        $priceRangeMax = max(100, (int) (ceil(((float) Product::query()->active()->max('price')) / 100) * 100));

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn (Builder $categoryQuery) =>
                $categoryQuery->where('categories.slug', $filters['category'])
            );
        }

        if (! empty($filters['q'])) {
            $query->whereFullText(['name', 'short_description'], $filters['q']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        match ($filters['sort'] ?? 'featured') {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('reviews_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('id'),
        };

        $products = $query->paginate(12)->withQueryString();

        $events = [[
            'type' => 'ecommerce',
            'name' => 'Product List Viewed',
            'properties' => ['products' => $tracking->productList($products->getCollection())],
            'event_id' => (string) Str::uuid(),
            'visibility_target' => 'catalog-product-list',
        ]];

        if (! empty($filters['q'])) {
            $events[] = [
                'type' => 'ecommerce',
                'name' => 'Products Searched',
                'properties' => ['search_length' => mb_strlen($filters['q']), 'result_count' => $products->total()],
                'event_id' => (string) Str::uuid(),
            ];
        }

        if (! empty($filters['category'])) {
            $events[] = [
                'type' => 'custom',
                'name' => 'CategoryViewed',
                'properties' => ['category' => $filters['category']],
                'event_id' => (string) Str::uuid(),
            ];
        }

        $appliedFilters = array_filter([
            'category' => $filters['category'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'query_present' => empty($filters['q']) ? null : true,
        ], static fn ($value): bool => $value !== null && $value !== '');

        if ($appliedFilters !== []) {
            $events[] = [
                'type' => 'custom',
                'name' => 'ProductFiltered',
                'properties' => ['filter_count' => count($appliedFilters)],
                'event_id' => (string) Str::uuid(),
            ];
        }

        if (isset($filters['sort']) && $filters['sort'] !== 'featured') {
            $events[] = [
                'type' => 'custom',
                'name' => 'ProductSorted',
                'properties' => ['sort' => $filters['sort']],
                'event_id' => (string) Str::uuid(),
            ];
        }

        $trackingContext = ['events' => $events];

        $selectedCategory = ! empty($filters['category']) ? $categories->firstWhere('slug', $filters['category']) : null;
        $seo = [
            'title' => $selectedCategory?->meta_title ?: ($selectedCategory ? $selectedCategory->name.' | Live Bajar' : 'Shop | Live Bajar'),
            'description' => $selectedCategory?->meta_description ?: ($selectedCategory?->description ?: 'Browse the Live Bajar product catalog.'),
            'keywords' => $selectedCategory?->meta_keywords,
        ];

        return view('store.products.index', compact('categories', 'products', 'filters', 'trackingContext', 'priceRangeMax', 'seo', 'selectedCategory'));
    }

    public function category(Request $request, Category $category, EcommerceTrackingService $tracking): View|RedirectResponse
    {
        abort_unless($category->is_active, 404);
        $request->query->set('category', $category->slug);

        return $this->index($request, $tracking);
    }

    public function show(Product $product, EcommerceTrackingService $tracking): View
    {
        abort_unless($product->is_active, 404);
        $product->load(['category:id,name,slug', 'categories:id,name,slug', 'brand:id,name,slug', 'mainImageAsset', 'images.mediaAsset']);

        $categoryIds = $product->categories->pluck('id');
        if ($categoryIds->isEmpty()) {
            $categoryIds = collect([$product->category_id]);
        }

        $relatedProducts = Product::query()->active()
            ->with(['category:id,name,slug', 'categories:id,name,slug', 'brand:id,name'])
            ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds))
            ->whereKeyNot($product->id)
            ->limit(3)
            ->get();

        $trackingContext = [
            'events' => [[
                'type' => 'ecommerce',
                'name' => 'Product Viewed',
                'properties' => $tracking->product($product),
                'event_id' => (string) Str::uuid(),
            ]],
        ];

        $seo = [
            'title' => $product->meta_title ?: $product->name.' | Live Bajar',
            'description' => $product->meta_description ?: $product->short_description,
            'keywords' => $product->meta_keywords,
            'canonical' => route('products.show', $product),
        ];

        return view('store.products.show', compact('product', 'relatedProducts', 'trackingContext', 'seo'));
    }
}
