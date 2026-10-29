<?php

namespace Tests\Feature\Tracking;

use App\Jobs\GenerateTrackingReport;
use App\Models\TrackingReport;
use App\Services\Tracking\TrackingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateTrackingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_only_aggregate_metrics_and_persists_validated_output(): void
    {
        $settings = app(TrackingSettingsService::class);
        $settings->save(['openai' => ['enabled' => true, 'api_key' => 'openai-test-key']]);
        $metrics = [
            'period_days' => 30,
            'unique_events' => 24,
            'orders_placed' => 3,
            'gross_order_revenue_bdt' => 4200,
            'campaign_source_groups' => ['meta' => 6, 'other' => 4],
        ];
        $report = TrackingReport::query()->create([
            'range_days' => 30,
            'language' => 'en',
            'status' => 'queued',
            'model' => 'gpt-4.1-mini',
            'metrics' => $metrics,
        ]);
        $output = [
            'summary' => 'Observed orders increased in the selected period.',
            'plans' => array_map(static fn (int $days): array => [
                'days' => $days,
                'focus' => 'Measure checkout completion.',
                'actions' => ['Review the checkout funnel.'],
                'evidence' => ['Three orders were recorded.'],
                'confidence' => 'low',
                'roi_assumptions' => [],
            ], [7, 30, 90]),
        ];
        Http::fake(['api.openai.com/*' => Http::response([
            'model' => 'gpt-4.1-mini-2025-04-14',
            'usage' => ['input_tokens' => 40, 'output_tokens' => 50],
            'output' => [[
                'type' => 'message',
                'content' => [['type' => 'output_text', 'text' => json_encode($output, JSON_THROW_ON_ERROR)]],
            ]],
        ], 200)]);

        (new GenerateTrackingReport($report->id))->handle($settings);

        $report->refresh();
        $this->assertSame('completed', $report->status);
        $this->assertSame(40, $report->input_tokens);
        $this->assertSame([7, 30, 90], array_column($report->report['plans'], 'days'));
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request->data()['store'] === false
                && $request->data()['text']['format']['type'] === 'json_schema'
                && str_contains($request->data()['input'][1]['content'], 'gross_order_revenue_bdt')
                && ! str_contains($request->data()['input'][1]['content'], 'customer@example.test');
        });
    }

    public function test_invalid_or_rejected_provider_response_does_not_leak_details(): void
    {
        $settings = app(TrackingSettingsService::class);
        $settings->save(['openai' => ['enabled' => true, 'api_key' => 'openai-test-key']]);
        $report = TrackingReport::query()->create([
            'range_days' => 7,
            'language' => 'bn',
            'status' => 'queued',
            'model' => 'gpt-4.1-mini',
            'metrics' => ['unique_events' => 20, 'orders_placed' => 3],
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'private provider detail']], 401)]);

        (new GenerateTrackingReport($report->id))->handle($settings);

        $report->refresh();
        $this->assertSame('failed', $report->status);
        $this->assertSame('provider_or_format_error', $report->failure_code);
        $this->assertNull($report->report);
        $this->assertStringNotContainsString('private provider detail', json_encode($report->toArray()));
    }
}
