# Tracking & AI Intelligence

পূর্ণ বাংলায় menu-by-menu tutorial, setup sequence, event mapping, Meta match quality, analytics definitions, delivery status, troubleshooting এবং অসম্পূর্ণ feature-এর তালিকা: [tracking.md](tracking.md).

The protected panel is available at `/admin/tracking` through the existing Live Bajar admin session. It uses the existing `web` session guard and `is_admin` authorization; it does not create another login.

## What is implemented

- An additive, first-party event store and responsive full-page admin panel.
- Consent-aware browser instrumentation for PageView, ViewContent, AddToCart, InitiateCheckout, Purchase, Search, LinkClick, PhoneClick and EmailClick.
- A verified server Purchase event from a committed order, with the same order event ID used by the success-page browser event.
- Aggregated local order/event reporting, funnel counts, campaign source groups, link reports, event inspector and safe CSV export.
- Encrypted provider configuration, disabled-by-default provider routing, a queued server delivery pipeline for Meta CAPI, TikTok Events API 2.0 and GA4 Measurement Protocol, plus an asynchronous structured OpenAI report job.
- Retention cleanup using Laravel's scheduler.

The project already has a Cloudflare Zaraz adapter. It remains intact and is separate from these direct provider adapters. Check the Cloudflare dashboard before enabling a direct pixel destination to avoid duplicate platform events.

## Safe first deployment

1. Take a database backup and deploy the code.
2. Run `php artisan migrate --force` after reviewing the additive migration in `ROLLBACK.md`.
3. Set `QUEUE_CONNECTION=database` in the deployment environment and start a Laravel queue worker for the `tracking,default` queues. The database queue connection uses the module's dedicated `tracking_jobs` table.
4. Configure Laravel's scheduler to run every minute. The daily tracking cleanup will then follow the application's timezone.
5. Open `/admin/tracking` with the existing admin account. Collection, external destinations and AI are off by default. Configure consent and test credentials before enabling them.

## Honest delivery states

Local `observed` events are database records. A `queued` delivery has not left the application; `processing` is protected by a row lock and a five-minute recovery lease. `accepted` means the provider returned the expected HTTP/API acceptance response. It does not establish event validation, ad attribution, or appearance in provider reports. Check provider diagnostics after configuration.

TikTok Events API 2.0 server delivery is deliberately blocked while Test Mode is on because the current official Web schema did not verify a test payload field; the legacy v1.0 test guide is not applied to the newer endpoint.

See `FINAL_IMPLEMENTATION_STATUS.md` for supported and incomplete areas, `CREDENTIAL_SETUP.md` for provider setup, and `TEST_PLAN.md` for verification.
