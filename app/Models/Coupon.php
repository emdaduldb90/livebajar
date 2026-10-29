<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'minimum_order_amount',
        'usage_limit', 'used_count', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isUsableFor(int $subtotalCents, ?Carbon $now = null): bool
    {
        $now ??= now();
        $minimumCents = (int) round(((float) $this->minimum_order_amount) * 100);

        return $this->is_active
            && $subtotalCents >= $minimumCents
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->expires_at === null || $this->expires_at->gte($now))
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function discountCents(int $subtotalCents): int
    {
        if ($this->discount_type === 'percentage') {
            return min($subtotalCents, (int) round($subtotalCents * ((float) $this->discount_value / 100)));
        }

        return min($subtotalCents, (int) round(((float) $this->discount_value) * 100));
    }
}
