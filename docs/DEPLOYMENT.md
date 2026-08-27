# Deployment (cPanel / shared hosting)

1. **App code outside the web root** — `/home/USER/dinsathi/` (not
   web-accessible) vs. `/home/USER/public_html/` pointing at
   `dinsathi/public`. If the host can't point the docroot there, copy
   `public/`'s contents into `public_html/` and add
   `define('APP_ROOT', '/home/USER/dinsathi');` near the top of the copied
   `index.php` (see the comment already in `public/index.php`).
2. **Fresh `.env` on the server itself** — never copy dev's `APP_KEY`/
   `HASH_PEPPER` over. Generate both with
   `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`.
3. **Two MySQL users**: the app's runtime user gets only
   `SELECT, INSERT, UPDATE, DELETE`; a separate `DB_MIGRATE_USER` with DDL
   rights is used only by `database/migrate.php`. A SQL-injection bug that
   somehow slips through still can't `DROP`/`ALTER` anything.
4. `php database/migrate.php` (`--seed` only on a genuinely fresh install —
   **delete or change the seeded admin account immediately after**, see
   STARTING.md's seeded credentials table).
5. Add the cron entry (the exact line is also a comment at the top of
   `cron/run_jobs.php`):
   ```
   * * * * * php /path/to/dinsathi/cron/run_jobs.php >> /path/to/dinsathi/storage/logs/cron.log 2>&1
   ```
6. `APP_ENV=production`, `APP_DEBUG=false`. **The app refuses to boot** if
   `APP_ENV=production` while `APP_DEBUG` is still on
   (`app/bootstrap.php`'s startup guards) — that's intentional, fix the env
   config rather than working around it.
7. Point an uptime monitor at `GET /health` (runs a real `SELECT 1`), not
   `/`.
8. Nightly `mysqldump --single-transaction | gzip` to off-server storage,
   and **test a restore at least once** before you need it for real.
9. Go-live checklist:
   - `.env` chmod 600, outside web root, not in git
   - `curl -I https://.../.env` and `.../storage/logs/app.log` both
     403/404 (also spot-check `/config/gateway.php` — same rule)
   - Admin password changed from the seeded default (`ChangeMe123!`)
   - Fresh `APP_KEY`/`HASH_PEPPER` (not copied from dev)
   - CSP has no `unsafe-inline` (already true by default — don't add it)
   - Manually verify, in a second browser, that a session dies on the
     browser's *next* request after a password reset (see docs/SECURITY.md)
10. **`PUSH_GATEWAY` stays `mock` until the VAPID/RFC 8291 crypto
    implementation gets a real review** — see TODO.md. Don't flip it to
    `webpush` without that review; `WebPushGateway` currently just throws.
    Web Push is the only reminder channel this app has — there is no SMS
    fallback.
11. The one thing that doesn't survive scaling past one server as-is:
    nothing, actually — this app has no local-disk uploads (unlike its
    photo-heavy siblings). Sessions live in MySQL, storage/ only holds logs
    and cron lock files. Safe to run behind a load balancer once the DB is
    shared.
