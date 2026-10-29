<?php

namespace App\Services\Tracking;

use App\Jobs\DeliverTrackingEvent;
use App\Models\Order;
use App\Models\TrackingDelivery;
use App\Models\TrackingEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TrackingEventRecorder
{
    private const CORE_EVENTS = [
        'PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase',
        'LinkClick', 'PhoneClick', 'EmailClick', 'Search',
    ];

    public function __construct(private readonly TrackingSettingsService $settings)
    {
    }

    /** @param array<string, mixed> $input */
    public function recordBrowser(array $input, Request $request): ?TrackingEvent
    {
        if (! Schema::hasTable('tracking_events') || ! $this->collectionEnabled()) {
            return null;
        }

        $name = is_string($input['event_name'] ?? null) ? $input['event_name'] : '';
        if (! in_array($name, self::CORE_EVENTS, true) || ! EventRegistry::typeFor($name)) {
            return null;
        }

        $consent = $this->consentState($input, $request);
        $configuration = $this->settings->all();
        if ($configuration['require_consent'] && $consent !== 'granted') {
            return null;
        }

        $properties = $this->sanitizeProperties($name, is_array($input['properties'] ?? null) ? $input['properties'] : []);
        $clientId = $consent === 'granted' ? $this->gaClientId($input['ga_client_id'] ?? null) : null;
        $sessionId = $consent === 'granted' ? $this->gaSessionId($input['ga_session_id'] ?? null) : null;
        if ($clientId !== null) {
            $request->session()->put('tracking.ga_client_id', $clientId);
        }
        if ($sessionId !== null) {
            $request->session()->put('tracking.ga_session_id', $sessionId);
        }
        if ($consent === 'granted') {
            $request->session()->put('tracking.consent_state', 'granted');
        }

        return $this->persist([
            'event_id' => $this->eventId($input['event_id'] ?? null),
            'event_name' => $name,
            'event_type' => EventRegistry::typeFor($name),
            'event_source' => 'browser',
            'consent_state' => $consent,
            'page_path' => $this->safePath($input['page_path'] ?? null),
            'referrer_host' => $this->safeHost($input['referrer'] ?? null),
            'campaign_source' => $this->campaign($input['campaign_source'] ?? null),
            'campaign_medium' => $this->campaign($input['campaign_medium'] ?? null),
            'campaign_name' => $this->campaign($input['campaign_name'] ?? null),
            'link_type' => $this->linkType($properties['link_type'] ?? null),
            'destination_host' => $this->destinationHost($properties['destination_host'] ?? null),
            'properties' => $properties,
            'ga_client_id' => $clientId,
            'ga_session_id' => $sessionId,
            'match_data' => null,
            'occurred_at' => now(),
        ]);
    }

    public function recordOrderPurchase(Order $order, Request $request): ?TrackingEvent
    {
        if (! Schema::hasTable('tracking_events') || ! $this->collectionEnabled()) {
            return null;
        }

        $configuration = $this->settings->all();
        $consent = $this->consentState([], $request);
        if ($configuration['require_consent'] && $consent !== 'granted') {
            return null;
        }

        $order->loadMissing('items');
        $tracking = app(EcommerceTrackingService::class)->order($order);
        $matchData = null;
        if ($consent === 'granted' && $configuration['allow_customer_matching']) {
            $email = strtolower(trim((string) $order->customer_email));
            $phone = preg_replace('/\D+/', '', (string) $order->customer_phone) ?: '';
            $matchData = array_filter([
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? hash('sha256', $email) : null,
                'phone' => strlen($phone) >= 8 ? hash('sha256', $phone) : null,
            ]);
        }

        return $this->persist([
            'event_id' => (string) ($order->tracking_purchase_event_id ?: Str::uuid()),
            'event_name' => 'Purchase',
            'event_type' => EventRegistry::typeFor('Purchase'),
            'event_source' => 'server',
            'consent_state' => $consent,
            'page_path' => '/checkout/success',
            'referrer_host' => null,
            'campaign_source' => null,
            'campaign_medium' => null,
            'campaign_name' => null,
            'link_type' => null,
            'destination_host' => null,
            'properties' => $this->sanitizeProperties('Purchase', $tracking),
            'ga_client_id' => $consent === 'granted' ? $request->session()->get('tracking.ga_client_id') : null,
            'ga_session_id' => $consent === 'granted' ? $request->session()->get('tracking.ga_session_id') : null,
            'match_data' => $matchData,
            'occurred_at' => $order->created_at ?: now(),
        ]);
    }

    private function collectionEnabled(): bool
    {
        return (bool) $this->settings->all()['collection_enabled'];
    }

    /** @param array<string, mixed> $input */
    private function consentState(array $input, Request $request): string
    {
        if ($request->cookie('livebajar_tracking_consent') === 'denied' || ($input['consent_state'] ?? null) === 'denied') {
            return 'denied';
        }
        if ($request->cookie('livebajar_tracking_consent') === 'granted' || ($input['consent_state'] ?? null) === 'granted' || $request->session()->get('tracking.consent_state') === 'granted') {
            return 'granted';
        }

        return 'unknown';
    }

    /** @param array<string, mixed> $attributes */
    private function persist(array $attributes): ?TrackingEvent
    {
        if ($attributes['event_id'] === '') {
            return null;
        }

        try {
            $event = TrackingEvent::query()->firstOrCreate(
                [
                    'event_name' => $attributes['event_name'],
                    'event_id' => $attributes['event_id'],
                    'event_source' => $attributes['event_source'],
                ],
                $attributes,
            );
        } catch (QueryException) {
            $event = TrackingEvent::query()
                ->where('event_name', $attributes['event_name'])
                ->where('event_id', $attributes['event_id'])
                ->where('event_source', $attributes['event_source'])
                ->first();
        }

        if ($event !== null && $event->event_source === 'server') {
            $this->queueServerDeliveries($event);
        }

        return $event;
    }

    private function queueServerDeliveries(TrackingEvent $event): void
    {
        $configuration = $this->settings->all();
        $driver = (string) config('queue.default', 'sync');
        foreach (['meta', 'tiktok', 'ga4'] as $destination) {
            $route = (bool) data_get($configuration, "routes.{$event->event_name}.{$destination}_server", false);
            if (! $route) {
                continue;
            }

            $status = $this->serverStatus($destination, $event, $configuration, $driver);
            $delivery = TrackingDelivery::query()->firstOrCreate(
                ['tracking_event_id' => $event->id, 'destination' => $destination, 'channel' => 'server'],
                ['status' => $status, 'queued_at' => now()],
            );

            if ($delivery->wasRecentlyCreated && $status === 'queued') {
                DeliverTrackingEvent::dispatch($delivery->id)->onQueue('tracking');
            }
        }
    }

    /** @param array<string, mixed> $configuration */
    private function serverStatus(string $destination, TrackingEvent $event, array $configuration, string $driver): string
    {
        if ($driver === 'sync' || $driver === 'null') {
            return 'blocked_queue';
        }
        if ($destination === 'tiktok' && $configuration['test_mode']) {
            return 'blocked_test_mode';
        }

        if (! data_get($configuration, "{$destination}.server_enabled", false)) {
            return 'blocked_configuration';
        }

        $hasCredentials = match ($destination) {
            'meta' => filled(data_get($configuration, 'meta.pixel_id')) && filled(data_get($configuration, 'meta.access_token')) && filled(data_get($configuration, 'meta.graph_version')) && (! $configuration['test_mode'] || filled(data_get($configuration, 'meta.test_event_code'))),
            'tiktok' => ! $configuration['test_mode'] && filled(data_get($configuration, 'tiktok.pixel_id')) && filled(data_get($configuration, 'tiktok.access_token')),
            'ga4' => filled(data_get($configuration, 'ga4.measurement_id')) && filled(data_get($configuration, 'ga4.api_secret')),
            default => false,
        };
        if (! $hasCredentials) {
            return 'blocked_configuration';
        }

        if ($destination === 'ga4' && ! $event->ga_client_id) {
            return 'blocked_identifier';
        }
        if ($destination === 'tiktok' && empty($event->match_data)) {
            return 'blocked_identifier';
        }
        if ($destination === 'meta' && empty($event->match_data)) {
            return 'blocked_identifier';
        }

        return 'queued';
    }

    /** @param array<string, mixed> $properties
     *  @return array<string, mixed>
     */
    private function sanitizeProperties(string $eventName, array $properties): array
    {
        $allowed = [
            'product_id', 'sku', 'name', 'category', 'brand', 'variant', 'coupon', 'order_id',
            'checkout_id', 'affiliation', 'creative', 'payment_type', 'value', 'revenue', 'total',
            'shipping', 'tax', 'discount', 'price', 'currency', 'quantity', 'step', 'position',
            'products', 'link_type', 'destination_host', 'search_length', 'result_count',
        ];
        $clean = [];
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $properties)) {
                continue;
            }
            $value = $properties[$key];
            if ($key === 'products' && is_array($value)) {
                $clean['products'] = array_values(array_filter(array_map(fn ($item): ?array => is_array($item) ? $this->sanitizeProduct($item) : null, array_slice($value, 0, 50))));
            } elseif (in_array($key, ['value', 'revenue', 'total', 'shipping', 'tax', 'discount', 'price', 'quantity', 'step', 'position', 'search_length', 'result_count'], true) && is_numeric($value)) {
                $number = (float) $value;
                if (is_finite($number) && $number >= 0) {
                    $clean[$key] = in_array($key, ['quantity', 'step', 'position', 'search_length', 'result_count'], true) ? (int) $number : round($number, 2);
                }
            } elseif ($key === 'currency' && is_string($value) && preg_match('/^[A-Z]{3}$/i', $value)) {
                $clean[$key] = strtoupper($value);
            } elseif (is_string($value) && mb_strlen($value) <= 160 && ! $this->looksLikePersonalData($value)) {
                $clean[$key] = trim($value);
            }
        }

        if ($eventName === 'Search') {
            unset($clean['name'], $clean['product_id'], $clean['sku'], $clean['coupon'], $clean['checkout_id']);
        }

        return $clean;
    }

    /** @param array<string, mixed> $product
     *  @return array<string, mixed>|null
     */
    private function sanitizeProduct(array $product): ?array
    {
        $keys = ['product_id', 'sku', 'name', 'category', 'brand', 'price', 'value', 'quantity', 'currency'];
        $clean = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $product)) {
                continue;
            }
            if (in_array($key, ['price', 'value', 'quantity'], true) && is_numeric($product[$key])) {
                $clean[$key] = max(0, (float) $product[$key]);
            } elseif ($key === 'currency' && is_string($product[$key]) && preg_match('/^[A-Z]{3}$/i', $product[$key])) {
                $clean[$key] = strtoupper($product[$key]);
            } elseif (is_string($product[$key]) && mb_strlen($product[$key]) <= 120 && ! $this->looksLikePersonalData($product[$key])) {
                $clean[$key] = $product[$key];
            }
        }

        return $clean === [] ? null : $clean;
    }

    private function looksLikePersonalData(string $value): bool
    {
        return preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value) === 1
            || preg_match('/(?:\+?\d[\d .()\-]{7,}\d)/', $value) === 1;
    }

    private function eventId(mixed $id): string
    {
        if (! is_string($id) || $id === '' || mb_strlen($id) > 120 || preg_match('/[^A-Za-z0-9_.:-]/', $id)) {
            return '';
        }

        return $id;
    }

    private function safePath(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $path = parse_url($value, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/') || strlen($path) > 512 || str_contains($path, '@') || preg_match('/\d{10,}/', $path)) {
            return null;
        }

        return $path;
    }

    private function safeHost(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $host = parse_url($value, PHP_URL_HOST);
        if (! is_string($host) || strlen($host) > 255 || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        return strtolower($host);
    }

    private function campaign(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 120 || $this->looksLikePersonalData($value)) {
            return null;
        }

        return preg_match('/^[\pL\pN ._:-]+$/u', trim($value)) === 1 ? trim($value) : null;
    }

    private function linkType(mixed $value): ?string
    {
        return is_string($value) && in_array($value, ['link', 'phone', 'email'], true) ? $value : null;
    }

    private function destinationHost(mixed $value): ?string
    {
        if (! is_string($value) || strlen($value) > 255 || filter_var($value, FILTER_VALIDATE_IP)) {
            return null;
        }
        $host = strtolower(trim($value));

        return $host !== '' && preg_match('/^[a-z0-9.-]+$/', $host) ? $host : null;
    }

    private function gaClientId(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{1,20}\.\d{1,20}$/', $value) ? $value : null;
    }

    private function gaSessionId(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{6,15}$/', $value) ? $value : null;
    }
}
