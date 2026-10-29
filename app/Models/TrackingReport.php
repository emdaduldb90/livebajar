<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingReport extends Model
{
    protected $fillable = [
        'requested_by', 'range_days', 'language', 'status', 'model', 'input_tokens',
        'output_tokens', 'metrics', 'report', 'failure_code',
    ];

    protected $casts = [
        'metrics' => 'array',
        'report' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
