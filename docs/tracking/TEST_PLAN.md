# Tracking test plan

Run after the additive migration on the isolated PHPUnit in-memory SQLite database:

```powershell
php artisan test
```

Expected coverage includes existing checkout behavior, registry parity, tracking config secret encryption, unauthorized panel denial, consent-aware first-party ingest and sanitized search, server purchase idempotency, local unique analytics and provider delivery behavior using HTTP fakes. Provider dashboards still require real test credentials to verify externally.

## Manual staging checks

1. Apply migrations to staging only; confirm panel redirects guests to existing admin login and rejects a non-admin.
2. Keep Test Mode on, use the storefront privacy-choice banner to allow optional tracking, enable collection, and inspect that PageView, product view, successful AddToCart, checkout, Search, link, phone and email events are captured once. Reject optional tracking and confirm that no browser event is collected.
3. Submit the same order request twice and refresh its success page; verify one local server Purchase and one browser Purchase row share the stored order event ID. Confirm order is pending/COD and is reported as placed, not paid.
4. Enable only a provider's test server route and process the `tracking` queue. Check both local status and the provider's Test Events/DebugView diagnostics. Verify that a successful HTTP status is not labelled as attributed.
5. Verify matching browser/server event IDs in Meta and TikTok diagnostics before enabling production dual routing.
6. For GA4, validate the payload with Google's `/debug/mp/collect`, then verify DebugView/reporting separately.
7. Test report generation with a low daily cap; confirm only aggregate data leaves the application and the worker records token usage.
8. Test scheduler/worker availability on the actual shared host before enabling any provider or AI destination.

Never replay production orders or send synthetic purchases to a live advertising dataset as part of validation.
