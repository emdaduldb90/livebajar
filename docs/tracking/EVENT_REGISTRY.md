# Event registry

The canonical source list is `app/Services/Tracking/EventRegistry.php`; the browser adapter mirrors that list in `public/js/tracking.js`. “Awaiting feature” is intentional: no event is fabricated for a feature that does not exist.

| # | Event | Type | Actual trigger / status | Meta / GA4 route |
|---:|---|---|---|---|
| 1 | Product List Viewed | Zaraz e-commerce | Implemented: product list becomes visible on home/shop | Zaraz e-commerce mapping |
| 2 | Products Searched | Zaraz e-commerce | Implemented: completed `q` search request | Zaraz e-commerce mapping |
| 3 | Product Clicked | Zaraz e-commerce | Implemented: product-card detail link click | Zaraz e-commerce mapping |
| 4 | Product Viewed | Zaraz e-commerce | Implemented: active detail page response | ViewContent via Zaraz dashboard |
| 5 | Product Added | Zaraz e-commerce | Implemented: after server cart mutation succeeds | AddToCart via Zaraz dashboard |
| 6 | Product Added to Wishlist | Zaraz e-commerce | Awaiting wishlist feature | Not routed |
| 7 | Product Removed | Zaraz e-commerce | Implemented: after server cart removal succeeds | Zaraz e-commerce mapping |
| 8 | Cart Viewed | Zaraz e-commerce | Implemented: non-empty cart page | Zaraz e-commerce mapping |
| 9 | Checkout Started | Zaraz e-commerce | Implemented: valid checkout page with server checkout UUID | InitiateCheckout via Zaraz dashboard |
| 10 | Checkout Step Viewed | Zaraz e-commerce | Implemented: checkout contact/shipping step | Zaraz e-commerce mapping |
| 11 | Checkout Step Completed | Zaraz e-commerce | Implemented: backend order transaction succeeds | Zaraz e-commerce mapping |
| 12 | Shipping Info Entered | Zaraz e-commerce | Implemented: valid checkout accepted; no address is emitted | Zaraz e-commerce mapping |
| 13 | Payment Info Entered | Zaraz e-commerce | Implemented: valid checkout accepted; payment method only | AddPaymentInfo via Zaraz dashboard |
| 14 | Order Completed | Zaraz e-commerce | Implemented: persisted order confirmation page | Purchase via Zaraz dashboard |
| 15 | Order Updated | Zaraz e-commerce | Awaiting configured server endpoint and business rule | Not routed |
| 16 | Order Refunded | Zaraz e-commerce | Awaiting refund workflow | Not routed |
| 17 | Order Cancelled | Zaraz e-commerce | Awaiting configured server endpoint and business rule | Not routed |
| 18 | Viewed Promotion | Zaraz e-commerce | Implemented: home hero visibility | Zaraz e-commerce mapping |
| 19 | Clicked Promotion | Zaraz e-commerce | Implemented: home hero CTA click | Zaraz e-commerce mapping |
| 20 | CategoryViewed | Custom | Implemented: category-filter result page | Optional GA4/custom action |
| 21 | ProductFiltered | Custom | Implemented: completed filtering request | Optional GA4/custom action |
| 22 | ProductSorted | Custom | Implemented: non-default completed sort | Optional GA4/custom action |
| 23 | ProductCompared | Custom | Awaiting comparison feature | Not routed |
| 24 | ProductImageViewed | Custom | Implemented: gallery image click | Optional GA4/custom action |
| 25 | ProductVideoPlayed | Custom | Awaiting video feature | Not routed |
| 26 | ProductVariantSelected | Custom | Awaiting variants | Not routed |
| 27 | ProductSpecificationViewed | Custom | Awaiting specification UI | Not routed |
| 28 | ProductReviewViewed | Custom | Awaiting reviews | Not routed |
| 29 | ProductReviewSubmitted | Custom | Awaiting reviews | Not routed |
| 30 | StockAlertRequested | Custom | Awaiting stock-alert feature | Not routed |
| 31 | PriceDropAlertRequested | Custom | Awaiting price-alert feature | Not routed |
| 32 | CouponApplied | Custom | Implemented: server accepts coupon | Optional GA4/custom action |
| 33 | CouponFailed | Custom | Implemented: server rejects usable-looking coupon | Optional GA4/custom action |
| 34 | ShippingCostCalculated | Custom | Implemented: delivery-area option changes | Optional GA4/custom action |
| 35 | BuyNowClicked | Custom | Implemented: Buy Now click | Optional Meta custom / GA4 |
| 36 | CheckoutError | Custom | Implemented: validation/processing errors return to checkout | GA4/internal only |
| 37 | PaymentFailed | Custom | Awaiting real payment gateway callback | Internal only |
| 38 | PaymentSuccessful | Custom | Awaiting real payment gateway callback | Internal only |
| 39 | CODSelected | Custom | Implemented: COD radio selection | Optional GA4/custom action |
| 40 | EMISelected | Custom | Awaiting EMI option | Not routed |
| 41 | OrderConfirmed | Custom | Implemented: persisted order accepted by backend | Optional GA4/internal |
| 42 | OrderShipped | Custom | Awaiting authorised server delivery | Internal only |
| 43 | OrderDelivered | Custom | Awaiting authorised server delivery | Internal only |
| 44 | ReturnRequested | Custom | Awaiting return workflow | Internal only |
| 45 | RepeatPurchase | Custom | Awaiting customer identity policy/workflow | Not routed |
| 46 | CustomerRegistered | Custom | Awaiting customer registration | CompleteRegistration when added |
| 47 | CustomerLoggedIn | Custom | Awaiting customer login | Optional GA4/custom action |
| 48 | WhatsAppClicked | Custom | Awaiting WhatsApp CTA | Contact when added |
| 49 | PhoneCallClicked | Custom | Awaiting telephone CTA | Contact when added |
| 50 | MessengerClicked | Custom | Awaiting Messenger CTA | Contact when added |
| 51 | WarrantyInfoViewed | Custom | Awaiting warranty UI | Optional GA4/custom action |
| 52 | StoreLocationViewed | Custom | Awaiting store-location UI | Optional GA4/custom action |
| 53 | ProductShared | Custom | Awaiting share UI | Optional GA4/custom action |
| 54 | LeadSubmitted | Custom | Awaiting inquiry form | Lead when added |

Only browser actions that really exist are connected. Awaiting entries remain registry-controlled integration points, not successful events.
