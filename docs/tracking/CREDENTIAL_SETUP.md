# Credential setup

All external connectors and AI begin disabled. Enter secrets only through `/admin/tracking` over HTTPS. Stored values use Laravel's encrypted cast and the application `APP_KEY`. Keep that key backed up securely; rotating it without re-encrypting tracking credentials makes them unreadable. Secrets are omitted from public configuration and configuration backups.

## Meta Pixel and Conversions API

1. In Meta Events Manager, create or select a website data source and copy its Pixel/Dataset ID.
2. Create an access token with the permissions appropriate for the selected dataset and direct Conversions API setup.
3. During setup, add a Test Events code and a currently supported Graph API version in the admin panel.
4. Keep Test Mode on, enable only Purchase server routing, use a non-production/test checkout, and confirm Events Manager diagnostics. Meta Pixel browser calls are suppressed while Test Mode is on; the browser tag does not use the server Test Events code.
5. Before switching Test Mode off, check Cloudflare Zaraz and any existing tags for an already configured Meta destination.

Official references: [Meta Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api/) and [Meta browser/server deduplication](https://developers.facebook.com/docs/marketing-api/conversions-api/deduplicate-pixel-and-server-events).

## TikTok Pixel and Events API 2.0

1. Create/select a website Pixel in TikTok Ads Manager and copy its Pixel Code.
2. Generate an Events API access token for that Pixel.
3. Configure the ID and token. Browser tags stay suppressed in Test Mode. The current Events API 2.0 Web schema and endpoint were reviewed, but a supported Test Events payload field was not verified; this module therefore blocks TikTok server delivery in Test Mode rather than sending a guessed test code.
4. After review, use a non-production Pixel/domain and explicitly switch out of Test Mode to validate Events API. The server integration requires explicitly consented matching data for Purchase; it stays blocked if this is unavailable. Enable optional hashed checkout matching only after confirming consent and data-use terms.
5. Verify event receipt and browser/server deduplication in Events Manager. HTTP `200` alone is not enough; the API response code must be zero.

Official references: [TikTok Events API 2.0 Web setup and endpoint](https://business-api.tiktok.com/gateway/docs/index?doc_id=1771100779668482), [TikTok event deduplication](https://business-api.tiktok.com/gateway/docs/index?doc_id=1771100865818625), and [TikTok Test Events guide](https://business-api.tiktok.com/portal/docs/verify-events-setup/v1.3). The Test Events guide sample uses the legacy v1.0 `/pixel/track/` endpoint; it is not silently reused for the 2.0 integration.

## Google Analytics 4

1. In the GA4 property, open the web data stream and copy its Measurement ID (`G-...`).
2. For Measurement Protocol, create an API secret in that same stream and enter it in the server-only secret field.
3. To correlate server Purchase events, allow the storefront tag to set GA cookies and grant consent. The first-party SDK reads `_ga` and `_ga_<stream>` only after consent; IDs are encrypted in event storage.
4. In Test Mode, server events use debug mode. A successful HTTP response is not validation and does not prove the event appears in reports. Use the official validation server and DebugView before production.

Official references: [GA4 Measurement Protocol](https://developers.google.com/analytics/devguides/collection/protocol/ga4), [sending events](https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events?client_type=gtag), and [validation](https://developers.google.com/analytics/devguides/collection/protocol/ga4/validating-events).

## Google Ads

Browser conversion requires a Conversion ID and Conversion Label. Only Purchase routes to the configured conversion action. Server conversion uploads need Google Ads API OAuth credentials, a customer account, a conversion action, consent settings and a currently supported upload method. The Google Ads API docs note new upload-click conversion integrations must use Data Manager API; this server integration is not implemented.

Official references: [Google Ads website conversions](https://support.google.com/google-ads/answer/7521212), [Google Ads API authorization](https://developers.google.com/google-ads/api/rest/auth), [conversion action categories and current upload guidance](https://developers.google.com/google-ads/api/docs/conversions/categories), and [Google Ads API quotas](https://developers.google.com/google-ads/api/docs/best-practices/quotas).

## OpenAI

Use an API key for the project that will own report usage. The key stays server-side. Choose an API model that currently supports Responses API Structured Outputs. The default model is `gpt-4.1-mini`; change it in the settings if your project uses a different supported model. Reports are queued, limited by daily count and output-token cap, and use `store:false` with aggregate metrics only.

Official references: [Responses API](https://developers.openai.com/api/docs/guides/text-generation) and [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses).
