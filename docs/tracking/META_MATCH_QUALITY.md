# Meta match quality: current implementation

This note describes the tracking module as it is implemented now. The score itself belongs to Meta Events Manager; this application neither computes nor displays an Event Match Quality score.

## What the module can send

- A server Purchase can contain checkout email and/or phone hashed with SHA-256. Email is trimmed and lowercased; phone punctuation is stripped, but the module does not add a country code or convert local numbers to international format.
- Hashes are generated only when the event has a recorded consent grant and the administrator enables **Allow consented, SHA-256 hashed checkout email/phone for server matching**.
- The optional hashes are stored encrypted at rest, then the Meta adapter maps them to **em** and **ph** for the queued Conversions API request.
- This applies to the backend Purchase event only. It does not add identity to browser events or other event names.
- Meta server delivery also needs collection enabled, the Purchase → Meta server route, server CAPI enabled, Pixel ID, access token, a supported Graph API version and an asynchronous queue. In Test Mode, a Test Events code is also required.

## What is deliberately absent

- The module does not collect or send **_fbp, _fbc, IP address, User-Agent** or **external_id**.
- Raw email and phone are not placed in event properties or browser JavaScript.
- There is no guarantee of a particular match-quality score, attribution, or reporting outcome.

## Safe validation

Use a consented staging/test order and Meta Test Events first. Confirm the Purchase in Meta diagnostics, then review Meta Events Manager's match-quality diagnostics. Browser Pixel tags are suppressed while this module's Test Mode is enabled. Before using browser and server copies together, confirm both use the same event name and event ID and check existing Cloudflare Zaraz tags for duplicate destinations.

Hashed identifiers are still pseudonymous personal data. Enable matching only when the site's notice, consent, applicable policy, and account-side requirements allow it. Review current Meta normalization and API requirements when configuring the integration.
