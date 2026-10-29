<?php

namespace App\Console\Commands;

use App\Services\Tracking\TrackingSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PruneTrackingEvents extends Command
{
    protected $signature = 'tracking:prune';

    protected $description = 'Delete first-party tracking events after the configured retention period';

    public function handle(TrackingSettingsService $settings): int
    {
        if (! Schema::hasTable('tracking_events')) {
            $this->info('Tracking tables are not installed; nothing to prune.');
            return self::SUCCESS;
        }

        $days = min(365, max(30, (int) $settings->all()['retention_days']));
        $deleted = DB::table('tracking_events')->where('occurred_at', '<', now()->subDays($days))->delete();
        $this->info("Removed {$deleted} expired event row(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
