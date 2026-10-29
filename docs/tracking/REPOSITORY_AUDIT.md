# Repository audit

Baseline audit date: 2026-10-28, before this module was implemented.

## Application facts

- Composer declares `laravel/framework:^10.10`; installed runtime reports Laravel 10.50.3 and PHP 8.2.12. Composer requires PHP `^8.1`.
- Storefront is server-rendered Blade. Tailwind/Vite configuration exists, while shipped storefront CSS and small scripts also live under `public/`.
- One `web` session guard authenticates admins. `EnsureAdmin` checks `users.is_admin`. There is no role/permission package or analyst/viewer role model.
- Product catalog, session cart, coupon, checkout, orders and order items exist. `CheckoutService::place()` checks stock and writes the order and order items in a transaction. Current checkout creates `pending` orders with `payment_status=pending`; COD is supported and no payment gateway callback was found.
- Search is a GET request to `/shop` with `q`, category, price and sort filters. Raw search text is not copied into the new first-party event store or AI inputs.
- Existing tracking code included a 54-name `EventRegistry`, session-flashed cart/checkout events, `public/js/tracking.js` and Cloudflare Zaraz calls. The registry now contains 63 governed names. Cloudflare/Zaraz configuration is outside this repository and cannot be verified here.
- Before this work, `.env.example` and the queue config defaulted to `sync`. The module now defaults to `database` and its additive migration supplies the dedicated `tracking_jobs` table.
- Existing `site_settings` are JSON storefront settings, not a safe place for API credentials. The new module stores its configuration in a separate encrypted-cast table.

## Safety decisions

- No `.env` values were read or copied into source or documentation. No application database migration was run against the working environment.
- The prior Zaraz adapter, admin authentication, routes, checkout behavior and customer records were preserved.
- Direct provider browser routes, server routes, first-party collection and AI start disabled. Consent is required by default. Test mode is on by default; browser destination scripts remain suppressed while it is on.
- A basic storefront allow/reject privacy choice was added. It is not a full CMP; the site owner must review policy text and provider purposes.
- No raw IP, user agent, customer name, address, email, phone, full URL query or search text is stored in the event record. Optional normalized checkout email/phone hashes are encrypted at rest and generated only when the consent gate passes and matching is explicitly enabled.

## Repository tooling limits

The workspace has no `.git` directory, so a git diff/status baseline was unavailable. The repository already contained earlier tracking documentation and tests dated 2026-10-15; those files describe the pre-module Zaraz work and should be read alongside the new documents here.
