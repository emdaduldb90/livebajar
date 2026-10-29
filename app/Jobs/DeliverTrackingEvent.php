<?php

namespace App\Jobs;

use App\Models\TrackingDelivery;
use App\Services\Tracking\TrackingDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeliverTrackingEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 20;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $deliveryId)
    {
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(TrackingDeliveryService $service): void
    {
        $claim = DB::transaction(function () {
            $delivery = TrackingDelivery::query()->with('event')->lockForUpdate()->find($this->deliveryId);
            if ($delivery === null || ! in_array($delivery->status, ['queued', 'processing'], true)) {
                return null;
            }
            if ($delivery->status === 'processing' && $delivery->attempted_at?->greaterThan(now()->subMinutes(5))) {
                return 'busy';
            }

            $delivery->forceFill([
                'status' => 'processing',
                'attempts' => $delivery->attempts + 1,
                'attempted_at' => now(),
            ])->save();

            return $delivery->fresh(['event']);
        });

        if ($claim === 'busy') {
            $this->release(300);
            return;
        }
        if (! ($claim instanceof TrackingDelivery)) {
            return;
        }
        $delivery = $claim;

        try {
            $result = $service->deliver($delivery);
        } catch (Throwable) {
            $delivery->forceFill([
                'status' => 'queued',
                'response_summary' => 'Provider transport failed. The job will retry.',
            ])->save();
            throw new \RuntimeException('Tracking provider transport failed.');
        }

        if (! $result['accepted']) {
            $delivery->forceFill([
                'status' => ($result['retryable'] ?? false) ? 'queued' : ($result['failure_status'] ?? 'failed'),
                'http_status' => $result['http_status'],
                'response_summary' => $result['summary'],
            ])->save();
            if ($result['retryable'] ?? false) {
                throw new \RuntimeException('Tracking provider did not accept the event.');
            }

            return;
        }

        $delivery->forceFill([
            'status' => 'accepted',
            'http_status' => $result['http_status'],
            'response_summary' => $result['summary'],
            'delivered_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        TrackingDelivery::query()->whereKey($this->deliveryId)->whereIn('status', ['queued', 'processing'])->update([
            'status' => 'failed',
            'response_summary' => 'Provider rejected the request after retry attempts.',
            'updated_at' => now(),
        ]);
    }
}
