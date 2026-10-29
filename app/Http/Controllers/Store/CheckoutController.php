<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Tracking\EcommerceTrackingService;
use App\Services\Tracking\TrackingEventRecorder;
use App\Services\Tracking\TrackingEventStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(CartService $cart, EcommerceTrackingService $tracking): View|RedirectResponse
    {
        $shippingOptions = $cart->shippingZones();
        $defaultZone = (string) config('store.default_shipping_zone', 'dhaka');
        $selectedShippingZone = old('shipping_zone', $defaultZone);

        if (! is_string($selectedShippingZone) || ! array_key_exists($selectedShippingZone, $shippingOptions)) {
            $selectedShippingZone = $defaultZone;
        }

        $summary = $cart->summary($selectedShippingZone);
        if ($summary['items']->isEmpty()) {
            return to_route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $checkoutId = $cart->checkoutId();
        $events = [
            [
                'type' => 'ecommerce',
                'name' => 'Checkout Started',
                'properties' => $tracking->cart($summary, $checkoutId),
                'event_id' => 'checkout_started_'.$checkoutId,
                'persistent' => true,
            ],
            [
                'type' => 'ecommerce',
                'name' => 'Checkout Step Viewed',
                'properties' => ['checkout_id' => $checkoutId, 'step' => 1],
                'event_id' => 'checkout_step_viewed_'.$checkoutId.'_1',
                'persistent' => true,
            ],
        ];

        if (session('errors')?->any()) {
            $events[] = [
                'type' => 'custom',
                'name' => 'CheckoutError',
                'properties' => ['error_type' => 'validation_or_processing', 'checkout_id' => $checkoutId],
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'persistent' => false,
            ];
        }

        $trackingContext = ['events' => $events, 'checkout_id' => $checkoutId];

        return view('checkout.create', compact('summary', 'shippingOptions', 'selectedShippingZone', 'trackingContext'));
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout, CartService $cart, TrackingEventRecorder $trackingEvents): RedirectResponse
    {
        $order = $checkout->place($request->validated(), $cart, $cart->checkoutId());
        $trackingEvents->recordOrderPurchase($order, $request);
        $cart->clear();
        Session::put('store.last_order_number', $order->order_number);

        return to_route('checkout.success', ['orderNumber' => $order->order_number]);
    }

    public function success(string $orderNumber, EcommerceTrackingService $tracking, TrackingEventStore $events): View
    {
        abort_unless(hash_equals((string) Session::get('store.last_order_number'), $orderNumber), 403);

        $order = Order::query()->with(['items', 'coupon'])->where('order_number', $orderNumber)->firstOrFail();
        $orderPayload = $tracking->order($order);
        $purchaseEventId = (string) $order->tracking_purchase_event_id;

        $events->flashEcommerce('Order Completed', $orderPayload, $purchaseEventId, true);
        $events->flashEcommerce('Checkout Step Completed', [
            'checkout_id' => $order->tracking_checkout_id,
            'step' => 1,
        ], 'checkout_step_completed_'.$order->tracking_checkout_id.'_1', true);
        $events->flashEcommerce('Shipping Info Entered', [
            'checkout_id' => $order->tracking_checkout_id,
            'shipping' => $orderPayload['shipping'],
            'currency' => $orderPayload['currency'],
        ], 'shipping_info_'.$order->tracking_checkout_id, true);
        $events->flashEcommerce('Payment Info Entered', [
            'checkout_id' => $order->tracking_checkout_id,
            'payment_type' => $order->payment_method,
        ], 'payment_info_'.$order->tracking_checkout_id, true);
        $events->flashCustom('OrderConfirmed', [
            'order_id' => $order->order_number,
            'checkout_id' => $order->tracking_checkout_id,
            'value' => $orderPayload['total'],
            'currency' => $orderPayload['currency'],
        ], 'order_confirmed_'.$order->tracking_checkout_id, true);

        return view('checkout.success', compact('order'));
    }
}
