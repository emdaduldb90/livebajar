<?php

namespace App\Services\Tracking;

use App\Models\Order;
use App\Models\TrackingDelivery;
use App\Models\TrackingEvent;
use App\Models\TrackingReport;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TrackingAnalyticsService
{
    public function __construct(private readonly TrackingSettingsService $settings)
    {
    }

    /** @return array<string, mixed> */
    public function dashboard(int $days = 30): array
    {
        $from = now()->subDays($days)->startOfDay();
        $events = TrackingEvent::query()->where('occurred_at', '>=', $from);
        $unique = (clone $events)->select(['event_name', 'event_id'])->distinct();
        $deduplicatedCount = DB::query()->fromSub($unique, 'unique_tracking_events')->count();
        $observedCount = (clone $events)->count();

        $funnel = [];
        foreach (['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase'] as $name) {
            $funnel[$name] = $this->distinctEventCount($name, $from);
        }

        $orders = Order::query()->where('created_at', '>=', $from);
        $orderCount = (clone $orders)->where('status', '!=', 'cancelled')->count();
        $revenue = (float) (clone $orders)->where('status', '!=', 'cancelled')->sum('total');

        return [
            'days' => $days,
            'from' => $from,
            'to' => now(),
            'observed_events' => $observedCount,
            'deduplicated_events' => $deduplicatedCount,
            'duplicate_copies' => max(0, $observedCount - $deduplicatedCount),
            'browser_events' => (clone $events)->where('event_source', 'browser')->count(),
            'server_events' => (clone $events)->where('event_source', 'server')->count(),
            'orders' => $orderCount,
            'revenue' => round($revenue, 2),
            'pending_orders' => (clone $orders)->where('status', 'pending')->count(),
            'cancelled_orders' => (clone $orders)->where('status', 'cancelled')->count(),
            'funnel' => $funnel,
            'top_links' => $this->topLinks($from),
            'campaigns' => $this->campaigns($from),
            'delivery_statuses' => TrackingDelivery::query()
                ->where('created_at', '>=', $from)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'data_quality' => $this->dataQuality(),
        ];
    }

    /** @return array<string, mixed> */
    public function aiMetrics(int $days = 30): array
    {
        $dashboard = $this->dashboard($days);
        $sourceTotals = [];
        foreach ($dashboard['campaigns'] as $campaign) {
            $sourceTotals[$campaign['normalized_source']] = ($sourceTotals[$campaign['normalized_source']] ?? 0) + $campaign['events'];
        }

        return [
            'period_days' => $days,
            'observed_events' => $dashboard['observed_events'],
            'unique_events' => $dashboard['deduplicated_events'],
            'orders_placed' => $dashboard['orders'],
            'gross_order_revenue_bdt' => $dashboard['revenue'],
            'pending_orders' => $dashboard['pending_orders'],
            'cancelled_orders' => $dashboard['cancelled_orders'],
            'funnel_unique_events' => $dashboard['funnel'],
            'campaign_source_groups' => $sourceTotals,
        ];
    }

    /** @return array<string, mixed> */
    private function dataQuality(): array
    {
        $settings = $this->settings->all();
        $driver = (string) config('queue.default', 'sync');
        $gaps = [];
        if (! $settings['collection_enabled']) {
            $gaps[] = ['severity' => 'high', 'message' => 'First-party collection is disabled.'];
        }
        if ($settings['require_consent']) {
            $gaps[] = ['severity' => 'info', 'message' => 'Events without a recorded consent grant are not collected.'];
        }
        if (in_array($driver, ['sync', 'null'], true)) {
            $gaps[] = ['severity' => 'high', 'message' => 'A background queue is not active; provider calls stay blocked.'];
        }
        foreach (['meta', 'tiktok', 'ga4'] as $provider) {
            if (! data_get($settings, "{$provider}.server_enabled")) {
                $gaps[] = ['severity' => 'info', 'message' => ucfirst($provider).' server delivery is disabled.'];
            }
        }

        return ['queue_driver' => $driver, 'gaps' => $gaps];
    }

    /** @return array<int, array<string, mixed>> */
    private function topLinks(\Illuminate\Support\Carbon $from): array
    {
        return TrackingEvent::query()
            ->where('occurred_at', '>=', $from)
            ->whereIn('event_name', ['LinkClick', 'PhoneClick', 'EmailClick'])
            ->select(['event_name', 'link_type', 'destination_host', 'page_path'])
            ->selectRaw('COUNT(DISTINCT event_id) as total')
            ->groupBy('event_name', 'link_type', 'destination_host', 'page_path')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(fn (TrackingEvent $event): array => [
                'event_name' => $event->event_name,
                'type' => $event->link_type ?: strtolower(str_replace('Click', '', $event->event_name)),
                'host' => $event->destination_host ?: '—',
                'page' => $event->page_path ?: '—',
                'total' => (int) $event->getAttribute('total'),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function campaigns(\Illuminate\Support\Carbon $from): array
    {
        $rows = TrackingEvent::query()
            ->where('occurred_at', '>=', $from)
            ->where('event_source', 'browser')
            ->whereNotNull('campaign_source')
            ->select('campaign_source')
            ->selectRaw('COUNT(DISTINCT event_id) as total')
            ->groupBy('campaign_source')
            ->orderByDesc('total')
            ->limit(30)
            ->get();

        return $rows->map(fn (TrackingEvent $event): array => [
            'source' => $event->campaign_source,
            'normalized_source' => $this->normalizeSource((string) $event->campaign_source),
            'events' => (int) $event->getAttribute('total'),
        ])->all();
    }

    private function normalizeSource(string $source): string
    {
        $source = strtolower($source);
        foreach (['google', 'facebook', 'meta', 'instagram', 'tiktok', 'bing', 'newsletter'] as $known) {
            if (str_contains($source, $known)) {
                return $known === 'facebook' || $known === 'instagram' ? 'meta' : $known;
            }
        }

        return in_array($source, ['direct', '(direct)', 'none'], true) ? 'direct' : 'other';
    }

    private function distinctEventCount(string $name, \Illuminate\Support\Carbon $from): int
    {
        $unique = TrackingEvent::query()
            ->where('event_name', $name)
            ->where('occurred_at', '>=', $from)
            ->select(['event_name', 'event_id'])
            ->distinct();

        return DB::query()->fromSub($unique, 'unique_event_names')->count();
    }
}
