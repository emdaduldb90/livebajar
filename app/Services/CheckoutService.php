<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money;
use App\Support\StorefrontCache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function place(array $data, CartService $cart, string $checkoutId): Order
    {
        $existingOrder = Order::query()->where('tracking_checkout_id', $checkoutId)->first();
        if ($existingOrder !== null) {
            return $existingOrder;
        }

        if ($cart->rawItems() === []) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $cart, $checkoutId): Order {
                    $rawItems = $cart->rawItems();
                    $products = Product::query()
                        ->whereIn('id', array_keys($rawItems))
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $subtotal = 0;
                    foreach ($rawItems as $productId => $quantity) {
                        $product = $products->get($productId);
                        if ($product === null || ! $product->is_active || $product->stock_quantity < $quantity) {
                            throw ValidationException::withMessages([
                                'cart' => 'One or more products no longer have enough stock. Please review your cart.',
                            ]);
                        }
                        $subtotal += $product->price_cents * $quantity;
                    }

                    $coupon = $this->lockedCoupon($cart->couponCode(), $subtotal);
                    $discount = $coupon?->discountCents($subtotal) ?? 0;
                    $shippingOption = $cart->shippingOption($data['shipping_zone']);
                    $shipping = (int) $shippingOption['fee_cents'];

                    $order = Order::create([
                        'order_number' => 'LB-'.Str::upper(Str::random(14)),
                        'tracking_checkout_id' => $checkoutId,
                        'tracking_purchase_event_id' => (string) Str::uuid(),
                        'user_id' => Auth::id(),
                        'coupon_id' => $coupon?->id,
                        'customer_name' => $data['name'],
                        'customer_email' => $data['email'] ?? '',
                        'customer_phone' => $data['phone'],
                        'city' => $shippingOption['city'],
                        'shipping_zone' => $data['shipping_zone'],
                        'address' => $data['address'],
                        'notes' => $data['notes'] ?? null,
                        'payment_method' => $data['payment_method'],
                        'payment_status' => 'pending',
                        'status' => 'pending',
                        'subtotal' => Money::decimal($subtotal),
                        'discount_amount' => Money::decimal($discount),
                        'shipping_amount' => Money::decimal($shipping),
                        'total' => Money::decimal(max(0, $subtotal - $discount + $shipping)),
                    ]);

                    foreach ($rawItems as $productId => $quantity) {
                        $product = $products->get($productId);
                        $lineTotal = $product->price_cents * $quantity;

                        $order->items()->create([
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'product_name' => $product->name,
                            'unit_price' => Money::decimal($product->price_cents),
                            'purchase_price' => $product->purchase_price === null ? null : Money::decimal($product->purchase_price_cents),
                            'quantity' => $quantity,
                            'line_total' => Money::decimal($lineTotal),
                        ]);

                        $product->decrement('stock_quantity', $quantity);
                    }

                    if ($coupon !== null) {
                        $coupon->increment('used_count');
                    }

                    StorefrontCache::forgetFeaturedProducts();

                    return $order;
                }, 3);
            } catch (QueryException $exception) {
                $existingOrder = Order::query()->where('tracking_checkout_id', $checkoutId)->first();
                if ($existingOrder !== null) {
                    return $existingOrder;
                }

                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to create the order.');
    }

    private function lockedCoupon(?string $code, int $subtotal): ?Coupon
    {
        if ($code === null) {
            return null;
        }

        $coupon = Coupon::query()->where('code', $code)->lockForUpdate()->first();
        if ($coupon === null || ! $coupon->isUsableFor($subtotal)) {
            throw ValidationException::withMessages(['coupon' => 'The applied coupon is no longer available.']);
        }

        return $coupon;
    }
}
