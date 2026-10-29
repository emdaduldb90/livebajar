<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingEvent extends Model
{
    protected $fillable = [
        'event_id', 'event_name', 'event_type', 'event_source', 'consent_state',
        'page_path', 'referrer_host', 'campaign_source', 'campaign_medium', 'campaign_name',
        'link_type', 'destination_host',
        'properties', 'ga_client_id', 'ga_session_id', 'match_data', 'occurred_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'ga_client_id' => 'encrypted',
        'match_data' => 'encrypted:array',
        'occurred_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(TrackingDelivery::class);
    }
}
