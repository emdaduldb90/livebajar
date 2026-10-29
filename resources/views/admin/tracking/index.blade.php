<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tracking &amp; AI Intelligence · Live Bajar</title>
    <link rel="stylesheet" href="{{ asset('css/tracking-admin.css') }}?v={{ filemtime(public_path('css/tracking-admin.css')) }}">
</head>
<body>
<div class="ti-shell">
    <aside class="ti-side">
        <div class="ti-brand"><div><strong>Live Bajar</strong><small>Tracking &amp; Intelligence</small></div><span class="ti-pill">ADMIN</span></div>
        <nav class="ti-nav" aria-label="Tracking navigation">
            @foreach(\App\Http\Controllers\Admin\TrackingController::SECTIONS as $key => $label)
                <a href="{{ route('admin.tracking.index', ['section' => $key, 'days' => $days]) }}" class="{{ $section === $key ? 'active' : '' }}" @if($section === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ti-side-foot">
            <a href="{{ route('admin.dashboard') }}">← Back to Admin</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener">View Store ↗</a>
            <form action="{{ route('admin.logout') }}" method="POST">@csrf<button class="ti-btn secondary" type="submit">Log out</button></form>
        </div>
    </aside>

    <main class="ti-main">
        @if(session('success'))<div class="ti-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="ti-error" role="alert">{{ $errors->first() }}</div>@endif
        <header class="ti-top">
            <div><h1>{{ \App\Http\Controllers\Admin\TrackingController::SECTIONS[$section] }}</h1><div class="ti-muted">Observed, queued, accepted and attributed figures are shown separately.</div></div>
            <div class="ti-row"><form method="GET" action="{{ route('admin.tracking.index') }}"><input type="hidden" name="section" value="{{ $section }}"><select class="ti-range" name="days" aria-label="Reporting period" onchange="this.form.submit()"><option value="7" @selected($days === 7)>Last 7 days</option><option value="30" @selected($days === 30)>Last 30 days</option><option value="90" @selected($days === 90)>Last 90 days</option></select></form><a class="ti-btn secondary" href="{{ route('admin.tracking.export', ['days' => $days]) }}">Export safe CSV</a></div>
        </header>

        @if(in_array($section, ['overview','analytics','funnels','links','campaigns','ecommerce','quality','health','actions','alerts'], true))
            <section class="ti-cards" aria-label="Summary metrics">
                <div class="ti-kpi"><span>Observed event rows</span><strong>{{ number_format($dashboard['observed_events']) }}</strong><small>Browser and verified server records</small></div>
                <div class="ti-kpi"><span>Unique event IDs</span><strong>{{ number_format($dashboard['deduplicated_events']) }}</strong><small>{{ number_format($dashboard['duplicate_copies']) }} duplicate copies excluded</small></div>
                <div class="ti-kpi"><span>Orders placed</span><strong>{{ number_format($dashboard['orders']) }}</strong><small>{{ number_format($dashboard['pending_orders']) }} pending · {{ number_format($dashboard['cancelled_orders']) }} cancelled</small></div>
                <div class="ti-kpi"><span>Gross order value</span><strong>{{ number_format($dashboard['revenue'], 2) }} BDT</strong><small>Non-cancelled orders in this period</small></div>
            </section>
        @endif

        @if(in_array($section, ['overview','funnels','analytics'], true))
            <section class="ti-card"><h2>Observed funnel events</h2>
                @php($largest = max(1, ...array_values($dashboard['funnel'])))
                @foreach($dashboard['funnel'] as $name => $count)
                    <div class="ti-bar-row"><span>{{ $name }}</span><progress class="ti-progress" max="100" value="{{ min(100, (int) round($count / $largest * 100)) }}" aria-label="{{ $count }} unique observed events"></progress><strong>{{ number_format($count) }}</strong></div>
                @endforeach
                <p class="ti-help">Funnel counts are distinct local event IDs by step. The steps are not cohort conversion rates.</p>
            </section>
        @endif

        @if(in_array($section, ['setup','platforms','routing','browser','server','privacy'], true))
            @if(in_array($section, ['setup','platforms'], true))
                <section class="ti-card"><h2>Safe activation checklist</h2><p class="ti-muted">Collection, provider routes and AI are disabled by default. Save settings only after consent, IDs and queue processing are ready. A provider API success does not prove ad attribution.</p><div class="ti-row"><span class="ti-pill {{ $configuration['collection_enabled'] ? 'ok' : 'warn' }}">First-party collection {{ $configuration['collection_enabled'] ? 'enabled' : 'disabled' }}</span><span class="ti-pill {{ $configuration['test_mode'] ? 'warn' : 'ok' }}">{{ $configuration['test_mode'] ? 'Test mode' : 'Production mode' }}</span><span class="ti-pill">Queue: {{ $dashboard['data_quality']['queue_driver'] }}</span></div></section>
            @endif
            <form class="ti-card" action="{{ route('admin.tracking.settings.update') }}" method="POST" autocomplete="off">
                @csrf @method('PUT')
                <h2>Collection and privacy</h2>
                <div class="ti-form-grid">
                    <div><label class="ti-check"><input type="checkbox" name="collection_enabled" value="1" @checked($configuration['collection_enabled'])><span>Enable first-party event collection</span></label><label class="ti-check"><input type="checkbox" name="require_consent" value="1" @checked($configuration['require_consent'])><span>Require the storefront consent grant</span></label><label class="ti-check"><input type="checkbox" name="test_mode" value="1" @checked($configuration['test_mode'])><span>Keep external destinations in test mode</span></label><label class="ti-check"><input type="checkbox" name="allow_customer_matching" value="1" @checked($configuration['allow_customer_matching'])><span>Allow consented, SHA-256 hashed checkout email/phone for server matching</span></label></div>
                    <div class="ti-field"><label for="retention_days">Event retention (days)</label><input id="retention_days" name="retention_days" type="number" min="30" max="365" value="{{ old('retention_days', $configuration['retention_days']) }}"><span class="ti-help">Daily cleanup runs through the Laravel scheduler when configured.</span></div>
                </div>

                <h2 class="ti-section-title">Destinations</h2>
                <div class="ti-provider"><h3>Meta Pixel + Conversions API</h3><div class="ti-form-grid"><div class="ti-field"><label for="meta_pixel">Pixel ID</label><input id="meta_pixel" name="meta[pixel_id]" value="{{ $configuration['meta']['pixel_id'] }}" maxlength="80"></div><div class="ti-field"><label for="meta_version">Graph API version (use a currently supported version)</label><input id="meta_version" name="meta[graph_version]" value="{{ $configuration['meta']['graph_version'] }}" placeholder="vNN.0"><span class="ti-help">Version is explicit because Meta version support changes.</span></div><div class="ti-field"><label for="meta_token">CAPI access token</label><input id="meta_token" name="meta[access_token]" type="password" autocomplete="new-password" placeholder="{{ $secretsConfigured['meta'] ? 'Saved; blank keeps current token' : 'Enter token' }}"><label class="ti-check"><input type="checkbox" name="meta[forget_access_token]" value="1"><span>Clear saved token</span></label><span class="ti-help">{{ $secretsConfigured['meta'] ? 'Token is saved encrypted.' : 'No token saved.' }}</span></div><div class="ti-field"><label for="meta_test">Test Events code</label><input id="meta_test" name="meta[test_event_code]" value="{{ $configuration['meta']['test_event_code'] }}" maxlength="128"></div></div><label class="ti-check"><input type="checkbox" name="meta[browser_enabled]" value="1" @checked($configuration['meta']['browser_enabled'])><span>Enable browser Pixel (Pixel ID is public)</span></label><label class="ti-check"><input type="checkbox" name="meta[server_enabled]" value="1" @checked($configuration['meta']['server_enabled'])><span>Enable queued server CAPI</span></label></div>

                <div class="ti-provider"><h3>TikTok Pixel + Events API 2.0</h3><div class="ti-form-grid"><div class="ti-field"><label for="tiktok_pixel">Pixel ID</label><input id="tiktok_pixel" name="tiktok[pixel_id]" value="{{ $configuration['tiktok']['pixel_id'] }}" maxlength="100"></div><div class="ti-field"><label for="tiktok_token">Events API access token</label><input id="tiktok_token" name="tiktok[access_token]" type="password" autocomplete="new-password" placeholder="{{ $secretsConfigured['tiktok'] ? 'Saved; blank keeps current token' : 'Enter token' }}"><label class="ti-check"><input type="checkbox" name="tiktok[forget_access_token]" value="1"><span>Clear saved token</span></label><span class="ti-help">{{ $secretsConfigured['tiktok'] ? 'Token is saved encrypted.' : 'No token saved.' }}</span></div></div><label class="ti-check"><input type="checkbox" name="tiktok[browser_enabled]" value="1" @checked($configuration['tiktok']['browser_enabled'])><span>Enable browser Pixel</span></label><label class="ti-check"><input type="checkbox" name="tiktok[server_enabled]" value="1" @checked($configuration['tiktok']['server_enabled'])><span>Enable queued Events API</span></label><p class="ti-help">TikTok Events API 2.0 test payload support is not confirmed in the current official schema; queued server delivery remains blocked while Test Mode is on.</p></div>

                <div class="ti-provider"><h3>Google Analytics 4</h3><div class="ti-form-grid"><div class="ti-field"><label for="ga_measurement">Measurement ID</label><input id="ga_measurement" name="ga4[measurement_id]" value="{{ $configuration['ga4']['measurement_id'] }}" placeholder="G-XXXXXXXXXX"></div><div class="ti-field"><label for="ga_secret">Measurement Protocol API secret</label><input id="ga_secret" name="ga4[api_secret]" type="password" autocomplete="new-password" placeholder="{{ $secretsConfigured['ga4'] ? 'Saved; blank keeps current secret' : 'Enter secret' }}"><label class="ti-check"><input type="checkbox" name="ga4[forget_api_secret]" value="1"><span>Clear saved secret</span></label><span class="ti-help">{{ $secretsConfigured['ga4'] ? 'Secret is saved encrypted.' : 'No secret saved.' }}</span></div></div><label class="ti-check"><input type="checkbox" name="ga4[browser_enabled]" value="1" @checked($configuration['ga4']['browser_enabled'])><span>Enable GA4 browser tag</span></label><label class="ti-check"><input type="checkbox" name="ga4[server_enabled]" value="1" @checked($configuration['ga4']['server_enabled'])><span>Enable queued Measurement Protocol (requires consented client ID)</span></label></div>

                <div class="ti-provider"><h3>Google Ads</h3><div class="ti-form-grid"><div class="ti-field"><label for="ads_id">Conversion ID</label><input id="ads_id" name="google_ads[conversion_id]" value="{{ $configuration['google_ads']['conversion_id'] }}"></div><div class="ti-field"><label for="ads_label">Conversion label</label><input id="ads_label" name="google_ads[conversion_label]" value="{{ $configuration['google_ads']['conversion_label'] }}"></div></div><label class="ti-check"><input type="checkbox" name="google_ads[browser_enabled]" value="1" @checked($configuration['google_ads']['browser_enabled'])><span>Enable browser conversion tag</span></label><label class="ti-check"><input type="checkbox" name="google_ads[server_enabled]" value="1" disabled><span>Server uploads unsupported here; OAuth and conversion action configuration are not implemented.</span></label></div>

                <div class="ti-provider"><h3>OpenAI business reports</h3><div class="ti-form-grid"><div class="ti-field"><label for="openai_key">API key</label><input id="openai_key" name="openai[api_key]" type="password" autocomplete="new-password" placeholder="{{ $secretsConfigured['openai'] ? 'Saved; blank keeps current key' : 'Enter API key' }}"><label class="ti-check"><input type="checkbox" name="openai[forget_api_key]" value="1"><span>Clear saved API key</span></label><span class="ti-help">{{ $secretsConfigured['openai'] ? 'Key is saved encrypted.' : 'No key saved.' }}</span></div><div class="ti-field"><label for="openai_model">Model</label><input id="openai_model" name="openai[model]" value="{{ $configuration['openai']['model'] }}"></div><div class="ti-field"><label for="reports_per_day">Maximum reports per day (0 disables requests)</label><input id="reports_per_day" name="openai[reports_per_day]" type="number" min="0" max="5" value="{{ $configuration['openai']['reports_per_day'] }}"></div><div class="ti-field"><label for="output_tokens">Maximum output tokens per report</label><input id="output_tokens" name="openai[max_output_tokens]" type="number" min="100" max="3000" value="{{ $configuration['openai']['max_output_tokens'] }}"></div></div><label class="ti-check"><input type="checkbox" name="openai[enabled]" value="1" @checked($configuration['openai']['enabled'])><span>Enable on-demand AI report generation</span></label><p class="ti-help">AI receives aggregate counts and revenue only. Raw event properties, URLs, emails, phones and order details are excluded.</p></div>

                <h2 class="ti-section-title">Per-event routing</h2><p class="ti-help">Browser and server switches are independent. Server delivery is queued only from verified backend Purchase events. All routes start off.</p>
                <div class="ti-table-wrap"><table class="ti-table ti-route"><thead><tr><th>Event</th><th>Meta browser</th><th>Meta server</th><th>TikTok browser</th><th>TikTok server</th><th>GA4 browser</th><th>GA4 server</th><th>Google Ads browser</th></tr></thead><tbody>
                @foreach($configuration['routes'] as $event => $routes)<tr><td>{{ $event }}</td>@foreach(['meta_browser','meta_server','tiktok_browser','tiktok_server','ga4_browser','ga4_server','google_ads_browser'] as $channel)<td><input type="checkbox" name="routes[{{ $event }}][{{ $channel }}]" value="1" @checked($routes[$channel] ?? false) aria-label="{{ $event }} via {{ $channel }}"></td>@endforeach</tr>@endforeach
                </tbody></table></div>
                <p class="ti-notice">Browser tags are suppressed while Test Mode is on because they do not have a server test code. Use a staging domain to validate browser tags, then explicitly switch to Production Mode. Check Cloudflare Zaraz for existing destination tags before enabling a direct Pixel, or you may double-count events. Enable Meta/TikTok dual-channel Purchase only after confirming the event name and ID match. GA4 Measurement Protocol success is not event validation or attribution. No provider is contacted by this form.</p>
                <button class="ti-btn red" type="submit">Save tracking configuration</button>
            </form>
        @endif

        @if(in_array($section, ['overview','analytics','funnels','links','campaigns','ecommerce','quality','health','actions','alerts'], true))
            <div class="ti-grid">
                @if(in_array($section, ['overview','links'], true))
                    <section class="ti-card"><h2>Link and contact interactions</h2>@if(count($dashboard['top_links']))<div class="ti-table-wrap"><table class="ti-table"><thead><tr><th>Type</th><th>Destination host</th><th>Page path</th><th>Observed</th></tr></thead><tbody>@foreach($dashboard['top_links'] as $link)<tr><td>{{ $link['type'] }}</td><td>{{ $link['host'] }}</td><td>{{ $link['page'] }}</td><td>{{ $link['total'] }}</td></tr>@endforeach</tbody></table></div>@else<div class="ti-empty">No link events in this period.</div>@endif</section>
                @endif
                @if(in_array($section, ['overview','campaigns'], true))
                    <section class="ti-card"><h2>Campaign source observations</h2>@if(count($dashboard['campaigns']))<div class="ti-table-wrap"><table class="ti-table"><thead><tr><th>UTM source</th><th>Normalized group</th><th>Unique events</th></tr></thead><tbody>@foreach($dashboard['campaigns'] as $campaign)<tr><td>{{ $campaign['source'] }}</td><td>{{ $campaign['normalized_source'] }}</td><td>{{ $campaign['events'] }}</td></tr>@endforeach</tbody></table></div>@else<div class="ti-empty">No campaign-tagged browser events were observed.</div>@endif<p class="ti-help">Campaign observations are not ad-platform attribution or ROI.</p></section>
                @endif
                @if(in_array($section, ['overview','health','quality'], true))
                    <section class="ti-card"><h2>Data quality and queue health</h2><p>Queue driver: <span class="ti-pill {{ in_array($dashboard['data_quality']['queue_driver'], ['sync','null'], true) ? 'bad' : 'ok' }}">{{ $dashboard['data_quality']['queue_driver'] }}</span></p>@foreach($dashboard['data_quality']['gaps'] as $gap)<p><span class="ti-pill {{ $gap['severity'] === 'high' ? 'warn' : '' }}">{{ strtoupper($gap['severity']) }}</span> {{ $gap['message'] }}</p>@endforeach</section>
                @endif
                @if(in_array($section, ['overview','ecommerce'], true))
                    <section class="ti-card"><h2>Order facts</h2><p>Placed orders: <strong>{{ $dashboard['orders'] }}</strong></p><p>Gross order value: <strong>{{ number_format($dashboard['revenue'], 2) }} BDT</strong></p><p>Pending: {{ $dashboard['pending_orders'] }} · Cancelled: {{ $dashboard['cancelled_orders'] }}</p><p class="ti-help">Orders are counted from the existing orders table. Status is order workflow state; payment state is not independently reconciled.</p></section>
                @endif
            </div>
        @endif

        @if(in_array($section, ['events','inspector'], true))
            <section class="ti-card"><div class="ti-row ti-row-between"><h2>Recent locally observed events</h2><span class="ti-pill">No IP or user-agent stored</span></div>
                @if($recentEvents->count())<div class="ti-table-wrap"><table class="ti-table"><thead><tr><th>Time</th><th>Event</th><th>Source</th><th>Event ID</th><th>Consent</th><th>Path</th><th>Delivery records</th></tr></thead><tbody>@foreach($recentEvents as $event)<tr><td>{{ $event->occurred_at?->format('Y-m-d H:i:s') }}</td><td><strong>{{ $event->event_name }}</strong></td><td>{{ $event->event_source }}</td><td>{{ \Illuminate\Support\Str::limit($event->event_id, 28) }}</td><td>{{ $event->consent_state }}</td><td>{{ $event->page_path ?: '—' }}</td><td>@forelse($event->deliveries as $delivery)<span class="ti-pill {{ $delivery->status === 'accepted' ? 'ok' : (str_starts_with($delivery->status, 'blocked') ? 'warn' : '') }}">{{ $delivery->destination }}: {{ $delivery->status }}</span> @empty<span class="ti-help">None</span>@endforelse</td></tr>@endforeach</tbody></table></div><div class="ti-pagination">{{ $recentEvents->onEachSide(1)->links('pagination::simple-tailwind') }}</div>@else<div class="ti-empty">No events have been collected yet. First-party collection is disabled until an admin enables it.</div>@endif
            </section>
        @endif

        @if(in_array($section, ['deliveries','server'], true))
            <section class="ti-card"><h2>Server delivery records</h2><p class="ti-help">“Accepted” means the provider accepted an HTTP request. It does not mean the event was validated, attributed or included in reports.</p>
                @if($deliveries->count())<div class="ti-table-wrap"><table class="ti-table"><thead><tr><th>Queued</th><th>Event</th><th>Destination</th><th>Status</th><th>Attempts</th><th>HTTP</th><th>Details</th><th></th></tr></thead><tbody>@foreach($deliveries as $delivery)<tr><td>{{ $delivery->queued_at?->format('Y-m-d H:i:s') ?: $delivery->created_at?->format('Y-m-d H:i:s') }}</td><td>{{ $delivery->event?->event_name }}</td><td>{{ strtoupper($delivery->destination) }}</td><td><span class="ti-pill {{ $delivery->status === 'accepted' ? 'ok' : (str_starts_with($delivery->status, 'blocked') ? 'warn' : ($delivery->status === 'failed' ? 'bad' : '')) }}">{{ $delivery->status }}</span></td><td>{{ $delivery->attempts }}</td><td>{{ $delivery->http_status ?: '—' }}</td><td>{{ $delivery->response_summary ?: '—' }}</td><td>@if(in_array($delivery->status, ['failed','blocked_queue','blocked_configuration','blocked_identifier','blocked_test_mode','blocked_consent'], true))<form action="{{ route('admin.tracking.deliveries.retry', $delivery) }}" method="POST">@csrf<button class="ti-btn secondary" type="submit">Retry</button></form>@endif</td></tr>@endforeach</tbody></table></div><div class="ti-pagination">{{ $deliveries->onEachSide(1)->links('pagination::simple-tailwind') }}</div>@else<div class="ti-empty">No server deliveries are queued.</div>@endif
            </section>
        @endif

        @if($section === 'ai')
            <section class="ti-card"><h2>Generate an aggregate business plan</h2><p class="ti-muted">Default language is Bangla. Input includes only totals, unique funnel counts and normalized source groups. AI is optional and disabled until an admin configures it.</p><form action="{{ route('admin.tracking.reports.store') }}" method="POST" class="ti-row">@csrf<label>Period <select class="ti-range" name="days"><option value="7">7 days</option><option value="30" selected>30 days</option><option value="90">90 days</option></select></label><label>Language <select class="ti-range" name="language"><option value="bn" selected>বাংলা</option><option value="en">English</option></select></label><button class="ti-btn red" type="submit">Queue report</button></form></section>
            <section class="ti-card"><h2>Report history</h2>@forelse($reports as $report)<article class="ti-report"><div class="ti-row"><strong>{{ $report->language === 'bn' ? 'বাংলা' : 'English' }} · {{ $report->range_days }} days</strong><span class="ti-pill {{ $report->status === 'completed' ? 'ok' : ($report->status === 'failed' ? 'bad' : 'warn') }}">{{ $report->status }}</span><span class="ti-help">{{ $report->created_at?->format('Y-m-d H:i') }}</span></div>@if($report->failure_code && $report->status !== 'completed')<p class="ti-help">{{ $report->failure_code === 'insufficient_data' ? 'No AI request was sent because the sample is too small.' : 'The report could not be generated. Check key, queue and worker status.' }}</p>@endif@if(is_array($report->report) && $report->report !== [])<p>{{ $report->report['summary'] ?? '' }}</p>@foreach(($report->report['plans'] ?? []) as $plan)<h3>{{ $plan['days'] ?? '' }} days · {{ $plan['focus'] ?? '' }} <span class="ti-pill">Confidence: {{ $plan['confidence'] ?? 'low' }}</span></h3><ul>@foreach(($plan['actions'] ?? []) as $action)<li>{{ $action }}</li>@endforeach</ul><p class="ti-help"><strong>Evidence:</strong> {{ implode(' · ', $plan['evidence'] ?? []) }}</p><p class="ti-help"><strong>ROI assumptions:</strong> {{ implode(' · ', $plan['roi_assumptions'] ?? []) }}</p>@endforeach@endif</article>@empty<div class="ti-empty">No reports generated.</div>@endforelse</section>
        @endif

        @if($section === 'backup')
            <section class="ti-card"><h2>Configuration backup</h2><p>Download provider IDs, event routes and non-secret settings. API keys, tokens and secrets are removed from the export.</p><a class="ti-btn" href="{{ route('admin.tracking.backup') }}">Download public configuration JSON</a><p class="ti-help ti-spaced">Restore/import is not enabled. Review the export before sharing it.</p></section>
        @endif

        @if(in_array($section, ['triggers','actions','alerts'], true))
            <section class="ti-card"><h2>{{ \App\Http\Controllers\Admin\TrackingController::SECTIONS[$section] }}</h2><div class="ti-notice"><strong>NOT_YET_IMPLEMENTED.</strong> Visual selector versioning, recommendation completion tracking and threshold-based alert delivery are not active. No mock event triggers or alert states are shown as working.</div><p class="ti-muted">Current supported coverage is the fixed event catalog and verified storefront hooks documented in <code>docs/tracking/INSTRUMENTATION_MAP.md</code>.</p></section>
        @endif

        @if($section === 'privacy')
            <section class="ti-card"><h2>Privacy controls</h2><p>Consent gate: <strong>{{ $configuration['require_consent'] ? 'Required' : 'Not required by this module' }}</strong></p><p>Customer matching: <strong>{{ $configuration['allow_customer_matching'] ? 'Enabled for consented order events' : 'Disabled' }}</strong></p><p>Retention setting: <strong>{{ $configuration['retention_days'] }} days</strong></p><p>Stored event rows exclude raw IP, user agent, email, phone, full URL query and search terms. Provider match hashes are encrypted at rest and only generated when the consent gate passes and matching is enabled.</p><a href="{{ route('admin.tracking.index', ['section' => 'setup']) }}">Edit privacy settings →</a></section>
        @endif

        @if($section === 'health')
            <section class="ti-card"><h2>System health</h2><p>Application queue connection: <strong>{{ $dashboard['data_quality']['queue_driver'] }}</strong></p><p>Collection enabled: <strong>{{ $configuration['collection_enabled'] ? 'Yes' : 'No' }}</strong></p><p>Meta browser/server: {{ $configuration['meta']['browser_enabled'] ? 'enabled' : 'off' }} / {{ $configuration['meta']['server_enabled'] ? 'enabled' : 'off' }}</p><p>TikTok browser/server: {{ $configuration['tiktok']['browser_enabled'] ? 'enabled' : 'off' }} / {{ $configuration['tiktok']['server_enabled'] ? 'enabled' : 'off' }}</p><p>GA4 browser/server: {{ $configuration['ga4']['browser_enabled'] ? 'enabled' : 'off' }} / {{ $configuration['ga4']['server_enabled'] ? 'enabled' : 'off' }}</p><p>AI reports: {{ $configuration['openai']['enabled'] ? 'enabled' : 'off' }}</p><p class="ti-help">Provider delivery and attribution require provider-side diagnostics. This page checks local configuration only.</p></section>
        @endif
    </main>
</div>
</body>
</html>
