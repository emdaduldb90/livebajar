<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_configurations', function (Blueprint $table): void {
            $table->id();
            $table->longText('payload');
            $table->timestamps();
        });

        Schema::create('tracking_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id', 120);
            $table->string('event_name', 100)->index();
            $table->string('event_type', 24);
            $table->string('event_source', 16);
            $table->string('consent_state', 16)->default('unknown');
            $table->string('page_path', 512)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('campaign_source', 120)->nullable();
            $table->string('campaign_medium', 120)->nullable();
            $table->string('campaign_name', 120)->nullable();
            $table->string('link_type', 16)->nullable();
            $table->string('destination_host', 255)->nullable();
            $table->json('properties')->nullable();
            $table->text('ga_client_id')->nullable();
            $table->string('ga_session_id', 32)->nullable();
            $table->longText('match_data')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->unique(['event_name', 'event_id', 'event_source'], 'tracking_events_id_source_unique');
            $table->index(['event_name', 'occurred_at'], 'tracking_events_name_time_index');
            $table->index(['destination_host', 'occurred_at'], 'tracking_events_link_time_index');
        });

        Schema::create('tracking_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_event_id')->constrained('tracking_events')->cascadeOnDelete();
            $table->string('destination', 24);
            $table->string('channel', 16);
            $table->string('status', 24)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('response_summary', 500)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['tracking_event_id', 'destination', 'channel'], 'tracking_deliveries_event_destination_channel_unique');
            $table->index(['status', 'created_at']);
        });

        Schema::create('tracking_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('range_days')->default(30);
            $table->string('language', 8)->default('bn');
            $table->string('status', 20)->default('queued');
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->json('metrics');
            $table->json('report')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('tracking_jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_jobs');
        Schema::dropIfExists('tracking_reports');
        Schema::dropIfExists('tracking_deliveries');
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('tracking_configurations');
    }
};
