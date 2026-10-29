<?php

namespace Tests\Feature\Tracking;

use App\Models\TrackingDelivery;
use App\Models\TrackingEvent;
use App\Services\Tracking\TrackingDeliveryService;
use App\Services\Tracking\TrackingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_purchase_uses_shared_event_id_and_test_code(): void
    {
        app(TrackingSettingsService::class)->save([
            'collection_enabled' => true,
            'require_consent' => true,
            'test_mode' => true,
            'meta' => [
                'pixel_id' => '123456789',
                'access_token' => 'server-token',
                'graph_version' => 'v22.0',
                'test_event_code' => 'TEST123',
                'server_enabled' => true,
            ],
            'routes' => ['Purchase' => ['meta_server' => true]],
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        $delivery = $this->purchaseDelivery('meta');

        $result = app(TrackingDeliveryService::class)->deliver($delivery);

        $this->assertTrue($result['accepted']);
        $this->assertSame(200, $result['http_status']);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/v22.0/123456789/events')
                && $request->data()['data'][0]['event_id'] === 'purchase-shared-id'
                && $request->data()['data'][0]['event_name'] === 'Purchase'
                && $request->data()['test_event_code'] === 'TEST123'
                && isset($request->data()['data'][0]['user_data']['em']);
        });
    }

    public function test_tiktok_is_not_sent_with_unverified_test_payload(): void
    {
        app(TrackingSettingsService::class)->save([
            'collection_enabled' => true,
            'require_consent' => true,
            'test_mode' => true,
            'tiktok' => ['pixel_id' => 'PIXEL123', 'access_token' => 'secret', 'server_enabled' => true],
            'routes' => ['Purchase' => ['tiktok_server' => true]],
        ]);
        Http::fake();
        $delivery = $this->purchaseDelivery('tiktok');

        $result = app(TrackingDeliveryService::class)->deliver($delivery);

        $this->assertFalse($result['accepted']);
        $this->assertStringContainsString('blocked in Test Mode', $result['summary']);
        Http::assertNothingSent();
    }

    public function test_ga4_measurement_protocol_requires_and_sends_consent_scoped_client_id(): void
    {
        app(TrackingSettingsService::class)->save([
            'collection_enabled' => true,
            'require_consent' => true,
            'test_mode' => true,
            'ga4' => ['measurement_id' => 'G-ABC123', 'api_secret' => 'mp-secret', 'server_enabled' => true],
            'routes' => ['Purchase' => ['ga4_server' => true]],
        ]);
        Http::fake(['www.google-analytics.com/*' => Http::response([], 204)]);
        $event = TrackingEvent::query()->create([
            'event_id' => 'ga4-purchase-1',
            'event_name' => 'Purchase',
            'event_type' => 'ecommerce',
            'event_source' => 'server',
            'consent_state' => 'granted',
            'properties' => ['order_id' => 'LB-ORDER12345678', 'total' => 100, 'currency' => 'BDT'],
            'ga_client_id' => '123456789.1234567890',
            'ga_session_id' => '1234567890',
            'occurred_at' => now(),
        ]);
        $delivery = TrackingDelivery::query()->create(['tracking_event_id' => $event->id, 'destination' => 'ga4', 'channel' => 'server', 'status' => 'queued']);
        $delivery->load('event');

        $result = app(TrackingDeliveryService::class)->deliver($delivery);

        $this->assertTrue($result['accepted']);
        Http::assertSent(function ($request): bool {
            return $request->data()['client_id'] === '123456789.1234567890'
                && $request->data()['events'][0]['name'] === 'purchase'
                && $request->data()['events'][0]['params']['transaction_id'] === 'LB-ORDER12345678'
                && $request->data()['events'][0]['params']['debug_mode'] === 1;
        });
    }

    private function purchaseDelivery(string $destination): TrackingDelivery
    {
        $event = TrackingEvent::query()->create([
            'event_id' => 'purchase-shared-id',
            'event_name' => 'Purchase',
            'event_type' => 'ecommerce',
            'event_source' => 'server',
            'consent_state' => 'granted',
            'page_path' => '/checkout/success',
            'properties' => ['order_id' => 'LB-ORDER12345678', 'total' => 125.5, 'currency' => 'BDT'],
            'match_data' => ['email' => hash('sha256', 'buyer@example.test')],
            'occurred_at' => now(),
        ]);

        return TrackingDelivery::query()->create([
            'tracking_event_id' => $event->id,
            'destination' => $destination,
            'channel' => 'server',
            'status' => 'queued',
        ])->load('event');
    }
}
