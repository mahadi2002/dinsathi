# Starting DinSathi locally (Windows / XAMPP)

## Two terminal tabs

```powershell
# Tab 1 — MySQL (background, leave running)
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone"

# Tab 2 — the app (blocks this tab, that's expected)
cd W:\Websites\dinsathi\public
C:\xampp\php\php.exe -S 127.0.0.1:8030 router-dev.php
```

Open `http://127.0.0.1:8030`.

**Use `router-dev.php`, never `index.php` directly**, with `php -S` — the
front controller doesn't know how to fall through to a static file, so
CSS/JS/images 404 otherwise.

**Leave both processes running across sessions** — don't `taskkill`
`php.exe`/`mysqld.exe` just because a task finished. Check what's already up
with `tasklist | findstr "mysqld php"` before assuming something's broken;
port 8030 is DinSathi's — the other three apps in this workspace use
8000/8010/8020.

## First time only

```bash
cp .env.example .env
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"   # run twice → APP_KEY, HASH_PEPPER
```
Paste each output into `.env` (`APP_KEY=`, `HASH_PEPPER=`). Set `DB_USER`/
`DB_MIGRATE_USER` to a MySQL user with rights on a fresh `dinsathi` database
(root is fine on a local XAMPP install).

```bash
php database/migrate.php --fresh --seed
php tests/smoke.php
```

## Seeded admin account

`php database/migrate.php --fresh --seed` runs `database/seeds/001_admin.php`,
which inserts one `admin_users` row if none exists for that email yet:

| Role  | Login           | Credential |
|-------|-----------------|------------|
| Admin | `/admin/login`  | `admin@prohor.local` / `ChangeMe123!` — **change this immediately, see docs/DEPLOYMENT.md** |

There's no seeded regular-user account — create one yourself at
`/register` (email + password, 8+ characters). Registration also creates
that user's default task list automatically (`AuthController::register()`).

## Where outbound mail actually goes

In local dev (`APP_ENV=local`, the `.env.example` default), `MailerService`
never calls a real network — password-reset emails are written to
`storage/logs/mail-YYYY-MM-DD.log` instead of being sent (outside `local`
it falls back to PHP's `mail()` via the host's MTA; see TODO.md — a real
SMTP/API transport is still open). Tail the relevant file to read a reset
link:

```bash
tail -f storage/logs/mail-*.log
```

## Running the background jobs manually

```bash
php cron/run_jobs.php
```

Safe to run repeatedly — daily-only jobs (`GenerateRecurringInstances`,
`Cleanup`) self-guard via the `jobs` table, `RolloverStreaks` self-guards on
the 20:00 Asia/Dhaka threshold, and `DispatchReminders` is idempotent by
design.

## If it won't load

- **500 on every page** — check `.env` has `APP_KEY`/`HASH_PEPPER` set and
  each decodes to exactly 32 bytes; check `storage/logs/app-*.log`.
- **CSS/JS 404** — you're running `index.php` directly instead of
  `router-dev.php` with `php -S`.
- **"Unknown database 'dinsathi'"** — run `php database/migrate.php --fresh`.
- **Password-reset email never arrives** — it's not supposed to over the
  network in local dev; read `storage/logs/mail-*.log` (see above).
