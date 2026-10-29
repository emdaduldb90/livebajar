<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    /** @return array<string, mixed> */
    public static function values(): array
    {
        return Cache::remember('storefront.site-settings', now()->addMinutes(15), fn (): array => self::query()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (self $setting): array => [$setting->key => $setting->value])
            ->all());
    }

    public static function put(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('storefront.site-settings');
    }
}
