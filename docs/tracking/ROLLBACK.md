# Rollback and data precautions

The tracking migration adds `tracking_configurations`, `tracking_events`, `tracking_deliveries`, `tracking_reports`, and a dedicated `tracking_jobs` queue table. The database queue connection uses this table. It does not modify or delete existing orders, products, users, sessions, storefront settings or prior tracking fields.

## Before rollout

- Back up the database.
- Deploy code and verify PHP syntax/route tests before applying the migration.
- Run the migration with `php artisan migrate --force` only after confirming the target environment.

## Application rollback

1. Disable first-party collection, browser destinations, server destinations and AI in the admin panel.
2. Stop the tracking queue worker and drain or inspect outstanding jobs.
3. Back up `tracking_configurations` securely if credentials must be preserved; the normal JSON backup omits secrets.
4. Roll back application code.
5. Do not run `php artisan migrate:rollback` blindly: migration rollback drops the five new tables and their analytics/configuration/jobs. Inspect migration history first, and export any data the owner wants to retain before dropping these tables.

The old Cloudflare Zaraz adapter and earlier tracking columns are not removed by this migration.
