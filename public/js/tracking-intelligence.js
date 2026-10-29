/* First-party collection and optional browser adapters for the protected tracking module. */
(() => {
    'use strict';

    const CORE = new Set(['PageView', 'ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase', 'LinkClick', 'PhoneClick', 'EmailClick', 'Search']);
    const node = document.getElementById('livebajar-tracking-config');
    const contextNode = document.getElementById('livebajar-tracking-context');
    const config = decode(node?.dataset.config) || {};
    const context = decode(contextNode?.dataset.context) || { events: [] };
    const fired = new Set();
    let loadedMeta = false;
    let loadedTikTok = false;
    let loadedGtag = false;
    let initialized = false;

    function decode(value) {
        if (!value) return null;
        try {
            const bytes = Uint8Array.from(window.atob(value), (character) => character.charCodeAt(0));
            return JSON.parse(new TextDecoder().decode(bytes));
        } catch (_) {
            return null;
        }
    }

    function cookie(name) {
        const prefix = `${name}=`;
        const item = document.cookie.split('; ').find((part) => part.startsWith(prefix));
        return item ? decodeURIComponent(item.slice(prefix.length)) : null;
    }

    function consentState() {
        if (window.LiveBajarConsent?.analytics === false || cookie('livebajar_tracking_consent') === 'denied') return 'denied';
        if (window.LiveBajarConsent?.analytics === true || cookie('livebajar_tracking_consent') === 'granted') return 'granted';
        return 'unknown';
    }

    function allowed() {
        if (config.skip_tracking) return false;
        const consent = consentState();
        if (consent === 'denied') return false;
        if (config.require_consent && consent !== 'granted') return false;
        return Boolean(config.collection_enabled || (!config.test_mode && (config.meta?.browser_enabled || config.tiktok?.browser_enabled || config.ga4?.browser_enabled || config.google_ads?.browser_enabled)));
    }

    function eventId() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return `lb_${Date.now()}_${Math.random().toString(36).slice(2, 12)}`;
    }

    function personal(value) {
        return /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i.test(value) || /(?:\+?\d[\d .()\-]{7,}\d)/.test(value);
    }

    function cleanProperties(properties = {}) {
        const allowed = new Set(['product_id', 'sku', 'name', 'category', 'brand', 'variant', 'coupon', 'order_id', 'checkout_id', 'affiliation', 'creative', 'payment_type', 'value', 'revenue', 'total', 'shipping', 'tax', 'discount', 'price', 'currency', 'quantity', 'step', 'position', 'products', 'link_type', 'destination_host', 'search_length', 'result_count']);
        const result = {};
        Object.entries(properties || {}).forEach(([key, value]) => {
            if (!allowed.has(key)) return;
            if (key === 'products' && Array.isArray(value)) {
                result.products = value.slice(0, 50).map((product) => cleanProperties(product)).filter((product) => product.product_id || product.sku);
            } else if (typeof value === 'string' && value.length <= 160 && !personal(value)) {
                result[key] = value;
            } else if (typeof value === 'number' && Number.isFinite(value) && value >= 0) {
                result[key] = value;
            }
        });
        if (typeof result.currency === 'string' && /^[a-z]{3}$/i.test(result.currency)) result.currency = result.currency.toUpperCase();
        return result;
    }

    function sourceTags() {
        const params = new URLSearchParams(window.location.search);
        const clean = (value) => typeof value === 'string' && value.length <= 120 && !personal(value) && /^[\p{L}\p{N} ._:-]+$/u.test(value) ? value.trim() : null;
        return {
            campaign_source: clean(params.get('utm_source')),
            campaign_medium: clean(params.get('utm_medium')),
            campaign_name: clean(params.get('utm_campaign')),
        };
    }

    function gaIdentifiers() {
        if (consentState() !== 'granted') return {};
        const gaCookie = cookie('_ga');
        const match = typeof gaCookie === 'string' ? gaCookie.match(/^GA\d+\.\d+\.(\d+\.\d+)$/) : null;
        const result = match ? { ga_client_id: match[1] } : {};
        const measurementId = config.ga4?.measurement_id || '';
        const cookieName = measurementId.startsWith('G-') ? `_ga_${measurementId.slice(2)}` : '';
        const sessionCookie = cookie(cookieName);
        const sessionMatch = typeof sessionCookie === 'string' ? sessionCookie.match(/^GS\d+\.\d+\.(\d+)\./) : null;
        if (sessionMatch) result.ga_session_id = sessionMatch[1];
        return result;
    }

    function pagePath() {
        return `${window.location.pathname || '/'}`.slice(0, 512);
    }

    function safeReferrer() {
        try {
            const url = new URL(document.referrer);
            return `${url.origin}${url.pathname}`.slice(0, 800);
        } catch (_) {
            return '';
        }
    }

    function sendLocal(name, id, properties) {
        if (!config.collection_enabled) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrf) return;
        const payload = {
            event_id: id,
            event_name: name,
            properties,
            page_path: pagePath(),
            referrer: safeReferrer(),
            consent_state: consentState(),
            ...sourceTags(),
            ...gaIdentifiers(),
        };
        fetch('/tracking/events', {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(payload),
        }).catch(() => {});
    }

    function ensureMeta() {
        const id = config.meta?.pixel_id;
        if (loadedMeta || config.test_mode || !config.meta?.browser_enabled || !id) return;
        loadedMeta = true;
        /* Meta's official queue bootstrap; no server token is present in this asset. */
        if (!window.fbq) {
            const fbq = window.fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
            if (!window._fbq) window._fbq = fbq;
            fbq.push = fbq; fbq.loaded = true; fbq.version = '2.0'; fbq.queue = [];
            const script = document.createElement('script'); script.async = true; script.src = 'https://connect.facebook.net/en_US/fbevents.js';
            document.head.appendChild(script);
        }
        window.fbq('init', id);
    }

    function ensureTikTok() {
        const id = config.tiktok?.pixel_id;
        if (loadedTikTok || config.test_mode || !config.tiktok?.browser_enabled || !id) return;
        loadedTikTok = true;
        if (!window.ttq) {
            const methods = ['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie','holdConsent','revokeConsent','grantConsent'];
            const ttq = window.ttq = [];
            ttq.methods = methods;
            ttq.setAndDefer = (target, method) => { target[method] = (...args) => target.push([method, ...args]); };
            methods.forEach((method) => ttq.setAndDefer(ttq, method));
            ttq.load = (pixelId) => {
                const script = document.createElement('script');
                script.async = true;
                script.src = `https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=${encodeURIComponent(pixelId)}&lib=ttq`;
                document.head.appendChild(script);
            };
        }
        window.ttq.load(id);
    }

    function ensureGtag() {
        if (loadedGtag || config.test_mode || !(config.ga4?.browser_enabled || config.google_ads?.browser_enabled)) return;
        loadedGtag = true;
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        if (config.ga4?.browser_enabled && config.ga4?.measurement_id) window.gtag('config', config.ga4.measurement_id, {send_page_view: false});
        if (config.google_ads?.browser_enabled && config.google_ads?.conversion_id) window.gtag('config', config.google_ads.conversion_id);
        const script = document.createElement('script'); script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(config.ga4?.browser_enabled ? config.ga4.measurement_id : config.google_ads.conversion_id)}`;
        document.head.appendChild(script);
    }

    function routeEnabled(name, provider) {
        return Boolean(config.routes?.[name]?.[`${provider}_browser`]);
    }

    function ecommercePayload(properties) {
        const products = Array.isArray(properties.products) ? properties.products : (properties.product_id || properties.sku ? [properties] : []);
        const items = products.map((item) => ({
            product_id: item.product_id || item.sku,
            sku: item.sku || item.product_id,
            name: item.name,
            category: item.category,
            brand: item.brand,
            price: Number(item.price || 0),
            quantity: Number(item.quantity || 1),
        })).filter((item) => item.product_id);
        return {products: items, value: properties.value ?? properties.total ?? properties.revenue, currency: properties.currency, order_id: properties.order_id, checkout_id: properties.checkout_id};
    }

    function fireMeta(name, id, properties) {
        if (config.test_mode || !config.meta?.browser_enabled || !routeEnabled(name, 'meta')) return;
        ensureMeta();
        if (typeof window.fbq !== 'function') return;
        const values = ecommercePayload(properties);
        const items = values.products.map((item) => ({id: item.product_id, quantity: item.quantity, item_price: item.price}));
        const custom = Object.fromEntries(Object.entries({value: values.value, currency: values.currency, content_ids: values.products.map((item) => item.product_id), content_type: 'product', contents: items, order_id: values.order_id}).filter(([, value]) => value !== undefined && value !== null && value !== ''));
        const standard = ['PageView','ViewContent','AddToCart','InitiateCheckout','Purchase','Search'].includes(name);
        window.fbq(standard ? 'track' : 'trackCustom', name, custom, {eventID: id});
    }

    function fireTikTok(name, id, properties) {
        if (config.test_mode || !config.tiktok?.browser_enabled || !routeEnabled(name, 'tiktok')) return;
        ensureTikTok();
        if (!window.ttq || typeof window.ttq.track !== 'function') return;
        const values = ecommercePayload(properties);
        const contents = values.products.map((item) => ({content_id: item.product_id, content_name: item.name, content_category: item.category, brand: item.brand, price: item.price, quantity: item.quantity}));
        const eventName = ({Purchase:'CompletePayment',PhoneClick:'ClickButton',EmailClick:'ClickButton',LinkClick:'ClickButton'})[name] || name;
        const payload = Object.fromEntries(Object.entries({value: values.value, currency: values.currency, content_type: 'product', contents}).filter(([, value]) => value !== undefined && value !== null && value !== ''));
        window.ttq.track(eventName, payload, {event_id: id});
    }

    function fireGtag(name, id, properties) {
        const values = ecommercePayload(properties);
        const items = values.products.map((item) => ({item_id: item.product_id, item_name: item.name, item_brand: item.brand, item_category: item.category, price: item.price, quantity: item.quantity}));
        const eventName = ({PageView:'page_view',ViewContent:'view_item',AddToCart:'add_to_cart',InitiateCheckout:'begin_checkout',Purchase:'purchase',LinkClick:'click',PhoneClick:'phone_click',EmailClick:'email_click',Search:'search'})[name];
        if (!config.test_mode && config.ga4?.browser_enabled && routeEnabled(name, 'ga4')) {
            ensureGtag();
            if (typeof window.gtag === 'function') window.gtag('event', eventName, {event_id: id, transaction_id: name === 'Purchase' ? values.order_id : undefined, value: values.value, currency: values.currency, items, engagement_time_msec: 1, link_type: properties.link_type});
        }
        if (!config.test_mode && name === 'Purchase' && config.google_ads?.browser_enabled && routeEnabled(name, 'google_ads') && config.google_ads.conversion_id && config.google_ads.conversion_label) {
            ensureGtag();
            window.gtag('event', 'conversion', {send_to: `${config.google_ads.conversion_id}/${config.google_ads.conversion_label}`, value: values.value, currency: values.currency, transaction_id: values.order_id || id});
        }
    }

    function track(name, properties = {}, id = eventId()) {
        if (!CORE.has(name) || fired.has(`${name}:${id}`) || !allowed()) return false;
        fired.add(`${name}:${id}`);
        const clean = cleanProperties(properties);
        sendLocal(name, id, clean);
        fireMeta(name, id, clean);
        fireTikTok(name, id, clean);
        fireGtag(name, id, clean);
        return true;
    }

    function mapContextEvent(event) {
        const mapped = ({'Product Viewed':'ViewContent','Product Added':'AddToCart','Checkout Started':'InitiateCheckout','Order Completed':'Purchase','Products Searched':'Search'})[event.name];
        if (!mapped) return;
        const send = () => track(mapped, event.properties || {}, typeof event.event_id === 'string' ? event.event_id : eventId());
        if (!event.visibility_target) return send();
        const target = document.getElementById(event.visibility_target);
        if (!target || !('IntersectionObserver' in window)) return send();
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) { observer.disconnect(); send(); }
        }, {threshold: 0.25});
        observer.observe(target);
    }

    function bindLinks() {
        document.addEventListener('click', (event) => {
            const anchor = event.target instanceof Element ? event.target.closest('a[href]') : null;
            if (!anchor || anchor.hasAttribute('data-tracking-ignore')) return;
            const href = anchor.getAttribute('href') || '';
            if (/^tel:/i.test(href)) return track('PhoneClick', {link_type: 'phone'});
            if (/^mailto:/i.test(href)) return track('EmailClick', {link_type: 'email'});
            try {
                const url = new URL(href, window.location.origin);
                if (/^\/(?:admin|login|logout|register|password|reset|account)(?:\/|$)/i.test(url.pathname)) return;
                track('LinkClick', {link_type: 'link', destination_host: url.hostname.slice(0, 255)});
            } catch (_) {}
        }, true);
    }

    function pageView() {
        track('PageView', {});
    }

    window.LiveBajarIntelligence = Object.freeze({track});
    window.SiteTracking = Object.freeze({track});

    function initialize() {
        if (initialized || !allowed()) return;
        initialized = true;
        ensureMeta(); ensureTikTok(); ensureGtag();
        pageView();
        (Array.isArray(context.events) ? context.events : []).forEach(mapContextEvent);
        bindLinks();

        let lastPath = `${window.location.pathname}${window.location.search}`;
        const pageTransition = () => {
            const nextPath = `${window.location.pathname}${window.location.search}`;
            if (nextPath !== lastPath) { lastPath = nextPath; pageView(); }
        };
        window.addEventListener('popstate', pageTransition);
        ['pushState', 'replaceState'].forEach((method) => {
            const original = history[method];
            if (typeof original !== 'function') return;
            history[method] = function (...args) {
                const result = original.apply(this, args);
                pageTransition();
                return result;
            };
        });
    }

    window.addEventListener('livebajar:consent-changed', initialize);
    initialize();
})();
