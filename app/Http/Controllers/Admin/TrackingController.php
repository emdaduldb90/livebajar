<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverTrackingEvent;
use App\Jobs\GenerateTrackingReport;
use App\Models\TrackingDelivery;
use App\Models\TrackingEvent;
use App\Models\TrackingReport;
use App\Services\Tracking\TrackingAnalyticsService;
use App\Services\Tracking\TrackingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public const SECTIONS = [
        'overview' => 'Overview Dashboard',
        'setup' => 'Configuration / Setup Wizard',
        'platforms' => 'Platform & Pixel Management',
        'events' => 'Event Management',
        'triggers' => 'Visual Trigger Builder',
        'routing' => 'Event Routing Matrix',
        'browser' => 'Browser Tracking',
        'server' => 'Server Tracking',
        'inspector' => 'Live Event Inspector',
        'analytics' => 'Analytics Dashboard',
        'funnels' => 'Conversion Funnels',
        'links' => 'Link & Button Analytics',
        'campaigns' => 'Campaign Attribution',
        'ecommerce' => 'E-commerce Intelligence',
        'ai' => 'AI Planning & Reports',
        'actions' => 'Recommendations / Action Tracker',
        'alerts' => 'Smart Alerts',
        'quality' => 'Deduplication & Data Quality',
        'deliveries' => 'Delivery Logs & Retry',
        'privacy' => 'Consent & Privacy',
        'health' => 'System Health',
        'backup' => 'Settings / Configuration Backup',
    ];

    public function index(Request $request, TrackingAnalyticsService $analytics, TrackingSettingsService $settings): View
    {
        abort_unless(Schema::hasTable('tracking_events'), 503, 'Run the tracking-intelligence database migration before opening this panel.');

        $section = $request->string('section')->toString();
        $section = array_key_exists($section, self::SECTIONS) ? $section : 'overview';
        $days = min(90, max(7, $request->integer('days', 30)));
        $dashboard = $analytics->dashboard($days);
        $configuration = $settings->all();
        $secretsConfigured = [
            'meta' => filled(data_get($configuration, 'meta.access_token')),
            'tiktok' => filled(data_get($configuration, 'tiktok.access_token')),
            'ga4' => filled(data_get($configuration, 'ga4.api_secret')),
            'openai' => filled(data_get($configuration, 'openai.api_key')),
        ];
        $recentEvents = TrackingEvent::query()->with('deliveries')->orderByDesc('occurred_at')->paginate(25, ['*'], 'events_page')->withQueryString();
        $deliveries = TrackingDelivery::query()->with('event')->orderByDesc('created_at')->paginate(25, ['*'], 'delivery_page')->withQueryString();
        $reports = TrackingReport::query()->orderByDesc('created_at')->limit(10)->get();

        return view('admin.tracking.index', compact('section', 'days', 'dashboard', 'configuration', 'secretsConfigured', 'recentEvents', 'deliveries', 'reports'));
    }

    public function update(Request $request, TrackingSettingsService $settings): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'collection_enabled' => ['nullable', 'boolean'],
            'require_consent' => ['nullable', 'boolean'],
            'test_mode' => ['nullable', 'boolean'],
            'allow_customer_matching' => ['nullable', 'boolean'],
            'retention_days' => ['required', 'integer', 'between:30,365'],
            'meta.pixel_id' => ['nullable', 'string', 'max:80'],
            'meta.access_token' => ['nullable', 'string', 'max:2048'],
            'meta.test_event_code' => ['nullable', 'string', 'max:128'],
            'meta.forget_access_token' => ['nullable', 'boolean'],
            'meta.graph_version' => ['nullable', 'regex:/^v\d+\.\d+$/'],
            'meta.browser_enabled' => ['nullable', 'boolean'],
            'meta.server_enabled' => ['nullable', 'boolean'],
            'tiktok.pixel_id' => ['nullable', 'string', 'max:100'],
            'tiktok.access_token' => ['nullable', 'string', 'max:2048'],
            'tiktok.forget_access_token' => ['nullable', 'boolean'],
            'tiktok.browser_enabled' => ['nullable', 'boolean'],
            'tiktok.server_enabled' => ['nullable', 'boolean'],
            'ga4.measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/i'],
            'ga4.api_secret' => ['nullable', 'string', 'max:500'],
            'ga4.forget_api_secret' => ['nullable', 'boolean'],
            'ga4.browser_enabled' => ['nullable', 'boolean'],
            'ga4.server_enabled' => ['nullable', 'boolean'],
            'google_ads.conversion_id' => ['nullable', 'string', 'max:80'],
            'google_ads.conversion_label' => ['nullable', 'string', 'max:180'],
            'google_ads.browser_enabled' => ['nullable', 'boolean'],
            'google_ads.server_enabled' => ['nullable', 'boolean'],
            'openai.api_key' => ['nullable', 'string', 'max:2048'],
            'openai.forget_api_key' => ['nullable', 'boolean'],
            'openai.model' => ['required', 'regex:/^[A-Za-z0-9._-]{1,80}$/'],
            'openai.enabled' => ['nullable', 'boolean'],
            'openai.reports_per_day' => ['required', 'integer', 'between:0,5'],
            'openai.max_output_tokens' => ['required', 'integer', 'between:100,3000'],
            'routes' => ['nullable', 'array'],
            'routes.*' => ['nullable', 'array'],
            'routes.*.*' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except(['meta', 'tiktok', 'ga4', 'openai']));
        }
        $validated = $validator->validated();

        $current = $settings->all();
        $saved = [
            'collection_enabled' => $request->boolean('collection_enabled'),
            'require_consent' => $request->boolean('require_consent'),
            'test_mode' => $request->boolean('test_mode'),
            'allow_customer_matching' => $request->boolean('allow_customer_matching'),
            'retention_days' => (int) $validated['retention_days'],
            'meta' => $this->providerSettings($validated['meta'] ?? [], $current['meta']),
            'tiktok' => $this->providerSettings($validated['tiktok'] ?? [], $current['tiktok']),
            'ga4' => $this->providerSettings($validated['ga4'] ?? [], $current['ga4']),
            'google_ads' => $this->providerSettings($validated['google_ads'] ?? [], $current['google_ads']),
            'openai' => $this->providerSettings($validated['openai'] ?? [], $current['openai']),
            'routes' => $this->routeSettings($validated['routes'] ?? []),
        ];

        try {
            $this->validateActivation($saved);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput($request->except(['meta', 'tiktok', 'ga4', 'openai']));
        }
        $settings->save($saved);

        return to_route('admin.tracking.index', ['section' => 'setup'])->with('success', 'Tracking settings saved. Secrets are encrypted and remain server-side.');
    }

    public function generateReport(Request $request, TrackingAnalyticsService $analytics, TrackingSettingsService $settings): RedirectResponse
    {
        $input = $request->validate([
            'days' => ['required', 'integer', 'in:7,30,90'],
            'language' => ['required', 'in:bn,en'],
        ]);
        $configuration = $settings->all();
        if (! $configuration['openai']['enabled'] || ! filled($configuration['openai']['api_key'])) {
            throw ValidationException::withMessages(['ai' => 'Enable AI reports and save an OpenAI API key in Configuration first.']);
        }
        if (in_array((string) config('queue.default'), ['sync', 'null'], true)) {
            throw ValidationException::withMessages(['ai' => 'AI reports need an asynchronous queue. Configure QUEUE_CONNECTION=database and run a queue worker.']);
        }
        $limit = max(0, min(5, (int) $configuration['openai']['reports_per_day']));
        if ($limit === 0 || TrackingReport::query()->where('created_at', '>=', now()->startOfDay())->count() >= $limit) {
            throw ValidationException::withMessages(['ai' => 'The configured daily AI report limit has been reached.']);
        }

        $metrics = $analytics->aiMetrics((int) $input['days']);
        $insufficient = $metrics['unique_events'] < 20 && $metrics['orders_placed'] < 3;
        $report = TrackingReport::query()->create([
            'requested_by' => $request->user()?->id,
            'range_days' => (int) $input['days'],
            'language' => $input['language'],
            'status' => $insufficient ? 'insufficient_data' : 'queued',
            'model' => $configuration['openai']['model'],
            'metrics' => $metrics,
            'failure_code' => $insufficient ? 'insufficient_data' : null,
            'report' => $insufficient ? ['summary' => $input['language'] === 'bn' ? 'বিশ্বস্ত পরিকল্পনার জন্য পর্যাপ্ত ব্যবহার বা অর্ডার ডেটা নেই।' : 'There is not enough event or order data for a grounded plan.', 'plans' => []] : null,
        ]);

        if (! $insufficient) {
            GenerateTrackingReport::dispatch($report->id)->onQueue('tracking');
        }

        return to_route('admin.tracking.index', ['section' => 'ai'])->with('success', $insufficient ? 'Report saved as insufficient data; no external AI request was sent.' : 'AI report queued. It will appear here after the background worker completes it.');
    }

    public function retryDelivery(TrackingDelivery $delivery, TrackingSettingsService $settings): RedirectResponse
    {
        abort_unless(in_array($delivery->status, ['failed', 'blocked_queue', 'blocked_configuration', 'blocked_identifier', 'blocked_test_mode', 'blocked_consent'], true), 409);
        abort_unless(! in_array((string) config('queue.default'), ['sync', 'null'], true), 409, 'A background queue must be active before retrying delivery.');
        abort_unless($delivery->channel === 'server', 409);

        $delivery->loadMissing('event');
        $event = $delivery->event;
        $configuration = $settings->all();
        abort_unless($event !== null, 404);
        abort_unless($configuration['collection_enabled'], 409, 'Enable tracking collection before retrying delivery.');
        abort_unless(! $configuration['require_consent'] || $event->consent_state === 'granted', 409, 'The event does not have the required consent.');
        abort_unless(data_get($configuration, "routes.{$event->event_name}.{$delivery->destination}_server", false), 409, 'Enable this event route before retrying delivery.');

        $ready = match ($delivery->destination) {
            'meta' => data_get($configuration, 'meta.server_enabled')
                && filled(data_get($configuration, 'meta.pixel_id'))
                && filled(data_get($configuration, 'meta.access_token'))
                && filled(data_get($configuration, 'meta.graph_version'))
                && (! $configuration['test_mode'] || filled(data_get($configuration, 'meta.test_event_code')))
                && ! empty($event->match_data),
            'tiktok' => ! $configuration['test_mode']
                && data_get($configuration, 'tiktok.server_enabled')
                && filled(data_get($configuration, 'tiktok.pixel_id'))
                && filled(data_get($configuration, 'tiktok.access_token'))
                && ! empty($event->match_data),
            'ga4' => data_get($configuration, 'ga4.server_enabled')
                && filled(data_get($configuration, 'ga4.measurement_id'))
                && filled(data_get($configuration, 'ga4.api_secret'))
                && filled($event->ga_client_id),
            default => false,
        };
        abort_unless($ready, 409, 'The destination, route, consent or identifiers are not ready for retry.');

        $delivery->forceFill(['status' => 'queued', 'queued_at' => now(), 'response_summary' => null])->save();
        DeliverTrackingEvent::dispatch($delivery->id)->onQueue('tracking');

        return to_route('admin.tracking.index', ['section' => 'deliveries'])->with('success', 'Delivery retry queued.');
    }

    public function backup(TrackingSettingsService $settings)
    {
        $all = $settings->all();
        foreach (['meta', 'tiktok', 'ga4', 'openai'] as $provider) {
            foreach (['access_token', 'api_secret', 'api_key'] as $secret) {
                unset($all[$provider][$secret]);
            }
        }

        return response()->streamDownload(fn () => print json_encode(['version' => 1, 'exported_at' => now()->toIso8601String(), 'settings' => $all], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'tracking-configuration-public.json', ['Content-Type' => 'application/json']);
    }

    public function export(Request $request)
    {
        abort_unless(Schema::hasTable('tracking_events'), 503);
        $days = min(90, max(1, $request->integer('days', 30)));
        $filename = 'tracking-events-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($days): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['occurred_at', 'event_name', 'event_id', 'source', 'consent', 'page_path', 'referrer_host', 'campaign_source', 'campaign_medium', 'campaign_name', 'value', 'currency', 'link_type', 'destination_host']);
            TrackingEvent::query()->where('occurred_at', '>=', now()->subDays($days))
                ->orderBy('id')->chunk(500, function ($events) use ($output): void {
                    foreach ($events as $event) {
                        $properties = $event->properties ?? [];
                        $row = [
                            $event->occurred_at?->toIso8601String(), $event->event_name, $event->event_id,
                            $event->event_source, $event->consent_state, $event->page_path, $event->referrer_host,
                            $event->campaign_source, $event->campaign_medium, $event->campaign_name,
                            $properties['value'] ?? $properties['total'] ?? null, $properties['currency'] ?? null,
                            $event->link_type, $event->destination_host,
                        ];
                        fputcsv($output, array_map($this->safeCsvCell(...), $row));
                    }
                });
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function providerSettings(array $input, array $current): array
    {
        $result = $current;
        foreach ($input as $key => $value) {
            if (str_starts_with((string) $key, 'forget_') && filter_var($value, FILTER_VALIDATE_BOOL)) {
                $result[substr((string) $key, 7)] = '';
            }
        }
        foreach (array_keys($current) as $key) {
            if (str_ends_with((string) $key, '_enabled') || $key === 'enabled') {
                $result[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOL);
            }
        }
        foreach ($input as $key => $value) {
            if (str_starts_with((string) $key, 'forget_')) {
                continue;
            }
            if (str_ends_with((string) $key, '_enabled') || $key === 'enabled') {
                $result[$key] = filter_var($value, FILTER_VALIDATE_BOOL);
            } elseif (is_string($value) && trim($value) !== '') {
                $result[$key] = trim($value);
            }
        }

        return $result;
    }

    private function routeSettings(array $input): array
    {
        $result = [];
        foreach (array_keys((new TrackingSettingsService)->defaults()['routes']) as $event) {
            $result[$event] = [];
            foreach (array_keys((new TrackingSettingsService)->defaults()['routes'][$event]) as $channel) {
                $result[$event][$channel] = filter_var(data_get($input, "{$event}.{$channel}", false), FILTER_VALIDATE_BOOL);
            }
        }

        return $result;
    }

    private function validateActivation(array $settings): void
    {
        if ($settings['meta']['browser_enabled'] && ! filled($settings['meta']['pixel_id'])) {
            throw ValidationException::withMessages(['meta.pixel_id' => 'Add a Meta Pixel ID before enabling the browser pixel.']);
        }
        if ($settings['meta']['server_enabled'] && (! filled($settings['meta']['pixel_id']) || ! filled($settings['meta']['access_token']) || ! filled($settings['meta']['graph_version']))) {
            throw ValidationException::withMessages(['meta.access_token' => 'Meta server delivery needs a Pixel ID, access token and Graph API version.']);
        }
        if ($settings['meta']['server_enabled'] && $settings['test_mode'] && ! filled($settings['meta']['test_event_code'])) {
            throw ValidationException::withMessages(['meta.test_event_code' => 'Meta Test Events code is required while test mode is on.']);
        }
        if ($settings['tiktok']['browser_enabled'] && ! filled($settings['tiktok']['pixel_id'])) {
            throw ValidationException::withMessages(['tiktok.pixel_id' => 'Add a TikTok Pixel ID before enabling the browser pixel.']);
        }
        if ($settings['tiktok']['server_enabled'] && (! filled($settings['tiktok']['pixel_id']) || ! filled($settings['tiktok']['access_token']))) {
            throw ValidationException::withMessages(['tiktok.access_token' => 'TikTok server delivery needs a Pixel ID and access token.']);
        }
        if (($settings['ga4']['browser_enabled'] || $settings['ga4']['server_enabled']) && ! filled($settings['ga4']['measurement_id'])) {
            throw ValidationException::withMessages(['ga4.measurement_id' => 'Add a GA4 Measurement ID before enabling GA4.']);
        }
        if ($settings['ga4']['server_enabled'] && ! filled($settings['ga4']['api_secret'])) {
            throw ValidationException::withMessages(['ga4.api_secret' => 'GA4 server delivery needs a Measurement Protocol API secret.']);
        }
        if ($settings['google_ads']['browser_enabled'] && (! filled($settings['google_ads']['conversion_id']) || ! filled($settings['google_ads']['conversion_label']))) {
            throw ValidationException::withMessages(['google_ads.conversion_id' => 'Google Ads browser conversions need both a conversion ID and label.']);
        }
        if ($settings['openai']['enabled'] && ! filled($settings['openai']['api_key'])) {
            throw ValidationException::withMessages(['openai.api_key' => 'Add an OpenAI API key before enabling report generation.']);
        }
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[\s]*[=+@-]/u', $value) === 1 ? "'".$value : $value;
    }
}
