# Event schema and definitions

## Stored row

`tracking_events` stores `event_name`, `event_id`, `event_type`, `event_source`, consent state, a path without query/fragment, referrer host, filtered UTM source/medium/campaign, optional link type/host, an allowlisted property object, encrypted GA client ID, GA session ID, encrypted optional matching hashes, and server-observed `occurred_at`.

The unique key `(event_name, event_id, event_source)` prevents a browser refresh or repeated server hook from inserting the same event twice on that source. A corresponding browser and server event may both be stored with the same `event_name` and `event_id` so platform deduplication can be measured; local dashboards count unique `(event_name,event_id)` pairs separately from observed rows.

## Required event names

`PageView`, `ViewContent`, `AddToCart`, `InitiateCheckout`, `Purchase`, `LinkClick`, `PhoneClick`, `EmailClick`, and `Search`.

## Platform mapping

- Meta: standard events keep their names; interaction events are sent as custom events. Server copies carry the same `event_id` as browser copies where the event exists in both channels.
- TikTok: Purchase maps to `CompletePayment`; other core names are passed as standard/custom web events with `event_id`.
- GA4: `PageView` → `page_view`, `ViewContent` → `view_item`, `AddToCart` → `add_to_cart`, `InitiateCheckout` → `begin_checkout`, `Purchase` → `purchase`, `Search` → `search`; link/contact actions use custom event names. Purchase includes the stored order number as `transaction_id`.
- Google Ads browser conversion is sent only for Purchase and requires both conversion ID and conversion label. Server upload is not implemented.

## Definitions

- **Observed**: local event row accepted after allowlist and consent checks.
- **Unique / deduplicated**: distinct event name and ID across browser/server rows for local reporting.
- **Queued**: a server delivery job has been stored for an asynchronous worker.
- **Accepted**: provider HTTP/API response met its success condition. Google Analytics Measurement Protocol can return success for malformed events; verify its validation/debug tooling separately.
- **Validated**: not asserted by this module. Confirm in the provider's diagnostics.
- **Attributed**: not asserted by this module. Attribution is determined by the destination platform and its reporting rules.

Order revenue is local gross order value from non-cancelled orders. It is not paid revenue, net of refunds, contribution margin, ad-attributed revenue or ROI.
