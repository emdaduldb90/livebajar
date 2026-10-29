<?php

namespace App\Models;

use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description', 'key_features',
        'specifications', 'faqs', 'customer_reviews',
        'meta_title', 'meta_description', 'meta_keywords',
        'price', 'compare_at_price', 'purchase_price', 'stock_quantity', 'image_url', 'image_asset_id', 'badge',
        'rating', 'reviews_count', 'is_featured', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'key_features' => 'array',
        'specifications' => 'array',
        'faqs' => 'array',
        'customer_reviews' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(static fn () => StorefrontCache::forgetHomeData());
        static::deleted(static fn () => StorefrontCache::forgetHomeData());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function mainImageAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getPriceCentsAttribute(): int
    {
        return (int) round(((float) $this->price) * 100);
    }

    public function getCompareAtPriceCentsAttribute(): ?int
    {
        return $this->compare_at_price === null
            ? null
            : (int) round(((float) $this->compare_at_price) * 100);
    }

    public function getPurchasePriceCentsAttribute(): ?int
    {
        return $this->purchase_price === null
            ? null
            : (int) round(((float) $this->purchase_price) * 100);
    }

    public function getImageUrlAttribute(?string $value): ?string
    {
        if ($value === null || $value === '' || Str::startsWith($value, ['http://', 'https://', '//', 'data:'])) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }

    public function keyFeaturesText(): string
    {
        return implode(PHP_EOL, array_filter($this->key_features ?? [], 'is_string'));
    }

    public function specificationsText(): string
    {
        $lines = [];

        foreach ($this->specifications ?? [] as $group => $specifications) {
            if (! is_array($specifications)) {
                continue;
            }

            foreach ($specifications as $specification) {
                if (! is_array($specification) || empty($specification['label']) || ! array_key_exists('value', $specification)) {
                    continue;
                }

                $lines[] = $group.' | '.$specification['label'].' | '.$specification['value'];
            }
        }

        return implode(PHP_EOL, $lines);
    }

    public function faqsText(): string
    {
        return collect($this->faqs ?? [])
            ->filter(fn ($faq): bool => is_array($faq) && ! empty($faq['question']) && ! empty($faq['answer']))
            ->map(fn (array $faq): string => $faq['question'].' | '.$faq['answer'])
            ->implode(PHP_EOL);
    }

    public function customerReviewsText(): string
    {
        return collect($this->customer_reviews ?? [])
            ->filter(fn ($review): bool => is_array($review) && ! empty($review['name']) && ! empty($review['body']))
            ->map(fn (array $review): string => ($review['rating'] ?? 5).' | '.$review['name'].' | '.$review['body'])
            ->implode(PHP_EOL);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
