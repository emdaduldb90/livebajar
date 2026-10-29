# Changelog

## 2026-10-28 — Tracking Intelligence module

- Added the protected `/admin/tracking` page and the existing admin navigation entry.
- Added encrypted settings, consent-aware first-party events, local analytics, export, queued provider delivery, OpenAI aggregate reports and scheduled retention cleanup.
- Added a storefront allow/reject privacy choice, consent-gated SDK initialization, and admin/bot/non-production suppression.
- Prevented connector secret blocks from being flashed back into sessions after invalid configuration submissions.
- Added readiness checks for manual delivery retries and moved the database queue to a dedicated `tracking_jobs` table.
- Added row-locked delivery claims with a recovery lease; 429/5xx responses retry with capped backoff while permanent provider errors are recorded without repeated requests.
- Moved the tracking panel and consent styles into same-origin CSS assets so the application's Content Security Policy can apply them; replaced the inline funnel bar width with a styled progress element.
- Added HTTP-fake coverage for Meta, TikTok and GA4 delivery payloads plus AI output validation and secret-redaction checks.
- Preserved the pre-existing Cloudflare Zaraz tracking integration and documented duplicate-destination risks.
- Added implementation, privacy, credential, hosting, test and rollback documentation.
