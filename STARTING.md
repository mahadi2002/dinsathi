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

## Seeded credentials

| Role  | Login                       | Credential |
|-------|------------------------------|------------|
| Admin | `/admin/login`                | `admin@dinsathi.local` / `ChangeMe123!` — **change this immediately, see docs/DEPLOYMENT.md** |
| User  | `/register`                   | Any `01[6/7/9]XXXXXXXX` number — OTP is logged, not really sent (see below) |

## Where the mock OTP/SMS actually go

`MockSmsGateway` and `MockGateway` never call a real network — they write to
`storage/logs/sms-YYYY-MM-DD.log` and `storage/logs/otp-YYYY-MM-DD.log`.
Tail the relevant file to read the code:

```bash
tail -f storage/logs/sms-*.log
```

## Test-number conventions (mock gateway)

Mirrors the convention used across this workspace's other apps:

| Mobile number suffix | Simulates |
|---|---|
| anything else | subscription activates normally |
| ends in `00` | low balance → `suspended` |
| ends in `99` | hard failure → `expired` |

Same-day retry on the same number is intentionally blocked (idempotency) —
use a different test number rather than "fixing" that.

## Running the background jobs manually

```bash
php cron/run_jobs.php
```

Safe to run repeatedly — daily-only jobs self-guard via the `jobs` table,
and minute-granularity jobs (`DispatchReminders`, `RetrySmsFailures`) are
idempotent by design.

## If it won't load

- **500 on every page** — check `.env` has `APP_KEY`/`HASH_PEPPER` set and
  each decodes to exactly 32 bytes; check `storage/logs/app-*.log`.
- **CSS/JS 404** — you're running `index.php` directly instead of
  `router-dev.php` with `php -S`.
- **"Unknown database 'dinsathi'"** — run `php database/migrate.php --fresh`.
- **OTP never arrives** — it's not supposed to over the network; read
  `storage/logs/sms-*.log` (see above).
