<?php

namespace App\Services\Tracking;

use App\Models\TrackingConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TrackingSettingsService
{
    public const PUBLIC_CACHE_KEY = 'tracking.intelligence.public-config';

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $routes = [];
        foreach (['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase', 'LinkClick', 'PhoneClick', 'EmailClick', 'Search'] as $event) {
            $routes[$event] = [
                'meta_browser' => false, 'meta_server' => false,
                'tiktok_browser' => false, 'tiktok_server' => false,
                'ga4_browser' => false, 'ga4_server' => false,
                'google_ads_browser' => false,
            ];
        }

        return [
            'collection_enabled' => false,
            'require_consent' => true,
            'test_mode' => true,
            'retention_days' => 90,
            'allow_customer_matching' => false,
            'routes' => $routes,
            'meta' => ['pixel_id' => '', 'access_token' => '', 'test_event_code' => '', 'graph_version' => '', 'browser_enabled' => false, 'server_enabled' => false],
            'tiktok' => ['pixel_id' => '', 'access_token' => '', 'browser_enabled' => false, 'server_enabled' => false],
            'ga4' => ['measurement_id' => '', 'api_secret' => '', 'browser_enabled' => false, 'server_enabled' => false],
            'google_ads' => ['conversion_id' => '', 'conversion_label' => '', 'browser_enabled' => false, 'server_enabled' => false],
            'openai' => ['api_key' => '', 'model' => 'gpt-4.1-mini', 'enabled' => false, 'reports_per_day' => 1, 'max_output_tokens' => 1200],
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if (! Schema::hasTable('tracking_configurations')) {
            return $this->defaults();
        }

        $saved = TrackingConfiguration::query()->orderBy('id')->first()?->payload;
        if (! is_array($saved)) {
            return $this->defaults();
        }

        return array_replace_recursive($this->defaults(), $saved);
    }

    /** @return array<string, mixed> */
    public function publicConfig(): array
    {
        return Cache::rememberForever(self::PUBLIC_CACHE_KEY, function (): array {
            $all = $this->all();
            $routes = [];
            foreach ($all['routes'] as $event => $settings) {
                $routes[$event] = array_filter($settings, static fn ($enabled, $key): bool => str_ends_with((string) $key, '_browser') && (bool) $enabled, ARRAY_FILTER_USE_BOTH);
            }

            return [
                'collection_enabled' => (bool) $all['collection_enabled'],
                'require_consent' => (bool) $all['require_consent'],
                'test_mode' => (bool) $all['test_mode'],
                'routes' => $routes,
                'meta' => ['pixel_id' => $all['meta']['pixel_id'], 'browser_enabled' => (bool) $all['meta']['browser_enabled']],
                'tiktok' => ['pixel_id' => $all['tiktok']['pixel_id'], 'browser_enabled' => (bool) $all['tiktok']['browser_enabled']],
                'ga4' => ['measurement_id' => $all['ga4']['measurement_id'], 'browser_enabled' => (bool) $all['ga4']['browser_enabled']],
                'google_ads' => ['conversion_id' => $all['google_ads']['conversion_id'], 'conversion_label' => $all['google_ads']['conversion_label'], 'browser_enabled' => (bool) $all['google_ads']['browser_enabled']],
            ];
        });
    }

    /** @param array<string, mixed> $settings */
    public function save(array $settings): void
    {
        if (! Schema::hasTable('tracking_configurations')) {
            throw new \RuntimeException('Run the tracking-intelligence migration before saving configuration.');
        }

        $current = $this->all();
        $settings = array_replace_recursive($current, $settings);
        TrackingConfiguration::query()->firstOrNew()->forceFill(['payload' => $settings])->save();
        Cache::forget(self::PUBLIC_CACHE_KEY);
    }
}
