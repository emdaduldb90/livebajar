<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'tracking_checkout_id', 'tracking_purchase_event_id', 'user_id', 'coupon_id', 'customer_name', 'customer_email',
        'customer_phone', 'city', 'shipping_zone', 'address', 'notes', 'payment_method',
        'payment_status', 'status', 'subtotal', 'discount_amount', 'shipping_amount', 'total',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getTotalCentsAttribute(): int
    {
        return (int) round(((float) $this->total) * 100);
    }
}
