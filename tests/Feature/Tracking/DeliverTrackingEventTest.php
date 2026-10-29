<?php

namespace Tests\Feature\Tracking;

use App\Jobs\DeliverTrackingEvent;
use App\Models\TrackingDelivery;
use App\Models\TrackingEvent;
use App\Services\Tracking\TrackingDeliveryService;
use App\Services\Tracking\TrackingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DeliverTrackingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_permanent_provider_response_is_not_retried(): void
    {
        $delivery = $this->queuedMetaPurchase();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'bad request']], 400)]);

        (new DeliverTrackingEvent($delivery->id))->handle(app(TrackingDeliveryService::class));

        $this->assertDatabaseHas('tracking_deliveries', [
            'id' => $delivery->id,
            'status' => 'failed',
            'attempts' => 1,
            'http_status' => 400,
        ]);
    }

    public function test_server_error_is_left_queued_for_capped_backoff_retry(): void
    {
        $delivery = $this->queuedMetaPurchase();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'temporary']], 503)]);

        try {
            (new DeliverTrackingEvent($delivery->id))->handle(app(TrackingDeliveryService::class));
            $this->fail('A retryable provider response should be rethrown to Laravel queue retries.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Tracking provider did not accept the event.', $exception->getMessage());
        }

        $this->assertDatabaseHas('tracking_deliveries', [
            'id' => $delivery->id,
            'status' => 'queued',
            'attempts' => 1,
            'http_status' => 503,
        ]);
    }

    private function queuedMetaPurchase(): TrackingDelivery
    {
        app(TrackingSettingsService::class)->save([
            'collection_enabled' => true,
            'require_consent' => true,
            'test_mode' => false,
            'meta' => [
                'pixel_id' => '123456789',
                'access_token' => 'server-token',
                'graph_version' => 'v22.0',
                'server_enabled' => true,
            ],
            'routes' => ['Purchase' => ['meta_server' => true]],
        ]);
        $event = TrackingEvent::query()->create([
            'event_id' => 'atomic-purchase-id',
            'event_name' => 'Purchase',
            'event_type' => 'ecommerce',
            'event_source' => 'server',
            'consent_state' => 'granted',
            'match_data' => ['email' => hash('sha256', 'buyer@example.test')],
            'properties' => ['order_id' => 'LB-ATOMIC1234567', 'total' => 100, 'currency' => 'BDT'],
            'occurred_at' => now(),
        ]);

        return TrackingDelivery::query()->create([
            'tracking_event_id' => $event->id,
            'destination' => 'meta',
            'channel' => 'server',
            'status' => 'queued',
        ]);
    }
}
