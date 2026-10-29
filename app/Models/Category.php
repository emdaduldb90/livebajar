<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Support\StorefrontCache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'is_active', 'show_on_homepage'];

    protected $casts = ['is_active' => 'boolean', 'show_on_homepage' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(static fn () => StorefrontCache::forgetHomeData());
        static::deleted(static fn () => StorefrontCache::forgetHomeData());
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
