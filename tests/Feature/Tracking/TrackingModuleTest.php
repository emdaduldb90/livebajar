<?php

namespace Tests\Feature\Tracking;

use App\Models\Order;
use App\Models\TrackingDelivery;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Jobs\DeliverTrackingEvent;
use App\Services\Tracking\TrackingAnalyticsService;
use App\Services\Tracking\TrackingEventRecorder;
use App\Services\Tracking\TrackingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TrackingModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_uses_existing_admin_session_and_denies_non_admins(): void
    {
        $this->get(route('admin.tracking.index'))->assertRedirect(route('admin.login'));

        $nonAdmin = User::factory()->create(['is_admin' => false]);
        $this->actingAs($nonAdmin)->get(route('admin.tracking.index'))->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $panel = $this->actingAs($admin)->get(route('admin.tracking.index'));
        $panel->assertOk()
            ->assertSee('tracking-admin.css', false)
            ->assertDontSee('<style>', false)
            ->assertDontSee(' style=', false);
        $this->assertStringContainsString("style-src 'self'", (string) $panel->headers->get('Content-Security-Policy'));
    }

    public function test_credentials_are_encrypted_and_never_exposed_in_public_config(): void
    {
        $settings = app(TrackingSettingsService::class);
        $settings->save(['meta' => ['access_token' => 'meta-test-secret']]);

        $stored = (string) DB::table('tracking_configurations')->value('payload');
        $public = $settings->publicConfig();

        $this->assertStringNotContainsString('meta-test-secret', $stored);
        $this->assertArrayNotHasKey('access_token', $public['meta']);
        $this->assertTrue($settings->all()['meta']['access_token'] === 'meta-test-secret');
    }

    public function test_invalid_settings_do_not_flash_connector_secrets_back_into_the_session(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.tracking.index', ['section' => 'setup']))
            ->put(route('admin.tracking.settings.update'), [
                'retention_days' => 10,
                'meta' => ['access_token' => 'must-not-be-flashed'],
                'tiktok' => ['access_token' => 'also-secret'],
                'ga4' => ['api_secret' => 'mp-secret'],
                'openai' => [
                    'api_key' => 'openai-secret',
                    'model' => 'gpt-4.1-mini',
                    'reports_per_day' => 1,
                    'max_output_tokens' => 1200,
                ],
            ])
            ->assertRedirect(route('admin.tracking.index', ['section' => 'setup']))
            ->assertSessionMissing('_old_input.meta.access_token')
            ->assertSessionMissing('_old_input.tiktok.access_token')
            ->assertSessionMissing('_old_input.ga4.api_secret')
            ->assertSessionMissing('_old_input.openai.api_key');
    }

    public function test_search_collection_discards_search_text_and_url_query(): void
    {
        app(TrackingSettingsService::class)->save(['collection_enabled' => true, 'require_consent' => false]);

        $this->trackingPost([
            'event_id' => 'search-event-1',
            'event_name' => 'Search',
            'properties' => ['search_length' => 20, 'result_count' => 3, 'query' => 'customer@example.test'],
            'page_path' => '/shop?q=customer%40example.test',
            'referrer' => 'https://search.example.test/?q=private',
            'consent_state' => 'unknown',
        ])->assertAccepted();

        $event = TrackingEvent::query()->firstOrFail();
        $this->assertSame('/shop', $event->page_path);
        $this->assertSame('search.example.test', $event->referrer_host);
        $this->assertSame(['search_length' => 20, 'result_count' => 3], $event->properties);
        $this->assertDatabaseMissing('tracking_events', ['page_path' => '/shop?q=customer%40example.test']);
    }

    public function test_required_consent_blocks_first_party_collection_by_default(): void
    {
        app(TrackingSettingsService::class)->save(['collection_enabled' => true, 'require_consent' => true]);

        $this->trackingPost([
            'event_id' => 'page-event-1',
            'event_name' => 'PageView',
            'page_path' => '/',
            'consent_state' => 'unknown',
        ])->assertAccepted();

        $this->assertDatabaseCount('tracking_events', 0);
    }

    public function test_storefront_exposes_optional_tracking_choices_when_collection_is_enabled(): void
    {
        config(['tracking.allow_non_production' => true]);
        app(TrackingSettingsService::class)->save(['collection_enabled' => true, 'require_consent' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-enabled="1" hidden', false)
            ->assertSee('tracking-consent.css', false)
            ->assertSee('Reject optional tracking')
            ->assertSee('Allow optional tracking');
    }

    public function test_server_purchase_is_idempotent_and_sync_queue_is_blocked(): void
    {
        app(TrackingSettingsService::class)->save([
            'collection_enabled' => true,
            'require_consent' => false,
            'meta' => ['pixel_id' => '123456', 'access_token' => 'secret', 'graph_version' => 'v99.0', 'server_enabled' => true],
            'routes' => ['Purchase' => ['meta_server' => true]],
        ]);
        Session::start();
        $order = Order::query()->create([
            'order_number' => 'LB-TRACKING123456',
            'tracking_checkout_id' => 'dff8912a-6587-49d2-8d78-514be149b268',
            'tracking_purchase_event_id' => 'b6fc6940-62a2-4ab1-99ab-79653b19aaba',
            'customer_name' => 'Private Person',
            'customer_email' => 'private@example.test',
            'customer_phone' => '+8801700000000',
            'city' => 'Dhaka',
            'shipping_zone' => 'dhaka',
            'address' => 'private address',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total' => 100,
        ]);

        $request = \Illuminate\Http\Request::create('/checkout', 'POST');
        $request->setLaravelSession(Session::driver());
        $recorder = app(TrackingEventRecorder::class);
        $recorder->recordOrderPurchase($order, $request);
        $recorder->recordOrderPurchase($order, $request);

        $this->assertDatabaseCount('tracking_events', 1);
        $this->assertDatabaseHas('tracking_events', ['event_name' => 'Purchase', 'event_id' => $order->tracking_purchase_event_id, 'event_source' => 'server']);
        $this->assertSame([], TrackingEvent::query()->firstOrFail()->properties['products'] ?? []);
        $this->assertSame('blocked_queue', TrackingDelivery::query()->firstOrFail()->status);
    }

    public function test_analytics_counts_browser_server_pair_as_one_unique_event(): void
    {
        foreach (['browser', 'server'] as $source) {
            TrackingEvent::query()->create([
                'event_id' => 'purchase-same-id',
                'event_name' => 'Purchase',
                'event_type' => 'ecommerce',
                'event_source' => $source,
                'consent_state' => 'granted',
                'properties' => ['value' => 100, 'currency' => 'BDT'],
                'occurred_at' => now(),
            ]);
        }

        $dashboard = app(TrackingAnalyticsService::class)->dashboard(30);

        $this->assertSame(2, $dashboard['observed_events']);
        $this->assertSame(1, $dashboard['deduplicated_events']);
        $this->assertSame(1, $dashboard['funnel']['Purchase']);
    }

    public function test_admin_can_retry_delivery_after_destination_is_ready(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();
        $settings = app(TrackingSettingsService::class);
        $settings->save([
            'collection_enabled' => true,
            'require_consent' => true,
            'test_mode' => false,
            'meta' => ['pixel_id' => '123456', 'access_token' => 'secret', 'graph_version' => 'v22.0', 'server_enabled' => true],
            'routes' => ['Purchase' => ['meta_server' => true]],
        ]);
        $event = TrackingEvent::query()->create([
            'event_id' => 'retry-purchase-1',
            'event_name' => 'Purchase',
            'event_type' => 'ecommerce',
            'event_source' => 'server',
            'consent_state' => 'granted',
            'match_data' => ['email' => hash('sha256', 'buyer@example.test')],
            'properties' => ['order_id' => 'LB-RETRY12345678', 'total' => 100, 'currency' => 'BDT'],
            'occurred_at' => now(),
        ]);
        $delivery = TrackingDelivery::query()->create([
            'tracking_event_id' => $event->id,
            'destination' => 'meta',
            'channel' => 'server',
            'status' => 'blocked_test_mode',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.tracking.deliveries.retry', $delivery))
            ->assertRedirect(route('admin.tracking.index', ['section' => 'deliveries']));

        $this->assertDatabaseHas('tracking_deliveries', ['id' => $delivery->id, 'status' => 'queued']);
        Queue::assertPushedOn('tracking', DeliverTrackingEvent::class);
    }

    private function trackingPost(array $payload): \Illuminate\Testing\TestResponse
    {
        $url = route('tracking.events.store');
        $origin = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);

        return $this->withHeader('Origin', $origin)->postJson($url, $payload);
    }
}
