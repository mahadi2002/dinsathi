# দিনসাথী (DinSathi) — Master Build Specification

Daily planner + reminder app for Bangladesh users. Fourth app in the series, following the GardenBondhu / IELTS Master BD / PustiSathi pattern exactly: plain PHP MVC, zero Composer, MySQL-only state, BDApps daily micro-subscription gate. Hosting undecided → build to the **shared-hosting baseline** (runs unchanged on a VPS). BDApps credentials not yet available → build entirely against `MockGateway`. Reminders must reach the user via **both Web Push and SMS** — this is the one architectural addition versus prior apps.

---

## 0. Locked Decisions

| Area | Decision | Rationale |
|---|---|---|
| Architecture | Single front controller, hand-rolled MVC | Consistent with series; no framework, no Composer |
| Templating | Plain PHP views + mandatory `e()` escape helper | No Twig, no build step |
| Database | MySQL 8 / MariaDB 10.4+, `utf8mb4_unicode_ci` | Bangla text correctness |
| Sessions | Custom DB session handler (`sessions` table) | Shared-hosting safe; server-side revocation on subscription expiry |
| Cache / rate limit | MySQL tables (`rate_limits`) | No Redis assumption |
| Background jobs | `jobs` table + **one cron entry every minute** | Reminder dispatch, recurrence generation, streak rollover, subscription renewal checks |
| CSS | Hand-written, compiled to one `app.css`, no Tailwind CDN | CDN forces `unsafe-inline` CSP |
| JS | Vanilla ES6, no bundler, Service Worker for Web Push only | Core CRUD works without JS; push requires SW by spec |
| Payment gateway | `SubscriptionGateway` interface → `MockGateway` (dev) + `BdAppsGateway` (prod stub) | No BDApps docs yet |
| SMS gateway | `SmsGateway` interface → `MockSmsGateway` (dev, logs to table) + `SmsGatewayAdapter` (prod stub, provider TBD) | Same swap-one-env-var pattern as payment |
| Push | Web Push (VAPID), no Firebase dependency | No third-party account required to develop; keeps stack framework-free |
| Language | Bangla-first UI, English for technical terms already standard (Task, List, Habit) | Matches source copy conventions |
| Web root | `public/` only | |
| Timezone | All storage in UTC, display converted to Asia/Dhaka | Correct reminder firing regardless of server TZ |

---

## 1. App Overview

**Product:** A daily planner and reminder app — tasks, recurring to-dos, categorized lists, habit tracking with streaks, a Pomodoro-style focus timer, and a daily review — gated behind a ৳2.78/day BDApps subscription for Robi & Airtel users.

**Free tier (no subscription):** view-only demo — create up to 3 tasks total, no reminders, no habit tracker, no push/SMS, no recurring tasks. Enough to evaluate the product, not enough to actually plan a day.

**Subscribed tier:** unlimited tasks, lists, recurring tasks, subtasks, habit tracker with streaks, push + SMS reminders, focus timer, daily/weekly review, data export.

---

## 2. Roles

| Role | Description |
|---|---|
| `user` | Registers via Robi/Airtel number, subscribes, manages own planner data |
| `admin` | Internal staff — user lookup, subscription/billing dashboard, SMS delivery log, broadcast announcements, content moderation (notes are private and not moderated; only abuse reports) |

