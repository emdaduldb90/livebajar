<?php

namespace App\Services\Tracking;

use App\Models\TrackingDelivery;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TrackingDeliveryService
{
    public function __construct(private readonly TrackingSettingsService $settings)
    {
    }

    /** @return array{accepted: bool, http_status: int|null, summary: string, retryable: bool, failure_status: string|null} */
    public function deliver(TrackingDelivery $delivery): array
    {
        $event = $delivery->event;
        if ($event === null || $delivery->channel !== 'server' || $event->event_source !== 'server') {
            return $this->blocked('blocked_configuration', 'Only verified server events are deliverable.');
        }

        $settings = $this->settings->all();
        if (! $settings['collection_enabled'] || $settings['require_consent'] && $event->consent_state !== 'granted') {
            return $this->blocked('blocked_consent', 'Collection or consent gate is closed.');
        }
        if (! data_get($settings, "routes.{$event->event_name}.{$delivery->destination}_server", false)) {
            return $this->blocked('blocked_configuration', 'The event route is disabled.');
        }

        return match ($delivery->destination) {
            'meta' => $this->meta($event, $settings),
            'tiktok' => $this->tiktok($event, $settings),
            'ga4' => $this->ga4($event, $settings),
            default => $this->blocked('blocked_configuration', 'Destination is not supported for server delivery.'),
        };
    }

    private function meta(\App\Models\TrackingEvent $event, array $settings): array
    {
        $config = $settings['meta'];
        if (! $config['server_enabled']) {
            return $this->blocked('blocked_configuration', 'Meta server delivery is disabled.');
        }
        if (empty($event->match_data)) {
            return $this->blocked('blocked_identifier', 'Meta server delivery needs consented match data.');
        }
        if (! filled($config['pixel_id']) || ! filled($config['access_token']) || ! filled($config['graph_version']) || $settings['test_mode'] && ! filled($config['test_event_code'])) {
            return $this->blocked('blocked_configuration', 'Meta server settings are incomplete.');
        }

        $data = [
            'event_name' => $event->event_name,
            'event_time' => $event->occurred_at->timestamp,
            'event_id' => $event->event_id,
            'action_source' => 'website',
            'event_source_url' => url($event->page_path ?: '/'),
            'user_data' => array_filter([
                'em' => $event->match_data['email'] ?? null,
                'ph' => $event->match_data['phone'] ?? null,
            ]),
            'custom_data' => $this->metaCustomData($event->properties ?? []),
        ];
        $payload = ['data' => [$data]];
        if ($settings['test_mode'] && filled($config['test_event_code'])) {
            $payload['test_event_code'] = $config['test_event_code'];
        }

        $endpoint = 'https://graph.facebook.com/'.$config['graph_version'].'/'.rawurlencode($config['pixel_id']).'/events?access_token='.rawurlencode($config['access_token']);
        $response = Http::connectTimeout(2)->timeout(6)->acceptJson()->post($endpoint, $payload);
        $body = $response->json();
        $accepted = $response->successful() && (int) data_get($body, 'events_received', 0) > 0;

        return $this->responseResult($response, $accepted, 'Meta accepted the event request; attribution is not confirmed.', 'Meta did not accept the event request.');
    }

    private function tiktok(\App\Models\TrackingEvent $event, array $settings): array
    {
        $config = $settings['tiktok'];
        if ($settings['test_mode']) {
            return $this->blocked('blocked_test_mode', 'TikTok Events API 2.0 is blocked in Test Mode because a current test payload field is not verified.');
        }
        if (! $config['server_enabled'] || ! filled($config['pixel_id']) || ! filled($config['access_token'])) {
            return $this->blocked('blocked_configuration', 'TikTok server settings are incomplete.');
        }
        if (empty($event->match_data)) {
            return $this->blocked('blocked_identifier', 'TikTok server delivery needs consented match data.');
        }

        $eventData = [
            'event' => $event->event_name === 'Purchase' ? 'CompletePayment' : $event->event_name,
            'event_time' => $event->occurred_at->timestamp,
            'event_id' => $event->event_id,
            'user' => $event->match_data,
            'page' => ['url' => url($event->page_path ?: '/')],
            'properties' => $this->tiktokProperties($event->properties ?? []),
        ];
        $payload = ['event_source' => 'web', 'event_source_id' => $config['pixel_id'], 'data' => [$eventData]];
        $response = Http::connectTimeout(2)->timeout(6)->acceptJson()
            ->withHeaders(['Access-Token' => $config['access_token']])
            ->post('https://business-api.tiktok.com/open_api/v1.3/event/track/', $payload);
        $accepted = $response->successful() && (int) $response->json('code', -1) === 0;

        return $this->responseResult($response, $accepted, 'TikTok accepted the event request; attribution is not confirmed.', 'TikTok did not accept the event request.');
    }

    private function ga4(\App\Models\TrackingEvent $event, array $settings): array
    {
        $config = $settings['ga4'];
        if (! $config['server_enabled'] || ! filled($config['measurement_id']) || ! filled($config['api_secret'])) {
            return $this->blocked('blocked_configuration', 'GA4 Measurement Protocol server settings are incomplete.');
        }
        if (! $event->ga_client_id) {
            return $this->blocked('blocked_identifier', 'GA4 Measurement Protocol needs a consented client ID.');
        }

        $name = match ($event->event_name) {
            'Purchase' => 'purchase',
            'ViewContent' => 'view_item',
            'AddToCart' => 'add_to_cart',
            'InitiateCheckout' => 'begin_checkout',
            'PageView' => 'page_view',
            'Search' => 'search',
            'LinkClick' => 'click',
            'PhoneClick' => 'phone_click',
            'EmailClick' => 'email_click',
            default => strtolower(str_replace(' ', '_', $event->event_name)),
        };
        $properties = $event->properties ?? [];
        $params = array_filter([
            'engagement_time_msec' => 1,
            'session_id' => $event->ga_session_id,
            'transaction_id' => $name === 'purchase' ? ($properties['order_id'] ?? null) : null,
            'value' => $properties['value'] ?? $properties['total'] ?? null,
            'currency' => $properties['currency'] ?? null,
            'coupon' => $properties['coupon'] ?? null,
            'shipping' => $properties['shipping'] ?? null,
            'tax' => $properties['tax'] ?? null,
            'items' => $this->ga4Items($properties['products'] ?? []),
            'debug_mode' => $settings['test_mode'] ? 1 : null,
        ], static fn ($value): bool => $value !== null);

        $endpoint = 'https://www.google-analytics.com/mp/collect?measurement_id='.rawurlencode($config['measurement_id']).'&api_secret='.rawurlencode($config['api_secret']);
        $response = Http::connectTimeout(2)->timeout(6)->acceptJson()->post($endpoint, [
            'client_id' => $event->ga_client_id,
            'events' => [['name' => $name, 'params' => $params]],
        ]);

        return $this->responseResult($response, $response->successful(), 'GA4 returned a successful HTTP response; event validation and reporting are not confirmed.', 'GA4 did not accept the event request.');
    }

    /** @return array{accepted: false, http_status: null, summary: string, retryable: false, failure_status: string} */
    private function blocked(string $status, string $summary): array
    {
        return ['accepted' => false, 'http_status' => null, 'summary' => $summary, 'retryable' => false, 'failure_status' => $status];
    }

    /** @return array{accepted: bool, http_status: int, summary: string, retryable: bool, failure_status: string|null} */
    private function responseResult(Response $response, bool $accepted, string $successSummary, string $failureSummary): array
    {
        return [
            'accepted' => $accepted,
            'http_status' => $response->status(),
            'summary' => $accepted ? $successSummary : $failureSummary,
            'retryable' => ! $accepted && ($response->status() === 429 || $response->serverError()),
            'failure_status' => $accepted ? null : 'failed',
        ];
    }

    private function metaCustomData(array $properties): array
    {
        $custom = array_filter([
            'value' => $properties['value'] ?? $properties['total'] ?? null,
            'currency' => $properties['currency'] ?? null,
            'content_ids' => $this->contentIds($properties['products'] ?? []),
            'content_type' => 'product',
            'contents' => $this->contents($properties['products'] ?? []),
            'order_id' => $properties['order_id'] ?? null,
        ], static fn ($value): bool => $value !== null && $value !== []);

        return $custom;
    }

    private function tiktokProperties(array $properties): array
    {
        $products = array_values(array_map(static fn (array $product): array => array_filter([
            'content_id' => $product['product_id'] ?? $product['sku'] ?? null,
            'content_name' => $product['name'] ?? null,
            'content_category' => $product['category'] ?? null,
            'brand' => $product['brand'] ?? null,
            'price' => $product['price'] ?? null,
            'quantity' => $product['quantity'] ?? null,
        ], static fn ($value): bool => $value !== null), array_filter($properties['products'] ?? [], 'is_array')));

        return array_filter([
            'value' => $properties['value'] ?? $properties['total'] ?? null,
            'currency' => $properties['currency'] ?? null,
            'content_type' => 'product',
            'contents' => $products,
        ], static fn ($value): bool => $value !== null && $value !== []);
    }

    private function ga4Items(array $products): array
    {
        return array_values(array_map(static fn (array $product): array => array_filter([
            'item_id' => $product['product_id'] ?? $product['sku'] ?? null,
            'item_name' => $product['name'] ?? null,
            'item_brand' => $product['brand'] ?? null,
            'item_category' => $product['category'] ?? null,
            'price' => $product['price'] ?? null,
            'quantity' => $product['quantity'] ?? null,
        ], static fn ($value): bool => $value !== null), array_filter($products, 'is_array')));
    }

    private function contentIds(array $products): array
    {
        return array_values(array_filter(array_map(static fn (array $product): ?string => isset($product['product_id']) ? (string) $product['product_id'] : (isset($product['sku']) ? (string) $product['sku'] : null), array_filter($products, 'is_array'))));
    }

    private function contents(array $products): array
    {
        return array_values(array_map(static fn (array $product): array => array_filter([
            'id' => $product['product_id'] ?? $product['sku'] ?? null,
            'quantity' => $product['quantity'] ?? null,
            'item_price' => $product['price'] ?? null,
        ], static fn ($value): bool => $value !== null), array_filter($products, 'is_array')));
    }
}
