# Deduplication and idempotency

1. `CartService` creates a server-side checkout UUID and invalidates it whenever a cart quantity, product, or coupon changes.
2. `orders.tracking_checkout_id` is unique. `CheckoutService` checks this key before placing an order and recovers the existing order after a duplicate-key race, preventing a repeated checkout POST from creating a second order.
3. Each new order stores a unique `tracking_purchase_event_id`. The confirmation-page browser adapter persists this event ID in local storage after it invokes Zaraz, so reloads do not re-send the same client purchase event.
4. Cart add/remove events are created only after the server mutation succeeds and are flashed once to the following server-rendered page.
5. The success page derives the Purchase payload from the committed order and order-item snapshots—not form fields or stale cart values.

The tracking module now has a queued direct server delivery adapter for Meta CAPI, TikTok Events API 2.0 and GA4 Measurement Protocol. Its backend Purchase event and authorized success-page browser copy use the same persisted order event ID. The Meta adapter sends that value as **event_id**; the browser adapter sends it as Meta **eventID**. Verify the event in Meta diagnostics before relying on provider deduplication. Cloudflare Zaraz remains a separate integration, so check its destination configuration for duplicate sends.
