<?php

namespace App\Services\Tracking;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class EcommerceTrackingService
{
    /**
     * The Facebook Commerce feed uses SKU as g:id. Keep every storefront event
     * on the same identifier so catalogue matching remains deterministic.
     *
     * @return array<string, mixed>
     */
    public function product(Product $product, int $quantity = 1, ?int $position = null): array
    {
        $product->loadMissing(['category:id,name,slug', 'brand:id,name']);

        $payload = [
            'product_id' => (string) $product->sku,
            'sku' => (string) $product->sku,
            'category' => (string) $product->category?->name,
            'name' => (string) $product->name,
            'brand' => (string) ($product->brand?->name ?: config('app.name')),
            'price' => $this->money($product->price),
            'quantity' => max(1, $quantity),
            'currency' => $this->currency(),
            'value' => round($this->money($product->price) * max(1, $quantity), 2),
        ];

        if ($position !== null) {
            $payload['position'] = $position;
        }

        return $payload;
    }

    /** @param Collection<int, Product>|iterable<Product> $products */
    public function productList(iterable $products): array
    {
        $list = [];
        $position = 1;

        foreach ($products as $product) {
            $list[] = $this->product($product, 1, $position);
            $position++;
        }

        return $list;
    }

    /**
     * @param  array{items: Collection, subtotal_cents: int, discount_cents: int, shipping_cents: int, total_cents: int, coupon: mixed}  $summary
     * @return array<string, mixed>
     */
    public function cart(array $summary, string $checkoutId): array
    {
        $products = [];
        foreach ($summary['items'] as $position => $item) {
            $products[] = $this->product($item['product'], (int) $item['quantity'], $position + 1);
        }

        return [
            'checkout_id' => $checkoutId,
            'products' => $products,
            'total' => $this->cents($summary['total_cents']),
            'revenue' => $this->cents(max(0, $summary['subtotal_cents'] - $summary['discount_cents'])),
            'shipping' => $this->cents($summary['shipping_cents']),
            'discount' => $this->cents($summary['discount_cents']),
            'coupon' => $summary['coupon']?->code,
            'currency' => $this->currency(),
        ];
    }

    /** @return array<string, mixed> */
    public function order(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'checkout_id' => (string) $order->tracking_checkout_id,
            'order_id' => (string) $order->order_number,
            'total' => $this->money($order->total),
            'revenue' => round(max(0, $this->money($order->subtotal) - $this->money($order->discount_amount)), 2),
            'shipping' => $this->money($order->shipping_amount),
            'tax' => 0.0,
            'discount' => $this->money($order->discount_amount),
            'coupon' => $order->coupon?->code,
            'currency' => $this->currency(),
            'products' => $order->items->values()->map(
                fn (OrderItem $item, int $position): array => $this->orderItem($item, $position + 1)
            )->all(),
        ];
    }

    /** @return array<string, scalar> */
    public function orderItem(OrderItem $item, int $position): array
    {
        $price = $this->money($item->unit_price);

        return [
            'product_id' => (string) $item->sku,
            'sku' => (string) $item->sku,
            'name' => (string) $item->product_name,
            'price' => $price,
            'quantity' => (int) $item->quantity,
            'value' => round($price * (int) $item->quantity, 2),
            'position' => $position,
        ];
    }

    /** @return array<string, scalar> */
    public function customProduct(Product $product): array
    {
        $payload = $this->product($product);

        return [
            'product_id' => $payload['product_id'],
            'sku' => $payload['sku'],
            'category' => $payload['category'],
            'brand' => $payload['brand'],
            'price' => $payload['price'],
            'currency' => $payload['currency'],
        ];
    }

    private function currency(): string
    {
        return (string) config('tracking.currency', 'BDT');
    }

    private function money(mixed $amount): float
    {
        return round((float) $amount, 2);
    }

    private function cents(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
