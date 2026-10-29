<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\ApplyCouponRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Tracking\EcommerceTrackingService;
use App\Services\Tracking\TrackingEventStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cart, EcommerceTrackingService $tracking): View
    {
        $summary = $cart->summary();
        $trackingContext = $summary['items']->isEmpty() ? ['events' => []] : ['events' => [[
            'type' => 'ecommerce',
            'name' => 'Cart Viewed',
            'properties' => $tracking->cart($summary, $cart->checkoutId()),
            'event_id' => 'cart_view_'.$cart->checkoutId(),
            'persistent' => true,
        ]]];

        return view('cart.index', compact('summary', 'trackingContext'));
    }

    public function store(AddToCartRequest $request, Product $product, CartService $cart, EcommerceTrackingService $tracking, TrackingEventStore $events): RedirectResponse
    {
        $addedQuantity = $cart->add($product, $request->integer('quantity'));
        if ($addedQuantity > 0) {
            $events->flashEcommerce('Product Added', $tracking->product($product, $addedQuantity));
        }

        if ($request->boolean('checkout')) {
            return to_route('cart.index');
        }

        return back()->with('success', 'Added "'.$product->name.'" to your cart.');
    }

    public function update(UpdateCartRequest $request, Product $product, CartService $cart): RedirectResponse
    {
        $cart->setQuantity($product, $request->integer('quantity'));

        return back()->with('success', 'Your cart was updated.');
    }

    public function destroy(Product $product, CartService $cart, EcommerceTrackingService $tracking, TrackingEventStore $events): RedirectResponse
    {
        $removedQuantity = $cart->remove($product);
        if ($removedQuantity > 0) {
            $events->flashEcommerce('Product Removed', $tracking->product($product, $removedQuantity));
        }

        return back()->with('success', 'Item removed from your cart.');
    }

    public function applyCoupon(ApplyCouponRequest $request, CartService $cart, TrackingEventStore $events): RedirectResponse
    {
        try {
            $cart->applyCoupon($request->string('code')->toString());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $events->flashCustom('CouponFailed', ['reason' => 'invalid_or_unavailable']);

            return back()->withErrors($exception->errors())->withInput();
        }

        $events->flashCustom('CouponApplied', [
            'coupon' => $cart->couponCode(),
            'currency' => config('tracking.currency', 'BDT'),
        ]);

        return back()->with('success', 'Coupon applied successfully.');
    }

    public function removeCoupon(CartService $cart): RedirectResponse
    {
        $cart->removeCoupon();

        return back()->with('success', 'Coupon removed.');
    }
}
