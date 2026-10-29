<?php

namespace App\Models;

use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'original_name',
        'alt_text',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = ['size_bytes' => 'integer'];

    protected static function booted(): void
    {
        static::saved(static fn () => StorefrontCache::forgetFeaturedProducts());
        static::deleted(static fn () => StorefrontCache::forgetFeaturedProducts());
    }

    public function mainProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'image_asset_id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.ltrim($this->path, '/'));
    }
}
