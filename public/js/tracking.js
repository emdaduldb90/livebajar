/*
 * Live Bajar's Zaraz adapter. It intentionally contains no advertising token,
 * customer identity, or direct Meta Pixel call. Cloudflare injects Zaraz for a
 * proxied production domain; this adapter merely emits validated first-party
 * business events when that API is available.
 */
(() => {
    'use strict';

    const REGISTRY = Object.freeze({
        'Product List Viewed': 'ecommerce',
        PageView: 'custom',
        ViewContent: 'ecommerce',
        AddToCart: 'ecommerce',
        InitiateCheckout: 'ecommerce',
        Purchase: 'ecommerce',
        LinkClick: 'custom',
        PhoneClick: 'custom',
        EmailClick: 'custom',
        Search: 'ecommerce',
        'Products Searched': 'ecommerce',
        'Product Clicked': 'ecommerce',
        'Product Viewed': 'ecommerce',
        'Product Added': 'ecommerce',
        'Product Added to Wishlist': 'ecommerce',
        'Product Removed': 'ecommerce',
        'Cart Viewed': 'ecommerce',
        'Checkout Started': 'ecommerce',
        'Checkout Step Viewed': 'ecommerce',
        'Checkout Step Completed': 'ecommerce',
        'Shipping Info Entered': 'ecommerce',
        'Payment Info Entered': 'ecommerce',
        'Order Completed': 'ecommerce',
        'Order Updated': 'ecommerce',
        'Order Refunded': 'ecommerce',
        'Order Cancelled': 'ecommerce',
        'Viewed Promotion': 'ecommerce',
        'Clicked Promotion': 'ecommerce',
        CategoryViewed: 'custom',
        ProductFiltered: 'custom',
        ProductSorted: 'custom',
        ProductCompared: 'custom',
        ProductImageViewed: 'custom',
        ProductVideoPlayed: 'custom',
        ProductVariantSelected: 'custom',
        ProductSpecificationViewed: 'custom',
        ProductReviewViewed: 'custom',
        ProductReviewSubmitted: 'custom',
        StockAlertRequested: 'custom',
        PriceDropAlertRequested: 'custom',
        CouponApplied: 'custom',
        CouponFailed: 'custom',
        ShippingCostCalculated: 'custom',
        BuyNowClicked: 'custom',
        CheckoutError: 'custom',
        PaymentFailed: 'custom',
        PaymentSuccessful: 'custom',
        CODSelected: 'custom',
        EMISelected: 'custom',
        OrderConfirmed: 'custom',
        OrderShipped: 'custom',
        OrderDelivered: 'custom',
        ReturnRequested: 'custom',
        RepeatPurchase: 'custom',
        CustomerRegistered: 'custom',
        CustomerLoggedIn: 'custom',
        WhatsAppClicked: 'custom',
        PhoneCallClicked: 'custom',
        MessengerClicked: 'custom',
        WarrantyInfoViewed: 'custom',
        StoreLocationViewed: 'custom',
        ProductShared: 'custom',
        LeadSubmitted: 'custom',
    });

    const body = document.body;
    const config = Object.freeze({
        enabled: body?.dataset.trackingEnabled === '1',
        debug: body?.dataset.trackingDebug === '1',
        requireConsent: body?.dataset.trackingRequireConsent === '1',
        excluded: body?.dataset.trackingExcluded === '1',
    });
    const inMemoryKeys = new Set();
    const pageContext = readPageContext();
    const sensitiveKey = /(?:email|phone|address|customer|client_ip|user_agent|_fbp|_fbc|password|token)/i;

    function decode(value) {
        if (!value || typeof value !== 'string') {
            return null;
        }

        try {
            const bytes = Uint8Array.from(window.atob(value), (character) => character.charCodeAt(0));
            return JSON.parse(new TextDecoder().decode(bytes));
        } catch (_) {
            return null;
        }
    }

    function readPageContext() {
        const node = document.getElementById('livebajar-tracking-context');
        const context = decode(node?.dataset.context);

        return context && typeof context === 'object' ? context : { events: [] };
    }

    function hasConsent() {
        if (window.LiveBajarConsent?.analytics === false || document.cookie.includes('livebajar_tracking_consent=denied')) {
            return false;
        }

        return !config.requireConsent || window.LiveBajarConsent?.analytics === true || document.cookie.includes('livebajar_tracking_consent=granted');
    }

    function canTrack() {
        return config.enabled && !config.excluded && hasConsent();
    }

    function finiteNumber(value) {
        const parsed = Number(value);

        return Number.isFinite(parsed) ? parsed : null;
    }

    function nonNegativeNumber(value) {
        const parsed = finiteNumber(value);

        return parsed !== null && parsed >= 0 ? parsed : null;
    }

    function cleanProduct(product) {
        if (!product || typeof product !== 'object' || !product.product_id) {
            return null;
        }

        const clean = {};
        ['product_id', 'sku', 'category', 'name', 'brand', 'variant', 'coupon'].forEach((key) => {
            if (typeof product[key] === 'string' && product[key].length <= 500) {
                clean[key] = product[key];
            }
        });
        ['price', 'value'].forEach((key) => {
            const value = nonNegativeNumber(product[key]);
            if (value !== null) {
                clean[key] = value;
            }
        });
        ['quantity', 'position'].forEach((key) => {
            const value = finiteNumber(product[key]);
            if (value !== null && value >= 0) {
                clean[key] = Math.floor(value);
            }
        });
        if (typeof product.currency === 'string' && /^[A-Z]{3}$/i.test(product.currency)) {
            clean.currency = product.currency.toUpperCase();
        }

        return clean.product_id ? clean : null;
    }

    function cleanEcommerce(properties) {
        if (!properties || typeof properties !== 'object') {
            return null;
        }

        const clean = {};
        ['product_id', 'sku', 'category', 'name', 'brand', 'variant', 'coupon', 'checkout_id', 'order_id', 'affiliation', 'creative', 'payment_type', 'link_type', 'destination_host'].forEach((key) => {
            if (typeof properties[key] === 'string' && properties[key].length <= 500) {
                clean[key] = properties[key];
            }
        });
        ['price', 'quantity', 'position', 'total', 'revenue', 'shipping', 'tax', 'discount', 'value', 'step', 'search_length', 'result_count'].forEach((key) => {
            const value = nonNegativeNumber(properties[key]);
            if (value !== null) {
                clean[key] = key === 'quantity' || key === 'position' || key === 'step' ? Math.floor(value) : value;
            }
        });
        if (typeof properties.currency === 'string' && /^[A-Z]{3}$/i.test(properties.currency)) {
            clean.currency = properties.currency.toUpperCase();
        }
        if (Array.isArray(properties.products)) {
            clean.products = properties.products.map(cleanProduct).filter(Boolean).slice(0, 100);
        }

        return clean;
    }

    function cleanCustom(properties) {
        if (!properties || typeof properties !== 'object' || Array.isArray(properties)) {
            return {};
        }

        return Object.entries(properties).reduce((clean, [key, value]) => {
            if (sensitiveKey.test(key) || !/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/.test(key)) {
                return clean;
            }
            if (typeof value === 'string' && value.length <= 500) {
                clean[key] = value;
            } else if (typeof value === 'boolean') {
                clean[key] = value;
            } else if (Number.isFinite(value)) {
                clean[key] = value;
            }

            return clean;
        }, {});
    }

    function storageKey(eventId) {
        return `livebajar.tracking.fired.${eventId}`;
    }

    function wasPersistentlyFired(eventId) {
        if (!eventId) {
            return false;
        }

        try {
            return window.localStorage.getItem(storageKey(eventId)) === '1';
        } catch (_) {
            return false;
        }
    }

    function markPersistentlyFired(eventId) {
        if (!eventId) {
            return;
        }

        try {
            window.localStorage.setItem(storageKey(eventId), '1');
        } catch (_) {
            // A privacy mode may block storage. The in-memory guard still applies.
        }
    }

    function debug(detail) {
        if (!config.debug) {
            return;
        }

        window.LiveBajarTrackingDebug = window.LiveBajarTrackingDebug || [];
        window.LiveBajarTrackingDebug.push(detail);
        window.dispatchEvent(new CustomEvent('livebajar:tracking', { detail }));
        window.console?.debug?.('[Live Bajar tracking]', detail);
    }

    function invoke(method, name, properties, eventId, persistent, attempt = 0) {
        const zarazMethod = window.zaraz?.[method];
        if (typeof zarazMethod !== 'function') {
            if (attempt < 5) {
                window.setTimeout(() => invoke(method, name, properties, eventId, persistent, attempt + 1), 250 * (attempt + 1));
            } else {
                debug({ name, event_id: eventId, destination: 'zaraz', status: 'unavailable' });
            }
            return;
        }

        try {
            const result = zarazMethod.call(window.zaraz, name, properties);
            if (persistent) {
                markPersistentlyFired(eventId);
            }
            Promise.resolve(result).then(
                () => debug({ name, event_id: eventId, destination: 'zaraz', status: 'dispatched' }),
                () => debug({ name, event_id: eventId, destination: 'zaraz', status: 'failed' }),
            );
        } catch (_) {
            debug({ name, event_id: eventId, destination: 'zaraz', status: 'failed' });
        }
    }

    function dispatch(type, name, properties = {}, options = {}) {
        if (REGISTRY[name] !== type) {
            debug({ name, destination: 'none', status: 'invalid_registry_event' });
            return false;
        }
        if (!canTrack()) {
            debug({ name, destination: 'none', status: 'disabled_or_no_consent' });
            return false;
        }

        const eventId = options.eventId || `${name}_${Date.now()}_${Math.random().toString(36).slice(2)}`;
        if (inMemoryKeys.has(eventId) || (options.persistent && wasPersistentlyFired(eventId))) {
            debug({ name, event_id: eventId, destination: 'none', status: 'duplicate_prevented' });
            return false;
        }

        const clean = type === 'ecommerce' ? cleanEcommerce(properties) : cleanCustom(properties);
        if (clean === null) {
            debug({ name, event_id: eventId, destination: 'none', status: 'invalid_payload' });
            return false;
        }

        inMemoryKeys.add(eventId);
        invoke(type === 'ecommerce' ? 'ecommerce' : 'track', name, clean, eventId, options.persistent === true);

        return true;
    }

    function trackEcommerce(name, properties, options = {}) {
        return dispatch('ecommerce', name, properties, options);
    }

    function trackCustom(name, properties, options = {}) {
        return dispatch('custom', name, properties, options);
    }

    function dispatchContextEvent(event) {
        if (!event || typeof event !== 'object') {
            return;
        }
        const send = () => dispatch(event.type, event.name, event.properties, {
            eventId: event.event_id,
            persistent: event.persistent === true,
        });
        if (!event.visibility_target) {
            send();
            return;
        }

        const target = document.getElementById(event.visibility_target);
        if (!target || !('IntersectionObserver' in window)) {
            send();
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                send();
            }
        }, { threshold: 0.25 });
        observer.observe(target);
    }

    function bindElementEvents() {
        document.querySelectorAll('[data-tracking-ecommerce], [data-tracking-custom]').forEach((element) => {
            element.addEventListener('click', () => {
                const type = element.dataset.trackingEcommerce ? 'ecommerce' : 'custom';
                const name = element.dataset.trackingEcommerce || element.dataset.trackingCustom;
                const properties = decode(element.dataset.trackingProperties) || {};
                dispatch(type, name, properties);
            }, { passive: true });
        });

        document.querySelectorAll('[data-tracking-visible-event]').forEach((element) => {
            const send = () => trackEcommerce(element.dataset.trackingVisibleEvent, decode(element.dataset.trackingVisibleProperties) || {});
            if (!('IntersectionObserver' in window)) {
                send();
                return;
            }
            const observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    observer.disconnect();
                    send();
                }
            }, { threshold: 0.25 });
            observer.observe(element);
        });
    }

    function bindCheckoutEvents() {
        const checkoutId = typeof pageContext.checkout_id === 'string' ? pageContext.checkout_id : null;
        document.querySelectorAll('[data-shipping-option]').forEach((option) => {
            option.addEventListener('change', () => {
                if (!option.checked) {
                    return;
                }
                const feeCents = nonNegativeNumber(option.dataset.feeCents) || 0;
                trackCustom('ShippingCostCalculated', {
                    checkout_id: checkoutId || 'unknown',
                    shipping_zone: option.value,
                    shipping: feeCents / 100,
                    currency: 'BDT',
                });
            });
        });
        document.querySelectorAll('input[name="payment_method"]').forEach((option) => {
            option.addEventListener('change', () => {
                if (option.checked && option.value === 'cod') {
                    trackCustom('CODSelected', { checkout_id: checkoutId || 'unknown' });
                }
            });
        });
    }

    window.LiveBajarTracking = Object.freeze({
        registry: REGISTRY,
        trackEcommerce,
        trackCustom,
    });

    (Array.isArray(pageContext.events) ? pageContext.events : []).forEach(dispatchContextEvent);
    bindElementEvents();
    bindCheckoutEvents();
    window.addEventListener('livebajar:consent-changed', (event) => {
        if (event.detail?.consent === 'granted' || hasConsent()) {
            const latestContext = readPageContext();
            (Array.isArray(latestContext.events) ? latestContext.events : []).forEach(dispatchContextEvent);
        }
    });
})();