No third role (unlike PustiSathi's nutritionist) — this is single-user planning, not a shared-caseload product.

---

## 3. Feature Set & Gate Matrix

| Feature | Free | Subscribed |
|---|---|---|
| Register / OTP login | ✅ | ✅ |
| Create tasks (cap 3 lifetime) | ✅ (capped) | ✅ unlimited |
| Due date + time | ✅ | ✅ |
| Priority (Low/Med/High/Urgent) | ✅ | ✅ |
| Subtasks / checklist | ❌ | ✅ |
| Lists / categories (custom, colored) | 1 default list only | ✅ unlimited |
| Tags | ❌ | ✅ |
| Recurring tasks | ❌ | ✅ (daily/weekly/monthly/custom RRULE-lite) |
| Reminders (push) | ❌ | ✅ |
| Reminders (SMS) | ❌ | ✅ |
| Calendar view (day/week/month) | day view only | ✅ all views |
| Habit tracker + streaks | ❌ | ✅ |
| Focus timer (Pomodoro) | ❌ | ✅ |
| Daily review / journal note | ❌ | ✅ |
| Data export (CSV) | ❌ | ✅ |
| Dark mode | ✅ | ✅ |

Gate enforced server-side in `SubscriptionMiddleware` on every write route and on the relevant read routes (habit tracker, focus timer, calendar week/month). Never trust client-side hiding alone.

---

## 4. Route Table

```
GET  /                          → Landing (public)
GET  /register                  → Phone entry
POST /register/otp              → Send OTP (MockGateway/BdAppsGateway OTP call)
POST /register/verify           → Verify OTP → create session
GET  /login                     → Phone entry (existing user)
POST /login/otp                 → Send OTP
POST /login/verify              → Verify OTP → session
POST /logout                    → Destroy session

GET  /subscribe                 → Subscribe screen (mobile number box, from landing copy)
POST /subscribe/otp             → BDApps SDP OTP request
POST /subscribe/confirm         → Confirm subscription → SubscriptionGateway::subscribe()
GET  /subscribe/status          → Poll status (for async gateway confirmation)
POST /unsubscribe               → SubscriptionGateway::unsubscribe()

GET  /app                       → Dashboard (today's tasks, habit ring, quick-add)
GET  /app/day/{date}            → Day view
GET  /app/week/{date}           → Week view          [subscribed]
GET  /app/month/{date}          → Month view          [subscribed]

GET|POST /app/tasks             → List / create task
GET|PATCH|DELETE /app/tasks/{id}→ View / update / delete task
POST /app/tasks/{id}/complete   → Toggle complete
POST /app/tasks/{id}/subtasks   → Add subtask          [subscribed]
PATCH /app/subtasks/{id}        → Toggle subtask

GET|POST /app/lists             → List / create list    [subscribed for >1]
PATCH|DELETE /app/lists/{id}

GET|POST /app/habits            → List / create habit   [subscribed]
POST /app/habits/{id}/checkin   → Mark today done
GET  /app/habits/{id}/history

GET|POST /app/focus             → Focus timer session log [subscribed]

GET  /app/review/{date}         → Daily review view      [subscribed]
POST /app/review/{date}         → Save review note

GET  /app/settings              → Notification prefs, push opt-in, data export
POST /app/settings/push/subscribe   → Store Web Push subscription
POST /app/settings/push/unsubscribe
POST /app/settings/export       → CSV export

GET  /admin/login
GET  /admin                     → Dashboard: active subs, MRR proxy, SMS delivery rate
GET  /admin/users
GET  /admin/users/{id}
GET  /admin/billing-events
GET  /admin/sms-log
POST /admin/broadcast            → Push+SMS announcement to all subscribed users
```

---

## 5. File / Directory Manifest

```
/public
  index.php              (front controller)
  app.css, app.js
  sw.js                  (service worker — Web Push only)
  manifest.webmanifest
/app
  /Core        (Router, Controller, Model, DB, Session, View, Middleware, Csrf)
  /Controllers (Auth, Subscribe, Dashboard, Task, List, Habit, Focus, Review, Settings, Admin*)
  /Middleware  (AuthMiddleware, SubscriptionMiddleware, AdminMiddleware, RateLimitMiddleware)
  /Models      (User, OtpVerification, Subscription, BillingEvent, Task, Subtask, TaskList,
                Tag, Habit, HabitLog, Reminder, PushSubscription, SmsLog, Notification,
                DailyReview, FocusSession, Job, AdminUser)
  /Gateways
    SubscriptionGateway.php (interface)
    MockGateway.php
    BdAppsGateway.php       (stub)
    SmsGateway.php          (interface)
    MockSmsGateway.php
    SmsGatewayAdapter.php   (stub, provider TBD)
    PushGateway.php         (Web Push / VAPID sender)
  /Jobs        (DispatchReminders, GenerateRecurringInstances, RolloverStreaks,
                CheckSubscriptionExpiry, RetrySmsFailures)
  /Views       (auth/, subscribe/, app/, admin/, layout/, partials/)
  /Support     (Validator, Sanitizer, Money, DateBD, RRuleLite)
/config
  app.php, db.php, gateway.php, push.php, sms.php, security.php
/storage
  logs/, cache/
/cron
  run_jobs.php
```

---

## 6. Design System — "সময়ছায়া" (SomoyChhaya, "Time's Shadow")

- **Primary:** deep indigo `#2E3A87` (focus, trust) — **Accent:** warm amber `#F5A623` (streaks, reminders, CTA)
- **Success:** `#2E7D32` · **Danger/urgent:** `#C62828` · **Surface:** `#FAFAFC` · **Dark mode surface:** `#14151F`
- **Fonts:** Hind Siliguri (Bangla body/UI), Inter (numerals, English technical terms), both self-hosted (no Google Fonts CDN — CSP/offline reliability)
- **Signature UI element:** a circular **day-progress ring** on the dashboard (like GardenBondhu's leaf diagnoser, IELTS's progress ring, PustiSathi's plate) — segments show completed/pending/overdue tasks for today; tapping a segment filters the task list. Habit streaks render as a small flame-style dot row, not gamified badges (keeps tone calm, not addictive-by-design).
- Reminder toasts use the amber accent with a bell icon; overdue tasks use danger red left-border, never a full red background (accessibility).

---

## 7. Reminder & Notification Architecture

Two independent delivery channels, both driven by the same `reminders` table — a reminder is not "sent," it is "dispatched" per channel, tracked separately so a failed SMS doesn't block push and vice versa.

- **Trigger creation:** every task/habit with a due time and reminder offset (e.g. "15 min before") inserts a row into `reminders` at `fire_at` (UTC).
- **Dispatch job (`DispatchReminders`, runs every cron minute):** selects `reminders` where `fire_at <= NOW() AND status = 'pending'`, and for each subscribed user with an active `push_subscriptions` row, calls `PushGateway::send()`; for each with SMS reminders enabled, calls `SmsGateway::send()`. Both are best-effort — a push failure (expired subscription, 410 Gone) marks that channel `failed` and deletes the stale subscription; SMS failures go to `RetrySmsFailures` with capped retry count (3) and 5-minute backoff.
- **Idempotency:** dispatch is guarded by `UPDATE ... WHERE status='pending'` row-locking so two overlapping cron runs can't double-send.
- **Quiet hours:** user-configurable (default 22:00–07:00 Asia/Dhaka) — reminders due in that window queue until the window ends, except explicitly "urgent" priority tasks.
- **Web Push specifics:** VAPID key pair generated once at setup, stored in `config/push.php`; `sw.js` handles `push` event, shows notification, `notificationclick` deep-links to `/app/day/{date}`.
- **SMS specifics:** provider is behind `SmsGateway` interface, unresolved (see §13). Message template kept under 160 chars (single SMS segment) to control cost: `"DinSathi: {task_title} — {time}. অ্যাপ খুলুন: {short_link}"`.

---

## 8. Recurring Task Engine

Lightweight RRULE subset (`RRuleLite`), not full iCal RFC 5545 — deliberately, to avoid a dependency:
- Patterns: `DAILY`, `WEEKLY(days[])`, `MONTHLY(day_of_month)`, `CUSTOM(interval_days)`
- A recurring task stores one **template row** (`tasks.is_template = 1`) plus generated **instance rows** for the next 30 days, refreshed by the `GenerateRecurringInstances` cron job daily. Editing the template can optionally cascade to future (not past) instances — user is asked "this task only / this and future" on edit, same UX pattern as Google Calendar.

---

## 9. Habit Tracker & Streaks

- A habit is boolean check-in per day (not measurable/quantity habits in v1 — flagged as a fast-follow, not MVP).
- `habit_logs` one row per habit per calendar day (Asia/Dhaka date, not UTC, so streaks match the user's actual day).
- Streak count computed on read (not stored denormalized) to avoid drift bugs: consecutive `habit_logs.completed = 1` rows ending yesterday or today.
- `RolloverStreaks` cron job (runs once daily at 00:05 Asia/Dhaka) sends a "streak at risk" push at 20:00 local time if today's check-in is still missing, gated to subscribed users only.

---

## 10. Exact Bangla UI Copy

### Landing page — top right
```
মাত্র ৳2.78/day
```

### Landing page — mid-page CTA block
```
🚀 এখনই Start করুন — মাত্র ৳2.78/day
Robi & Airtel Users Only  |  যেকোনো সময় Unsubscribe করুন

প্রতিদিনের কাজ, রিমাইন্ডার আর অভ্যাস — সব একসাথে, একটাই জায়গায়।
কোনো কাজ যেন আর মিস না হয়, DinSathi আপনাকে সময়মতো মনে করিয়ে দেবে —
Push Notification এবং SMS দুইভাবেই। প্রতিদিন মাত্র ৳2.78, কোনো লুকানো চার্জ নেই।

[ Subscribe Now ]
```

### Subscribe box (bottom of landing page)
```
আপনার Robi বা Airtel Number দিন
Instant Access পাবেন DinSathi-এর সব Feature-এ!

Mobile Number
01XXXXXXXXX
শুধু Robi (016/019) ও Airtel (017) Number

⚡
Daily মাত্র ৳2.78 — যেকোনো সময় Unsubscribe করুন

[ OTP পাঠান → ]
```

### Footer
```
Privacy Policy
Terms & Conditions
Contact Us
Powered by BDApps

Robi & Airtel Bangladesh

© 2026 DinSathi — সর্বস্বত্ব সংরক্ষিত

⚠️ এই Service BDApps-এর মাধ্যমে Charge করা হয়। Daily ৳2.78 আপনার Robi/Airtel Account থেকে কাটা হবে। Unsubscribe করতে STOP লিখে 16216 নম্বরে SMS করুন।
```

### In-app microcopy (selected)
```
Free tier limit: "আপনি Free Version-এ আছেন — মাত্র ৩টি Task তৈরি করতে পারবেন। Unlimited Task, Reminder ও Habit Tracker পেতে Subscribe করুন।"
Task cap hit: "Free Limit শেষ! এখনই Subscribe করে সব Feature Unlock করুন — মাত্র ৳2.78/day।"
Reminder push opt-in: "সময়মতো Reminder পেতে Notification Allow করুন।"
Streak at risk: "আজকের '{habit_name}' এখনো Check-in করা হয়নি — Streak ভাঙার আগেই করে ফেলুন!"
OTP error: "OTP মিলছে না। আবার চেষ্টা করুন।"
OTP rate limit: "অনেকবার চেষ্টা হয়েছে। ৫ মিনিট পর আবার চেষ্টা করুন।"
```

---

## 11. Non-Functional Requirements

- **Auth:** OTP-only (no passwords) — matches BDApps SDP flow; sessions server-side, DB-backed, revoked immediately on unsubscribe.
- **Rate limiting:** OTP requests (3/mobile/hour), login attempts, push/SMS dispatch endpoints — all via `rate_limits` table, same pattern as prior apps.
- **CSRF:** token on every state-changing form/AJAX call.
- **Input handling:** parameterized queries only, `e()` escaping in every view, strict Content-Security-Policy (no inline scripts, no third-party CDNs except self-hosted assets).
- **Transport:** HTTPS enforced via `.htaccess` redirect + HSTS header.
- **Data at rest:** mobile numbers stored, phone number partially masked in admin UI (`017XXXXX23`) except on the single detail view needed for support.
- **Backups:** daily `mysqldump` via cron to off-server storage (hosting-agnostic — exact target TBD with hosting decision).
- **Timezone correctness:** all `fire_at`/`due_at` stored UTC; conversion centralized in `Support\DateBD` — no ad hoc `date()` calls in controllers.

---

## 12. Error / Edge-Case Matrix

| Case | Handling |
|---|---|
| OTP expired | 5-minute TTL, clear Bangla error, resend allowed after cooldown |
| Non-Robi/Airtel number entered | Client + server validate prefix (see BTRC map, §13), reject with explicit message before OTP send |
| Subscription lapses mid-session | `SubscriptionMiddleware` re-checks on every gated request (not just login) — degrade to free-tier view instantly, don't delete data |
| Push subscription expired (410) | Silently delete row, fall back to SMS-only for that user until re-opt-in |
| SMS provider timeout | Job retries 3x with backoff, then logs `failed` in `sms_log`, visible in admin dashboard |
| User in quiet hours | Reminder queues, fires at window end, unless task priority = Urgent |
| Recurring task edited mid-series | Explicit "this only / this + future" prompt; past instances never mutated |
| Duplicate cron overlap | Row-level `UPDATE...WHERE status='pending'` prevents double-dispatch |
| Clock drift between fire_at and actual send | Acceptable drift window ±2 min (cron granularity); documented, not hidden |

---

## 13. Open Blockers (flagged, not resolved here)

- Real BDApps SDP/OTP API contract — not yet obtained (build against `MockGateway`)
- SMS gateway provider for Bangladesh (BDApps-bundled SMS vs separate provider like Alpha SMS/Banglalink SMS API) — cost and reliability tradeoffs need your decision before `SmsGatewayAdapter` can be finalized; `MockSmsGateway` unblocks full development meanwhile
- BTRC operator prefix map — same verification flag as GardenBondhu/IELTS Master BD
- BDApps-mandated VAT wording — verify current requirement before launch
- Landing page persuasion copy above is a first draft — legal/marketing review recommended before launch given the subscription-charge disclosure language
