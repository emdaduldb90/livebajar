<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingDelivery extends Model
{
    protected $fillable = [
        'tracking_event_id', 'destination', 'channel', 'status', 'attempts', 'http_status',
        'response_summary', 'queued_at', 'attempted_at', 'delivered_at',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'attempted_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(TrackingEvent::class, 'tracking_event_id');
    }
}
