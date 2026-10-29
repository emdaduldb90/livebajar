# Shared hosting deployment

The module needs no Docker, Node server, Redis, GTM server container, Stape or VPS. Compile front-end assets only if changing the existing Vite app; the tracking SDK is a plain local JavaScript file in `public/js/`.

## First deployment

1. Back up the database and deploy the code.
2. Review and run the additive migration: `php artisan migrate --force`.
3. Configure the environment with `QUEUE_CONNECTION=database` and `APP_KEY` set. Do not copy actual credentials into `.env.example` or source control.
4. Start a queue worker for the `tracking,default` queues. On hPanel/cPanel, create a persistent process if supported. Otherwise configure a frequent cron invocation such as `php artisan queue:work database --queue=tracking,default --stop-when-empty --tries=5 --max-time=50` and confirm the host's process/time limits permit it.
5. Add the scheduler cron entry `* * * * * php /path/to/artisan schedule:run`. Confirm the hosting plan runs cron reliably. The daily retention cleanup only works when the scheduler runs.
6. Visit `/admin/tracking`, save test credentials and test in a staging environment.

Exact panel labels and process supervision differ by hosting provider and plan. Do not assume a shared host supports an always-on worker. When `QUEUE_CONNECTION=sync` or `null`, this module blocks external HTTP delivery and AI generation rather than making those calls during a page/checkout/admin request.

## Operations

- Queue worker: `php artisan queue:work database --queue=tracking,default --tries=5 --timeout=20`
- Failed queue inspection: `php artisan queue:failed`
- Retry after inspecting failure: `php artisan queue:retry all`
- Scheduler validation: `php artisan schedule:list`
- Retention dry-run is not available; `php artisan tracking:prune` deletes events past the configured retention window and cascades their delivery rows.
- After environment changes, use `php artisan config:clear` and, when appropriate, `php artisan config:cache`.

Use separate, restricted database and filesystem backups for encrypted credentials. The panel's JSON configuration export intentionally excludes secrets.
