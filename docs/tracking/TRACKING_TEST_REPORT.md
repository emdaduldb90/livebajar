# Tracking test report

## Current automated verification (2026-10-28)

- `php artisan test`: **25 passed, 166 assertions** on the PHPUnit test database.
- Coverage includes the 63-event registry, checkout and cart hooks, admin access, encrypted credentials, session secret redaction, consent preference rendering, sanitized Search capture, idempotent Purchase events, local browser/server dedup and readiness-checked retries.
- HTTP fakes cover Meta CAPI Test Events, TikTok Test Mode blocking, GA4 Measurement Protocol, transient vs permanent delivery failure handling, aggregate-only OpenAI input, structured response validation and sanitized failure handling.
- `php -l` passed on changed tracking PHP files; `node --check` passed for all three tracking JavaScript assets; `php artisan route:list --path=tracking` lists seven routes.
- Provider APIs were not called with owner credentials. Browser behavior still needs staging QA.

## Historical baseline coverage (2026-10-15)

- Run on 2026-10-15: `php artisan test` — **7 passed, 80 assertions**.
- `EventRegistryTest`: validates all 54 governed names and verifies that the browser adapter contains each exact name.
- `EcommerceTrackingServiceTest`: validates SKU/catalog mapping, BDT currency, selling price, quantity, position and computed line value.
- Existing Laravel feature smoke test remains in the suite.

## Manual verification required after deployment

| Check | Local code status | External confirmation |
|---|---|---|
| Product view emits one valid `Product Viewed` event | Implemented | Zaraz/Meta Test Events required |
| Successful cart add emits `Product Added` | Implemented | Zaraz/Meta Test Events required |
| Checkout uses a stable server checkout ID | Implemented | Browser/devtools verification required |
| Successful persisted order emits one `Order Completed` browser event per stored purchase ID | Implemented | Zaraz/Meta Test Events required |
| Pixel/CAPI deduplication | Shared Purchase event IDs are implemented for Meta/TikTok | Requires staging diagnostics with provider credentials |
| Customer match quality score | Optional consented checkout email/phone SHA-256 matching is server-only; no browser identity matching or local score | Requires Meta Events Manager diagnostics |

No live Cloudflare or Meta delivery is asserted by this report.
