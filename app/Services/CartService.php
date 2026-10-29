<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const ITEMS_KEY = 'store.cart.items';
    private const COUPON_KEY = 'store.cart.coupon';
    private const CHECKOUT_ID_KEY = 'store.cart.checkout_id';

    /** @return array<int, int> */
    public function rawItems(): array
    {
        return collect(Session::get(self::ITEMS_KEY, []))
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])
            ->filter(fn ($quantity, $id) => $id > 0 && $quantity > 0)
            ->all();
    }

    /** @return Collection<int, array{product: Product, quantity: int, line_total_cents: int}> */
    public function items(): Collection
    {
        $rawItems = $this->rawItems();

        if ($rawItems === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->with(['category:id,name,slug', 'brand:id,name'])
            ->whereIn('id', array_keys($rawItems))
            ->get()
            ->keyBy('id');

        $validItems = [];
        foreach ($rawItems as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product === null) {
                continue;
            }

            $quantity = min($quantity, max(0, $product->stock_quantity));
            if ($quantity === 0) {
                continue;
            }

            $validItems[$productId] = $quantity;
        }

        if ($validItems !== $rawItems) {
            Session::put(self::ITEMS_KEY, $validItems);
            $this->forgetCheckout();
        }

        return collect($validItems)->map(function (int $quantity, int $productId) use ($products): array {
            $product = $products->get($productId);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'line_total_cents' => $product->price_cents * $quantity,
            ];
        });
    }

    public function count(): int
    {
        return array_sum($this->rawItems());
    }

    public function add(Product $product, int $quantity): int
    {
        if (! $product->is_active || $product->stock_quantity < 1) {
            throw ValidationException::withMessages(['cart' => 'This product is currently unavailable.']);
        }

        $items = $this->rawItems();
        $maximum = min((int) config('store.cart_max_quantity'), $product->stock_quantity);
        $existingQuantity = $items[$product->id] ?? 0;
        $items[$product->id] = min($maximum, $existingQuantity + $quantity);
        Session::put(self::ITEMS_KEY, $items);

        $addedQuantity = $items[$product->id] - $existingQuantity;
        if ($addedQuantity > 0) {
            $this->forgetCheckout();
        }

        return $addedQuantity;
    }

    public function setQuantity(Product $product, int $quantity): void
    {
        $items = $this->rawItems();
        $before = $items;

        if ($quantity < 1) {
            unset($items[$product->id]);
        } else {
            $items[$product->id] = min(
                $quantity,
                min((int) config('store.cart_max_quantity'), max(0, $product->stock_quantity))
            );
        }

        Session::put(self::ITEMS_KEY, array_filter($items));

        if ($before !== $items) {
            $this->forgetCheckout();
        }
    }

    public function remove(Product $product): int
    {
        $items = $this->rawItems();
        $removedQuantity = $items[$product->id] ?? 0;
        unset($items[$product->id]);
        Session::put(self::ITEMS_KEY, $items);
        if ($removedQuantity > 0) {
            $this->forgetCheckout();
        }

        return $removedQuantity;
    }

    public function clear(): void
    {
        Session::forget([self::ITEMS_KEY, self::COUPON_KEY, self::CHECKOUT_ID_KEY]);
    }

    public function applyCoupon(string $code): void
    {
        $subtotal = $this->items()->sum('line_total_cents');
        $coupon = Coupon::query()->where('code', mb_strtoupper(trim($code)))->first();

        if ($coupon === null || ! $coupon->isUsableFor($subtotal)) {
            throw ValidationException::withMessages(['coupon' => 'That coupon is invalid, expired, or cannot be used on this order.']);
        }

        Session::put(self::COUPON_KEY, $coupon->code);
        $this->forgetCheckout();
    }

    public function removeCoupon(): void
    {
        Session::forget(self::COUPON_KEY);
        $this->forgetCheckout();
    }

    public function couponFor(int $subtotalCents): ?Coupon
    {
        $code = Session::get(self::COUPON_KEY);
        if (! is_string($code) || $code === '') {
            return null;
        }

        $coupon = Coupon::query()->where('code', $code)->first();
        if ($coupon === null || ! $coupon->isUsableFor($subtotalCents)) {
            $this->removeCoupon();

            return null;
        }

        return $coupon;
    }

    /** @return array<string, array{label: string, fee_label: string, city: string, fee_cents: int}> */
    public function shippingZones(): array
    {
        return config('store.shipping_zones', []);
    }

    /** @return array{label: string, fee_label: string, city: string, fee_cents: int} */
    public function shippingOption(string $zone): array
    {
        $option = $this->shippingZones()[$zone] ?? null;

        if (! is_array($option)) {
            throw new \InvalidArgumentException('The selected shipping area is not configured.');
        }

        return $option;
    }

    /** @return array{items: Collection, subtotal_cents: int, discount_cents: int, shipping_cents: int, shipping_label: ?string, total_cents: int, coupon: ?Coupon} */
    public function summary(?string $shippingZone = null): array
    {
        $items = $this->items();
        $subtotal = (int) $items->sum('line_total_cents');
        $coupon = $this->couponFor($subtotal);
        $discount = $coupon?->discountCents($subtotal) ?? 0;
        $shippingLabel = null;

        if ($shippingZone !== null) {
            $shippingOption = $this->shippingOption($shippingZone);
            $shipping = (int) $shippingOption['fee_cents'];
            $shippingLabel = (string) $shippingOption['fee_label'];
        } else {
            $shipping = $subtotal === 0 || $subtotal >= (int) config('store.free_shipping_threshold_cents')
                ? 0
                : (int) config('store.shipping_fee_cents');
        }

        return [
            'items' => $items,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'shipping_cents' => $shipping,
            'shipping_label' => $shippingLabel,
            'total_cents' => max(0, $subtotal - $discount + $shipping),
            'coupon' => $coupon,
        ];
    }

    public function couponCode(): ?string
    {
        return Session::get(self::COUPON_KEY);
    }

    /**
     * This ID is generated server-side and is invalidated whenever the cart or
     * coupon changes. It makes duplicate checkout submissions idempotent.
     */
    public function checkoutId(): string
    {
        $checkoutId = Session::get(self::CHECKOUT_ID_KEY);
        if (is_string($checkoutId) && Str::isUuid($checkoutId)) {
            return $checkoutId;
        }

        $checkoutId = (string) Str::uuid();
        Session::put(self::CHECKOUT_ID_KEY, $checkoutId);

        return $checkoutId;
    }

    private function forgetCheckout(): void
    {
        Session::forget(self::CHECKOUT_ID_KEY);
    }
}
