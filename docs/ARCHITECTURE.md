# Architecture

## Request lifecycle

1. `public/index.php` — the only file the web server executes directly.
   Defines `APP_ROOT`, requires `app/bootstrap.php`.
2. `bootstrap.php` — autoloader (`App\` → `app/`), loads `.env`, runs
   startup guards (refuses to boot with `APP_ENV=production` while
   `SUBSCRIPTION_GATEWAY=mock` or `APP_DEBUG=true`), registers error/
   exception/shutdown handlers.
3. `Request::capture()` builds an immutable request from superglobals.
4. `Session::start()` — DB-backed session (see below).
5. `Router::dispatch()` matches `app/routes.php`, runs the route's
   middleware chain, calls the controller action.
6. Controllers stay thin: parse input, call one `Service` method, render a
   view or redirect. All SQL lives in `Repositories/`.
7. `SecurityHeaders` middleware wraps every response, including error pages.

## Things worth knowing before you touch them

- **Sessions live in MySQL** (`sessions` table), not files. This is what
  makes `SubscriptionService::unsubscribe()` able to revoke *other* active
  sessions the instant a subscription lapses — the row is deleted, so the
  next request from that browser has nothing to read. (The unsubscribing
  browser's *own* current request keeps working until it ends — PHP already
  has the session data in memory — but its very next request re-checks the
  DB and gets redirected to `/subscribe`, per `RequireSubscription`/`SubscriptionService`.)
- **No cached "is subscribed" flag, anywhere.** `SubscriptionService::hasAccess()`
  (`planner`) and `hasSmsAccess()` (`sms_reminders`) are both queries every
  time, called fresh on every gated request/dispatch. See the browser
  walkthrough in this repo's build history: unsubscribing and reloading
  `/app` immediately redirects to `/subscribe` — no session flag to go
  stale. There is no free tier for either plan.
- **All `due_at`/`fire_at`/`created_at` columns are UTC.** Conversion to
  Asia/Dhaka happens *only* through `App\Support\DateBD` (or the
  `bn_date_utc()` view helper that wraps it) — never a bare `bn_date()` call
  or `date()`/`strtotime()` on a raw DB column. `bn_date()` alone assumes
  its input is already Dhaka-local (calendar `DATE` columns, URL date
  params); feeding it a UTC `DATETIME` directly silently shows the wrong
  hour. This bug shipped once during this build (task due-times showed as
  noon instead of 6pm) and was caught by browser-testing the actual task
  card, not by code review — see docs/DEVELOPMENT.md's testing note.
- **Push notifications.** `ReminderService` calls `MockPushGateway`
  directly — every environment actually runs the mock today.
  `WebPushGateway` is scaffolding that throws until real VAPID/RFC 8291
  crypto is implemented (see TODO.md); the driver-selection factory that
  used to sit between them was removed since the real gateway had no
  working code path to select. Restore a factory if/when `WebPushGateway`
  becomes real. The subscription/SMS/OTP gateways this section used to
  describe (`SubscriptionGateway`, `SmsGateway`, `BdAppsGateway`,
  `SmsGatewayAdapter`) were deleted along with carrier billing.
- **CSP has no `unsafe-inline`**, on scripts or styles. Every dynamic value
  a view needs goes through a pre-generated CSS utility class
  (`.progress-N`, `.swatch-N`, `.mt-sm`, ...) instead of `style=""`, and
  every confirm-before-submit goes through `data-confirm="…"` + a listener
  in `app.js` instead of `onsubmit="return confirm(...)"`.

## Layer responsibilities

```
Controllers/     parse Request, call one Service, return a Response — never raw SQL
Services/        business rules: caps, subscription state, recurrence math, streak math
Repositories/    all SQL, one class per table (or tight cluster of tables)
Gateways/        external-system interfaces + Mock implementations
Support/         DateBD (UTC↔Dhaka), RRuleLite (recurrence engine)
Jobs/            one class per cron task, called by cron/run_jobs.php
Core/            framework primitives: Router, Db, Session, Csrf, Validator, ...
```

## Cron

One crontab line drives everything (`cron/run_jobs.php`, every minute).
Minute-granularity jobs (`DispatchReminders`, `RetrySmsFailures`) run every
invocation; daily-only jobs (`GenerateRecurringInstances`,
`CheckSubscriptionExpiry`, `Cleanup`) self-guard via a "have I already run
today" check against the `jobs` table. `RolloverStreaks` is a third
pattern: it runs every minute but no-ops until Asia/Dhaka local time
reaches 20:00. A file lock (`cron/_lock.php`) stops overlapping runs from
double-dispatching.

## Recurring tasks

A recurring task is one **template** row (`tasks.is_template = 1`,
`recurrence_rule` set) plus generated **instance** rows for the next
`RECURRENCE_HORIZON_DAYS` (default 30), refreshed daily by
`GenerateRecurringInstances`. `App\Support\RRuleLite` is a deliberately
small subset of RRULE — `DAILY`, `WEEKLY:MO,WE,FR`, `MONTHLY:N` (clamped to
the month's last day), `CUSTOM:N` — not full RFC 5545, to keep the
zero-dependency constraint. See `tests/smoke.php` for its test vectors.
