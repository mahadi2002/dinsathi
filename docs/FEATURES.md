# Features

## Subscription state machine

`subscriptions.status`: `active ⇄ suspended/expired → unsubscribed`, or
`→ unsubscribed` from any state at any time (`POST /unsubscribe` is gated
only by `auth`, never by `sub` — reachable while suspended or expired too).

`MockGateway` decides the initial state from the mobile number's last two
digits (see STARTING.md's test-number table); `CheckSubscriptionExpiry`
(daily cron) re-runs the same simulated charge for every subscription past
`next_charge_at`. There is no `pending` state in this schema (unlike some
sibling apps) — `MockGateway::confirmSubscription()` is synchronous, so a
subscription is `active`/`suspended`/`expired` from the moment it's created.

## No free tier — two-tier subscription

There is no free tier and no task/list cap. `subscriptions.plan` is
`planner` or `sms_reminders`, one row per (user, plan). Every `/app/*`
route requires an active `planner` subscription (`sub` route middleware,
re-verified in `TaskService`/service layer via
`SubscriptionService::hasAccess()` — defense in depth, not just hidden in
the view). Push notifications ship bundled with `planner` at no extra
charge; SMS delivery additionally requires `SubscriptionService::hasSmsAccess()`
(the `sms_reminders` plan), checked at dispatch time in `ReminderService`
since it's a channel toggle, not a page. See docs/ROUTES.md for the full
route/plan matrix.

## Recurring tasks

See docs/ARCHITECTURE.md's Recurring tasks section and
`App\Support\RRuleLite`. `RecurrenceService::generateFor()` is called both
at task-creation time (initial batch) and daily by the
`GenerateRecurringInstances` job (keeps the horizon topped up). Editing a
template only affects *future* generation — already-created instances are
independent rows and are never mutated retroactively.

## Reminders — dual channel

`reminders` table, one row per (task, fire time). `ReminderService::dispatchDue()`
(cron, every minute):

1. Selects `pending` rows with `fire_at <= now`.
2. Row-locks each with `UPDATE ... WHERE status='pending'` before touching
   it — two overlapping cron runs can't double-send.
3. Quiet hours (`users.push_quiet_start/end`, default 22:00–07:00
   Asia/Dhaka) delay the reminder to the window's end, **unless** the
   source task's priority is `urgent`.
4. Push and SMS are dispatched independently and tracked separately
   (`push_status`, `sms_status`) — a push failure never blocks the SMS leg
   or vice versa. SMS failures get 3 retry attempts via `RetrySmsFailures`.

## Habit streaks

Computed on read (`HabitService::streak()`), never stored — avoids drift
bugs. A streak counts consecutive **active days**
(`habits.active_days`) with a completed check-in, ending today or
yesterday; a day the habit wasn't scheduled on is skipped, not treated as a
gap. Verified against hand-built fixtures in `tests/smoke.php` isn't
practical for streak math specifically (it needs seeded historical
`habit_logs` rows), but `RRuleLite`'s date-math sibling logic is covered
there as a proxy for the same class of off-by-one bugs.

## Known open items

- **Real BDApps SDP/OTP API contract** — not obtained. `BdAppsGateway`
  throws on every method until real docs exist (01-BUILD-SPEC.md §13).
  `SUBSCRIPTION_GATEWAY=mock` is hard-blocked whenever `APP_ENV=production`.
- **SMS provider unresolved** — `SmsGatewayAdapter` refuses to run without
  `SMS_PROVIDER_API_BASE`/`_API_KEY` configured, and even then has no
  request-shape implementation, since the provider itself hasn't been
  chosen. `MockSmsGateway` (writes to `sms_log` + the app log) is what
  every environment actually runs today.
- **Real Web Push is not implemented.** `PushGateway` defaults to
  `MockPushGateway`, which writes an in-app `notifications` row instead of
  a real browser push. A correct implementation needs a hand-signed VAPID
  JWT (ES256) and RFC 8291 payload encryption (ECDH P-256 + HKDF-SHA256 +
  AES-128-GCM) using only `ext-openssl`, with zero Composer dependencies —
  genuine crypto that fails silently when wrong (a malformed payload just
  never shows a notification; there's no user-visible error). Flagged in
  04-AI-BUILD-PLAYBOOK.md's Production Readiness Flags as needing
  independent review or a vetted library before switching `PUSH_GATEWAY`
  away from `mock`. The full plumbing around it — service worker, the
  subscribe/unsubscribe endpoints, `push_subscriptions` storage, the
  dispatch call site — is real and tested; only the actual crypto is a
  stub.
- **Contact form has no dedicated table.** `02-SCHEMA.sql` doesn't include
  one; messages are appended to `storage/logs/contact-*.log` and shown
  read-only in `/admin/contact`. Promote to a real `contact_messages` table
  with states (new/resolved) the moment volume makes a log file
  impractical — that's a schema addendum
  (`0NN_addendum_contact_messages.sql`), not a silent change to
  `02-SCHEMA.sql`.
- **Self-hosted webfonts not vendored.** The design calls for Hind
  Siliguri (Bangla) + Inter (Latin/numerals), self-hosted per the no-CDN
  rule. This build ships a system-font stack (Nirmala UI / Noto Sans
  Bengali / Segoe UI) instead, since fetching and licensing real `.woff2`
  files wasn't possible in this environment. Visually solid on
  Windows/Android/most Linux; add real webfonts before treating the visual
  design as final — see docs/DEVELOPMENT.md.
- **BTRC operator prefix map unverified** — same flag raised independently
  by every sibling app in this workspace. `config/operators.php` uses
  018=Robi, 016=Airtel (matching the other apps in this workspace and
  corrected from the build spec's original landing-page draft, which had
  016/019=Robi and 017=Airtel — 017 is actually Grameenphone and 019 is
  Banglalink). Confirm against the real BTRC allocation before launch.
- **"3 lifetime tasks"** — see the task-cap note above; implemented as a
  live count, not a persisted monotonic counter.
