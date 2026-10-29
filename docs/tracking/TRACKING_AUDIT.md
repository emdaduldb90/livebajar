# Tracking audit baseline

Date: 2026-10-15

This is a historical pre-implementation baseline. Later tracking-module work added first-party collection and direct provider adapters; use **tracking.md** for current behavior.

## Application observed

- Laravel 10, PHP 8.1+, server-rendered Blade storefront and MySQL.
- Cart state is a Laravel session; checkout creates `orders` and `order_items` in one database transaction.
- The Facebook Commerce feed uses the product `sku` as `g:id`. Tracking uses the same SKU for every `product_id` / `content_id` equivalent.
- The site has no customer registration, login, wishlist, reviews, variants, comparison, payment gateway callback, return workflow, or customer-support CTA feature.

## Source-code baseline before this work

- No `window.zaraz`, `fbq`, Meta Pixel, Meta CAPI, GA4, GTM, or `dataLayer` call was present in `app/`, `resources/`, `routes/`, `public/`, or `config/`.
- No browser customer matching fields, `_fbp`, `_fbc`, hashing routine, or CAPI access token was present.
- Cloudflare dashboard configuration is external to this source tree and was not inspected or changed.

## Protection decisions

- No direct Pixel or CAPI stream was added. This prevents an unverified parallel Meta conversion stream.
- The adapter invokes `zaraz.ecommerce()` only for Cloudflare's 19 supported e-commerce event names, and `zaraz.track()` only for governed custom events.
- `email`, phone, address, IP, user-agent, `_fbp`, `_fbc`, tokens, and customer identifiers are excluded from browser payloads and debug output.
- Production tracking defaults on; local/test tracking defaults off unless explicitly enabled with `TRACKING_ALLOW_NON_PRODUCTION=true`.
- Existing consent integrations are honoured through `window.LiveBajarConsent.analytics` and the `livebajar_tracking_consent` cookie. A consent-management platform still needs to be configured before a consent-required launch.

## External dependencies not verified in code

- Cloudflare proxied domain and Zaraz auto-injection.
- Zaraz E-commerce Tracking and the Meta/Facebook Pixel tool settings.
- Meta pixel/dataset ID, catalog assignment, consent policy, browser/server event mapping, and Test Events delivery.
- A configured Zaraz HTTP Events API endpoint for authoritative server-side status events.

See `ZARAZ_DASHBOARD_SETUP.md` for the required deployment checks.
