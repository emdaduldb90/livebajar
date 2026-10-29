<?php

namespace App\Services\Tracking;

/**
 * One canonical registry for every event requested for Live Bajar. A registry
 * entry means the event name is governed and documented; it does not imply a
 * product feature that does not yet exist is being falsely tracked.
 */
final class EventRegistry
{
    /** @var array<int, string> */
    private const ECOMMERCE = [
        'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase', 'Search',
        'Product List Viewed', 'Products Searched', 'Product Clicked', 'Product Viewed',
        'Product Added', 'Product Added to Wishlist', 'Product Removed', 'Cart Viewed',
        'Checkout Started', 'Checkout Step Viewed', 'Checkout Step Completed',
        'Shipping Info Entered', 'Payment Info Entered', 'Order Completed', 'Order Updated',
        'Order Refunded', 'Order Cancelled', 'Viewed Promotion', 'Clicked Promotion',
    ];

    /** @var array<int, string> */
    private const CUSTOM = [
        'PageView', 'LinkClick', 'PhoneClick', 'EmailClick',
        'CategoryViewed', 'ProductFiltered', 'ProductSorted', 'ProductCompared',
        'ProductImageViewed', 'ProductVideoPlayed', 'ProductVariantSelected',
        'ProductSpecificationViewed', 'ProductReviewViewed', 'ProductReviewSubmitted',
        'StockAlertRequested', 'PriceDropAlertRequested', 'CouponApplied', 'CouponFailed',
        'ShippingCostCalculated', 'BuyNowClicked', 'CheckoutError', 'PaymentFailed',
        'PaymentSuccessful', 'CODSelected', 'EMISelected', 'OrderConfirmed', 'OrderShipped',
        'OrderDelivered', 'ReturnRequested', 'RepeatPurchase', 'CustomerRegistered',
        'CustomerLoggedIn', 'WhatsAppClicked', 'PhoneCallClicked', 'MessengerClicked',
        'WarrantyInfoViewed', 'StoreLocationViewed', 'ProductShared', 'LeadSubmitted',
    ];

    /** @return array<string, 'ecommerce'|'custom'> */
    public static function all(): array
    {
        return array_merge(
            array_fill_keys(self::ECOMMERCE, 'ecommerce'),
            array_fill_keys(self::CUSTOM, 'custom'),
        );
    }

    public static function typeFor(string $name): ?string
    {
        return self::all()[$name] ?? null;
    }

    public static function isEcommerce(string $name): bool
    {
        return self::typeFor($name) === 'ecommerce';
    }

    public static function isCustom(string $name): bool
    {
        return self::typeFor($name) === 'custom';
    }
}
