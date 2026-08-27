# দিনসাথী (DinSathi)

A Bangla-language daily planner and reminder app — tasks, recurring to-dos,
lists, a habit tracker with streaks, a focus timer, and a daily review.
Free, login-or-registered access only — no subscription, no billing.
Fourth app in this workspace's series (after GardenBondhu, IELTS Master BD,
PustiSathi) — same architecture, re-skinned.

## Stack

- **PHP 8.2+, zero Composer packages.** Hand-rolled front controller,
  Router, Middleware pipeline, Controllers → Services → Repositories, plain
  PHP views.
- **MySQL 8 / MariaDB 10.4+**, `utf8mb4_unicode_ci`, DB-backed sessions.
- **Vanilla JS**, no bundler, no framework. A Service Worker for Web Push
  only — core CRUD works without JS.
- **Strict CSP**, zero `unsafe-inline` on scripts or styles.

This app follows the same architecture as the rest of the series (see
`PLAYBOOK.md` in the parent workspace, if you have it checked out
alongside the other apps — not part of this repo). See
[`TODO.md`](TODO.md) for what has changed since the original build,
including the Phase 1 rebrand/auth rebuild.

## Quick start

```bash
cp .env.example .env
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"   # run twice → APP_KEY, HASH_PEPPER
php database/migrate.php --fresh --seed
php tests/smoke.php
php -S 127.0.0.1:8000 -t public public/router-dev.php
```

**Windows/PowerShell** needs two separate tabs — MySQL backgrounded, the
PHP server blocking its own tab (that's it actively serving, not stuck):

```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone"
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public/router-dev.php
```

**Stopping it:** `Ctrl+C` in the PHP server's tab. MySQL is left running on
purpose (shared infrastructure) — `C:\xampp\mysql\bin\mysqladmin.exe -u
root shutdown` if you want it down too.

Full walkthrough, including port conventions and troubleshooting, is in
[STARTING.md](STARTING.md).

## Docs

- [STARTING.md](STARTING.md) — local dev setup, seeded credentials
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- [docs/DATABASE.md](docs/DATABASE.md)
- [docs/SECURITY.md](docs/SECURITY.md)
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
- [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)
- [TODO.md](TODO.md)

## License

MIT — see [LICENSE](LICENSE).
