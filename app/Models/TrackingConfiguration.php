<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingConfiguration extends Model
{
    protected $fillable = ['payload'];

    protected $casts = [
        'payload' => 'encrypted:array',
    ];
}
