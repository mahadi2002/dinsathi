# DinSathi — AI Build Playbook

## Session Preamble (paste at the start of every coding session)

```
You are building দিনসাথী (DinSathi), a Bangla-language daily planner + reminder app.
Reference files, in this order, before writing any code: 01-BUILD-SPEC.md, 02-SCHEMA.sql,
03-ENV-AND-CONFIG.md. Locked decisions in 01-BUILD-SPEC §0 are non-negotiable — do not
introduce Composer, a framework, Redis, or any CDN dependency. Backend is plain PHP MVC
only. Frontend is unrestricted but keep it framework-free per the lock. Build against
MockGateway and MockSmsGateway — never attempt to guess the real BDApps or SMS provider
API shape. Follow the exact Bangla copy in §10 verbatim; do not paraphrase or "improve" it.
Before ending the session, state which phase you completed and what the next phase is.
```

## Anti-Drift Checklist (run before every commit)

- [ ] No Composer `vendor/` directory introduced
- [ ] No new external CDN `<script>`/`<link>` added
- [ ] Every gated feature checks `SubscriptionMiddleware`, not just hidden in the view
- [ ] All new tables match `02-SCHEMA.sql` exactly — no ad hoc column additions without updating the schema file first
- [ ] All Bangla strings pulled from §10 verbatim, not re-translated
- [ ] All `due_at`/`fire_at` writes are UTC; only view-layer converts to Asia/Dhaka
- [ ] Reminder dispatch idempotency (row lock) still intact after any edit to `DispatchReminders`

---

## Build Phases

### Phase 1 — Core skeleton
Front controller, router, DB wrapper (PDO, prepared statements only), session handler backed by `sessions` table, `.env` loader, base layout view, CSRF helper.
**Acceptance:** hitting `/` renders the landing page shell with no errors; session cookie is set with correct flags.

### Phase 2 — Auth (OTP)
`users`, `otp_verifications`, `rate_limits` tables live. Register/login controllers, `MockGateway::requestOtp/verify`. Rate limiting enforced.
**Acceptance:** can register a Robi/Airtel number, receive (logged, since mock) OTP, verify, land on `/app` with a session.

### Phase 3 — Subscription flow
`subscriptions`, `billing_events` tables. `/subscribe` page with exact copy from §10, `MockGateway::confirmSubscription`, `SubscriptionMiddleware`.
**Acceptance:** unsubscribed user sees free-tier caps; subscribing flips `SubscriptionMiddleware` to unlocked immediately; unsubscribe reverts it.

### Phase 4 — Task CRUD + lists
`task_lists`, `tasks`, `subtasks`, `tags`, `task_tags`. Full CRUD, priority, due date/time, default list auto-created on register.
**Acceptance:** free user can create up to 3 tasks then is blocked with the exact copy string; subscribed user unlimited, can add subtasks/tags/extra lists.

### Phase 5 — Calendar views
Day/week/month views reading from `tasks`. Week/month gated to subscribed.
**Acceptance:** day view works for free users; week/month return the gate prompt for free users and render correctly for subscribed.

### Phase 6 — Recurring engine
`RRuleLite` support class, `is_template`/`parent_template_id` logic, `GenerateRecurringInstances` job.
**Acceptance:** creating a weekly recurring task produces correct instances for the next 30 days; editing with "this + future" only changes unshown/future instances.

### Phase 7 — Reminders core (push)
`reminders`, `push_subscriptions` tables, `sw.js`, VAPID key generation, `PushGateway`, `DispatchReminders` job, quiet-hours logic.
**Acceptance:** a task due in 2 minutes with a reminder offset fires a real browser push notification via the cron job; quiet-hours window correctly delays non-urgent reminders.

### Phase 8 — Reminders (SMS)
`sms_log` table, `MockSmsGateway`, dual-channel dispatch in the same job, `RetrySmsFailures`.
**Acceptance:** same reminder fires both a push and a mock SMS log entry independently; simulated push failure doesn't block the SMS channel and vice versa.

### Phase 9 — Habit tracker
`habits`, `habit_logs`, streak computation, `RolloverStreaks` job, streak-at-risk push.
**Acceptance:** daily check-in persists per Asia/Dhaka date; streak count correct across a manually backdated test dataset; at-risk push fires at 20:00 local for an unchecked habit.

### Phase 10 — Focus timer
`focus_sessions`, simple start/stop UI, optional task link.
**Acceptance:** completed sessions logged with correct duration; visible in a basic history list.

### Phase 11 — Daily review
`daily_reviews`, one entry per user per day, mood selector.
**Acceptance:** subscribed-only; entry persists and is editable same-day, read-only for past days (or explicitly editable — confirm preference during build).

### Phase 12 — Dashboard + design system
Day-progress ring, habit flame-dot row, dark mode, "সময়ছায়া" tokens applied across all views built so far.
**Acceptance:** dashboard ring segments are tappable and filter the task list; dark mode toggle persists per user.

### Phase 13 — Admin panel
`admin_users`, `audit_log`, dashboard (active subs, SMS delivery rate), user lookup with masked numbers, broadcast tool.
**Acceptance:** admin login separate from user auth; broadcast sends a push + SMS to all subscribed users via the same gateways; every admin action writes to `audit_log`.

### Phase 14 — Hardening & launch checklist
Run the full security checklist in `03-ENV-AND-CONFIG.md §9`. Load-test the cron dispatch path with a synthetic backlog of reminders. Confirm `.htaccess` headers with a live header check, not just file inspection.
**Acceptance:** all checklist items ticked; a `curl -I` against `/.env` and `/config/gateway.php` returns 403/404, not 200.

---

## Production Readiness Flags (do not remove before these are resolved)

- SMS provider unresolved → `SmsGatewayAdapter` remains a stub; do not market SMS reliability until a real provider is wired and tested.
- BDApps SDP/OTP contract unresolved → `BdAppsGateway` remains a stub; do not go live on real billing until this is replaced.
- BTRC operator prefix map unverified → confirm before rejecting/accepting numbers in production.
- Web Push crypto implementation (VAPID/AES-GCM) must be independently reviewed or replaced with a vetted library before launch — this is the one place custom crypto was written to preserve the zero-Composer constraint, and it deserves a second look.
