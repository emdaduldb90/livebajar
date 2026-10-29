# Instrumentation map

| Required event | Source | Actual trigger | Deduplication / caveat |
|---|---|---|---|
| PageView | Browser | Storefront page load and detected History API/popstate path changes | New browser event ID for each transition |
| ViewContent | Browser | Existing product detail `Product Viewed` context, mapped to the canonical name | Product SKU and product metadata only |
| AddToCart | Browser | Existing flash event emitted after `CartService::add()` succeeds | Failed cart validation does not create this event |
| InitiateCheckout | Browser | Existing `Checkout Started` context when non-empty cart checkout page renders | Stable checkout ID is carried as a property |
| Purchase | Server + browser | Server after order transaction commits; browser on authorized order confirmation page | Both use `orders.tracking_purchase_event_id`; server row is idempotent. Current order status is pending/COD, so this means order placed, not paid/delivered. |
| LinkClick | Browser | Delegated click on any same-page anchor; stores current path and destination host only | No destination query, fragment or text value is stored |
| PhoneClick | Browser | Delegated click on `tel:` anchor | Phone number is never copied to event payload |
| EmailClick | Browser | Delegated click on `mailto:` anchor | Email address is never copied to event payload |
| Search | Browser | Existing completed `/shop?q=...` result context | Stores search length and result count only; raw query is excluded |

Existing Zaraz events continue to use their original event names and mapping. The new module maps only the applicable existing contexts to the required canonical names and does not fabricate unimplemented wishlist, refund, gateway, review or customer-login actions.
