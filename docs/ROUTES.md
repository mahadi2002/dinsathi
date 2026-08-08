# Routes

Middleware keys: `csrf` · `guest` · `auth` · `sub` (active subscription) ·
`admin` · `rl:<bucket>` (rate limit). Full source of truth is
`app/routes.php` — this is a guided tour, not a duplicate to keep in sync
by hand.

## Public

| Route | Guards | Notes |
|---|---|---|
| `GET /` | — | Landing page, exact §10 copy |
| `GET /privacy`, `/terms`, `/contact` | — | |
| `POST /contact` | `csrf`, `rl:contact` | Honeypot + 2s min fill time instead of CAPTCHA |
| `GET /health` | — | Real `SELECT 1`, point an uptime monitor here |

## Auth (phone + OTP only)

| Route | Guards |
|---|---|
| `GET/POST /register`, `/register/otp`, `/register/verify` | `guest` |
| `GET/POST /login`, `/login/otp`, `/login/verify` | `guest` |
| `POST /otp/resend` | `guest`, `rl:otp_resend` |
| `POST /logout` | `auth`, `csrf` |

## Subscription — two independent plans

`subscriptions.plan` is `planner` (mandatory, gates every `/app/*` route)
or `sms_reminders` (optional add-on, gates only the SMS delivery channel).
Same OTP-confirm shape, separate paths.

| Route | Guards | Notes |
|---|---|---|
| `GET /subscribe` | `auth` | Planner: current status, OTP box; `?manage=1` re-shows it even when active |
| `POST /subscribe/otp` | `auth`, `rl:otp_request` | BDApps SDP OTP (separate from login OTP) |
| `POST /subscribe/confirm` | `auth` | Activates `planner` via `SubscriptionGateway::confirmSubscription()` |
| `GET /subscribe/status` | `auth` | Poll endpoint (JSON) |
| `POST /unsubscribe` | `auth` **only, no `sub`** | Planner unsubscribe; reachable from every state — see PLAYBOOK.txt §6 |
| `GET /subscribe/sms` | `auth` | SMS add-on: current status, OTP box |
| `POST /subscribe/sms/otp` | `auth`, `rl:otp_request` | |
| `POST /subscribe/sms/confirm` | `auth` | Activates `sms_reminders` |
| `GET /subscribe/sms/status` | `auth` | Poll endpoint (JSON) |
| `POST /unsubscribe/sms` | `auth` **only, no `sub`** | SMS add-on unsubscribe; push stays on |

## Gated app (`/app/*`)

No free tier. Every route below requires `auth` **and** `sub` (active
`planner` subscription) — a lapsed/no subscription redirects to
`/subscribe`. `SubscriptionService::hasAccess()` is re-verified inside the
service layer too (`TaskService`, etc.), not just at the route — defense
in depth, not just hidden in the view. SMS delivery is a separate,
finer-grained gate: `SubscriptionService::hasSmsAccess()` is checked inside
`ReminderService` at dispatch time, since it's a channel toggle inside
Settings, not a page.

| Route | Notes |
|---|---|
| `GET /app` | dashboard; SMS add-on upsell shown when `sms_reminders` isn't active |
| `GET /app/day/{date}`, `/app/week/{date}`, `/app/month/{date}` | |
| `GET/POST /app/tasks`, `GET/PATCH/DELETE /app/tasks/{id}` | task CRUD; `GET /app/tasks` supports `?q=`, `?tag_id=`, `?list_id=` |
| `POST /app/tasks/{id}/complete` | |
| `POST /app/tasks/{id}/subtasks`, `PATCH /app/subtasks/{id}` | |
| `GET/POST /app/lists`, `PATCH/DELETE /app/lists/{id}` | |
| `GET/POST /app/habits`, `POST .../checkin`, `GET .../history`, `DELETE /app/habits/{id}` | |
| `GET/POST /app/focus` | |
| `GET/POST /app/review/{date}` | |
| `GET /app/insights` | weekly/monthly task + focus + habit-streak summary |
| `GET/POST /app/settings` | prefs, planner + SMS add-on status, export, push-subscribe |

## Admin (`/admin/*`) — entirely separate auth (email + password, not phone/OTP)

| Route | Notes |
|---|---|
| `GET/POST /admin/login` | `rl:admin_login` |
| `GET /admin` | dashboard: active subs, MRR proxy, SMS delivery rate |
| `GET /admin/users`, `/admin/users/{id}` | numbers masked in list, full number on detail (audit-logged) |
| `GET /admin/billing-events`, `/admin/sms-log` | |
| `GET /admin/contact` | tails `storage/logs/contact-*.log` — see docs/FEATURES.md |
| `GET/POST /admin/broadcast` | push + optional SMS to every active subscriber |
