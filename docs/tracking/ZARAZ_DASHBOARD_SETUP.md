# Cloudflare Zaraz deployment checklist

This checklist covers the existing Cloudflare Zaraz path only. The direct provider adapters are separate and documented in **tracking.md** and **CREDENTIAL_SETUP.md**. Configuring Zaraz does not configure the direct adapters, and enabling both for the same destination can duplicate events.

These settings cannot be applied from this local Laravel source tree.

1. Put the production domain behind Cloudflare and confirm the Zaraz script is auto-injected exactly once. Do not also add a second manual Zaraz or direct Meta Pixel script.
2. In **Zaraz → Settings**, enable **E-commerce tracking**.
3. Add/configure the Facebook Pixel tool with the approved Pixel/Dataset ID. Under its advanced settings, enable **E-commerce tracking**.
4. Confirm the dashboard maps the e-commerce actions to ViewContent, AddToCart, InitiateCheckout and Purchase, and that SKU is the catalog identifier.
5. Configure consent in Cloudflare/CMP before enabling a consent-required region. If a CMP is used, set `window.LiveBajarConsent.analytics` to `true`/`false` or the documented `livebajar_tracking_consent` cookie.
6. In a non-production test domain, turn on Zaraz debug/monitoring and use Meta Events Manager **Test Events**. Verify each core funnel event's ID, BDT value, currency, content IDs and quantity.
7. This Laravel module now has a separate queued Meta CAPI adapter. Decide which integration should own each destination before enabling it. If using Zaraz HTTP Events API as well, configure its endpoint deliberately and verify event name/ID deduplication; do not assume Zaraz and the direct adapter share configuration or event IDs.
8. Confirm production CSP, CDN cache rules and privacy policy do not block `/cdn-cgi/zaraz/*` or the chosen Cloudflare tracking requests.

Cloudflare documents that E-commerce Tracking must be enabled globally and for the destination tool, and that `zaraz.ecommerce()` events are then forwarded by the configured tool. See the official [Zaraz E-commerce Web API documentation](https://developers.cloudflare.com/zaraz/web-api/ecommerce/) and [HTTP Events API documentation](https://developers.cloudflare.com/zaraz/http-events-api/).
