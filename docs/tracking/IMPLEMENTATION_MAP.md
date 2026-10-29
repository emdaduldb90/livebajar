# Implementation map

| Area | Code location | Status |
|---|---|---|
| Existing-session admin panel | `routes/web.php`, `TrackingController`, `resources/views/admin/tracking/index.blade.php` | Implemented; guarded by `auth` + `admin` |
| Encrypted config and public-safe config | `TrackingConfiguration`, `TrackingSettingsService` | Implemented; secrets excluded from HTML and backup |
| First-party browser capture | `public/js/tracking-intelligence.js`, `TrackingEventController` | Implemented for the nine required events; disabled by default |
| Server Purchase | `CheckoutController`, `TrackingEventRecorder` | Implemented after successful order persistence; idempotent by event name, event ID and source |
| Canonical local event persistence | `tracking_events` table and `TrackingEventRecorder` | Implemented with allowlisted fields and no raw URL query |
| Local funnel/commerce analytics | `TrackingAnalyticsService` | Implemented; order totals are based on existing order data |
| Provider server queue | `DeliverTrackingEvent`, `TrackingDeliveryService` | Implemented for Meta, TikTok Events API 2.0 and GA4 MP; only if explicitly enabled, consented, configured and asynchronous queue is active |
| Provider browser integrations | `tracking-intelligence.js` | Implemented for Meta Pixel, TikTok Pixel, GA4 gtag and Google Ads browser conversion; test mode suppresses these scripts |
| AI business plans | `GenerateTrackingReport` | Implemented as queued Responses API JSON schema job; disabled until configured |
| Retention | `PruneTrackingEvents`, `Console\Kernel` | Implemented; requires the Laravel scheduler |
| CSV and safe configuration backup | `TrackingController` | Implemented; exports omit encrypted secrets |
| Visual trigger builder | — | Not implemented; no arbitrary JavaScript/selector builder is exposed |
| Recommendation workflow / alert delivery | — | Not implemented; panel labels these as unavailable |
| Google Ads server uploads | — | Not implemented; direct upload requires OAuth, conversion action setup and a selected documented methodology |
