# Meta event mapping

This table describes the existing Cloudflare Zaraz e-commerce/custom event mapping. The direct module's browser and server adapters are separate; see **tracking.md** and **EVENT_SCHEMA.md** for those mappings.

| Live Bajar / Zaraz event | Intended Meta event | Routing status |
|---|---|---|
| Product Viewed | ViewContent | Configure Zaraz Facebook Pixel E-commerce action |
| Product Added | AddToCart | Configure Zaraz Facebook Pixel E-commerce action |
| Checkout Started | InitiateCheckout | Configure Zaraz Facebook Pixel E-commerce action |
| Order Completed | Purchase | Configure Zaraz Facebook Pixel E-commerce action |
| Products Searched | Search | Configure only if the Zaraz tool exposes the mapping |
| Product Added to Wishlist | AddToWishlist | Awaiting wishlist feature |
| Payment Info Entered | AddPaymentInfo | Configure only if the Zaraz tool exposes the mapping |
| CustomerRegistered | CompleteRegistration | Awaiting account feature |
| LeadSubmitted | Lead | Awaiting inquiry form |
| WhatsAppClicked / PhoneCallClicked / MessengerClicked | Contact | Awaiting CTAs |

## Product and value policy

- `product_id` and `sku` are the product SKU, matching the feed's `g:id`.
- Currency is the three-letter code `BDT`; all values are numeric decimal values, never formatted `৳` strings.
- Product view/add use the active selling price. Purchase uses the database transaction's final `orders.total` and saved order-item price snapshot.
- Purchase revenue is `subtotal - discount`; shipping and discount are sent as separate numeric fields. Tax is `0` because this application has no tax calculation.

## Deduplication boundary

For the Zaraz mapping only, this file does not configure a Meta CAPI endpoint or guarantee that Zaraz and the separate direct CAPI adapter share an event ID. The direct Meta adapter now sends the persisted Purchase event ID as **event_id**. Verify the separate destinations in Meta diagnostics and prevent duplicate sends.
