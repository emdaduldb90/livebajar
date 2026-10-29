<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Services\ImageWebpOptimizer;
use App\Support\StorefrontCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $products = Product::query()->with(['category:id,name', 'categories:id,name', 'brand:id,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product(['is_active' => true, 'stock_quantity' => 0, 'rating' => 0, 'reviews_count' => 0]),
            'categories' => $this->categories(),
            'brands' => $this->brands(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $categoryIds = $data['category_ids'];
        unset($data['category_ids']);

        DB::transaction(function () use ($request, $data, $categoryIds): void {
            $product = Product::query()->create($this->withMainImage($request, $data));
            $product->categories()->sync($categoryIds);
            $this->storeGalleryImages($request, $product);
        });
        StorefrontCache::forgetHomeData();

        return to_route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load(['categories', 'images.mediaAsset', 'mainImageAsset']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categories(),
            'brands' => $this->brands(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $categoryIds = $data['category_ids'];
        unset($data['category_ids']);

        DB::transaction(function () use ($request, $product, $data, $categoryIds): void {
            $this->removeGalleryImages($request, $product);
            $product->update($this->withMainImage($request, $data));
            $product->categories()->sync($categoryIds);
            $this->storeGalleryImages($request, $product);
        });
        StorefrontCache::forgetHomeData();

        return to_route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('admin.products.index')->with('success', 'Product deleted. Its unused image files remain available in File Manager.');
    }

    private function categories()
    {
        return Category::query()->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    private function brands()
    {
        return Brand::query()->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        // Browsers omit unchecked checkbox groups; normalize it so failed validation
        // preserves the user's choice to remove every additional category.
        $request->merge(['additional_category_ids' => $request->input('additional_category_ids', [])]);

        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'additional_category_ids' => ['nullable', 'array', 'max:50'],
            'additional_category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('products', 'sku')->ignore($product)],
            'short_description' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'key_features_text' => ['nullable', 'string', 'max:10000'],
            'specifications_text' => ['nullable', 'string', 'max:30000'],
            'faqs_text' => ['nullable', 'string', 'max:20000'],
            'customer_reviews_text' => ['nullable', 'string', 'max:30000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price', 'max:9999999999.99'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'image_url' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                $isUrl = filter_var($value, FILTER_VALIDATE_URL)
                    && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
                $isLocalProductImage = ! str_contains($value, '..')
                    && preg_match('#^products/[a-z0-9][a-z0-9/_\-.]*\.(png|jpe?g|webp)$#i', $value) === 1;

                if (! $isUrl && ! $isLocalProductImage) {
                    $fail('The product image must be a valid URL or a local product image path.');
                }
            }],
            'image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'gallery_uploads' => ['nullable', 'array', 'max:12'],
            'gallery_uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_gallery_images' => ['nullable', 'array', 'max:100'],
            'remove_gallery_images.*' => ['integer'],
            'badge' => ['nullable', 'string', 'max:40'],
            'rating' => ['required', 'numeric', 'between:0,5'],
            'reviews_count' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);

        if (($data['image_url'] ?? '') === '' && ! $request->hasFile('image_upload')) {
            throw ValidationException::withMessages(['image_upload' => 'Upload a main image or provide an image URL.']);
        }

        $data['category_ids'] = collect(array_merge(
            [(int) $data['category_id']],
            array_map('intval', $data['additional_category_ids'] ?? []),
        ))->unique()->values()->all();

        unset($data['additional_category_ids']);
        unset($data['image_upload'], $data['gallery_uploads'], $data['remove_gallery_images']);
        $data['key_features'] = $this->keyFeatures($data['key_features_text'] ?? '');
        $data['specifications'] = $this->specifications($data['specifications_text'] ?? '');
        $data['faqs'] = $this->faqs($data['faqs_text'] ?? '');
        $data['customer_reviews'] = $this->customerReviews($data['customer_reviews_text'] ?? '');
        unset($data['key_features_text'], $data['specifications_text'], $data['faqs_text'], $data['customer_reviews_text']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function withMainImage(Request $request, array $data): array
    {
        if ($request->hasFile('image_upload')) {
            $asset = $this->storeImage($request->file('image_upload'), $data['name']);
            $data['image_url'] = $asset->path;
            $data['image_asset_id'] = $asset->id;

            return $data;
        }

        $data['image_url'] = $this->normaliseImageReference($data['image_url']);
        $data['image_asset_id'] = MediaAsset::query()->where('path', $data['image_url'])->value('id');

        return $data;
    }

    private function storeGalleryImages(Request $request, Product $product): void
    {
        $sortOrder = ((int) $product->images()->max('sort_order')) + 1;

        foreach ($request->file('gallery_uploads', []) as $index => $file) {
            $asset = $this->storeImage($file, $product->name.' image '.($sortOrder + $index));
            $product->images()->create([
                'media_asset_id' => $asset->id,
                'path' => $asset->path,
                'sort_order' => $sortOrder + $index,
            ]);
        }
    }

    private function removeGalleryImages(Request $request, Product $product): void
    {
        $ids = $request->input('remove_gallery_images', []);
        if ($ids !== []) {
            $product->images()->whereIn('id', $ids)->delete();
        }
    }

    private function storeImage(UploadedFile $file, string $altText): MediaAsset
    {
        try {
            $optimized = app(ImageWebpOptimizer::class)->storeUpload($file, 'products/'.now()->format('Y/m'));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['image_upload' => $exception->getMessage()]);
        }

        return MediaAsset::query()->create([
            'path' => $optimized['path'],
            'original_name' => $optimized['original_name'],
            'alt_text' => Str::limit($altText, 255, ''),
            'mime_type' => $optimized['mime_type'],
            'size_bytes' => $optimized['size_bytes'],
        ]);
    }

    private function normaliseImageReference(string $reference): string
    {
        $path = parse_url($reference, PHP_URL_PATH);

        if (is_string($path) && str_starts_with($path, '/storage/products/')) {
            return ltrim(Str::after($path, '/storage/'), '/');
        }

        return $reference;
    }

    /** @return array<int, string> */
    private function keyFeatures(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(fn (string $line): string => Str::limit($line, 500, ''))
            ->take(50)
            ->values()
            ->all();
    }

    /** @return array<string, array<int, array{label: string, value: string}>> */
    private function specifications(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])
            ->map(fn (string $line): array => array_map('trim', explode('|', $line, 3)))
            ->filter(fn (array $parts): bool => count($parts) === 3 && $parts[1] !== '' && $parts[2] !== '')
            ->take(100)
            ->reduce(function (array $groups, array $parts): array {
                [$group, $label, $specificationValue] = $parts;
                $group = Str::limit($group !== '' ? $group : 'Specifications', 120, '');
                $groups[$group] ??= [];
                $groups[$group][] = [
                    'label' => Str::limit($label, 160, ''),
                    'value' => Str::limit($specificationValue, 1000, ''),
                ];

                return $groups;
            }, []);
    }

    /** @return array<int, array{question: string, answer: string}> */
    private function faqs(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])
            ->map(fn (string $line): array => array_map('trim', explode('|', $line, 2)))
            ->filter(fn (array $parts): bool => count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '')
            ->take(50)
            ->map(fn (array $parts): array => [
                'question' => Str::limit($parts[0], 500, ''),
                'answer' => Str::limit($parts[1], 3000, ''),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{rating: int, name: string, body: string}> */
    private function customerReviews(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])
            ->map(fn (string $line): array => array_map('trim', explode('|', $line, 3)))
            ->filter(fn (array $parts): bool => count($parts) === 3 && $parts[1] !== '' && $parts[2] !== '')
            ->take(100)
            ->map(fn (array $parts): array => [
                'rating' => min(5, max(1, (int) $parts[0])),
                'name' => Str::limit($parts[1], 120, ''),
                'body' => Str::limit($parts[2], 3000, ''),
            ])
            ->values()
            ->all();
    }
}
