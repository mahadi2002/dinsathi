# Database

Schema source of truth: `database/migrations/*.sql`, applied in numeric
order by `database/migrate.php`. `DEFAULT CURRENT_TIMESTAMP` is used
throughout rather than a bare `UTC_TIMESTAMP()` default (MariaDB 10.4
rejects a bare function-call default outside parentheses on `ON UPDATE`);
the connection forces `SET time_zone = '+00:00'` everywhere, so
`CURRENT_TIMESTAMP` already evaluates in UTC — same stored value, portable
syntax.

Migration `010_phase1_email_auth_and_billing_removal.sql` is a hard break:
it swaps `users` from phone+OTP to email+password and drops every
subscription/billing/SMS table outright (no production data existed yet,
so it's a straight ALTER/DROP addendum rather than a data migration). The
table list below reflects the schema **after** 010, not each migration
file's original content in isolation.

## Tables, grouped by migration file

**001_users_auth** — `users`, `sessions`, `rate_limits`
  (`otp_verifications` was dropped by 010)
**002_subscriptions_billing** / **007_addendum_subscription_plans** —
  *removed entirely by 010* (`subscriptions`, `billing_events`)
**003_planner_core** — `task_lists`, `tags`, `tasks`, `task_tags`, `subtasks`
**004_habits_focus_review** / **009_addendum_quantity_habits** — `habits`
  (+ `target_quantity`, `unit`), `habit_logs` (+ `quantity`),
  `focus_sessions`, `daily_reviews`
**005_reminders_notifications_jobs** — `reminders` (no `sms_status`/
  `sms_attempts` after 010), `push_subscriptions`, `notifications`, `jobs`
  (`sms_log` was dropped by 010)
**006_admin_audit** — `admin_users`, `audit_log`
**008_addendum_contact_messages** — `contact_messages`
**010_phase1_email_auth_and_billing_removal** — adds `users.email` /
  `users.password_hash` (unique on `email`), drops `users.mobile_number` /
  `operator` / `sms_reminders_on`, adds `password_resets`, drops
  `otp_verifications`, `subscriptions`, `billing_events`, `sms_log`, and
  `reminders.sms_status` / `sms_attempts`

## Notes worth knowing

- **Auth is email + password now.** `users.email` (`VARCHAR(191)`, unique)
  and `users.password_hash` (bcrypt via `password_hash()`/
  `PASSWORD_DEFAULT`) replaced the old `mobile_number`/`operator` columns.
  `password_resets` holds only a blind-indexed hash of the reset token
  (`Crypto::blindIndex()`), never the raw token, with a TTL and
  `consumed_at` — see docs/SECURITY.md.
- **No subscription/billing tables exist anymore.** The app is free and
  login-only; every `/app/*` route just requires an authenticated,
  `active`-status account (`RequireAuth` middleware) — no plan, no gate.
- **No SMS tables or columns exist anymore.** Reminders are push-only:
  `reminders` has no `sms_status`/`sms_attempts`, and `sms_log` is gone.
  `push_subscriptions` (Web Push endpoint/keys) is the only delivery-related
  table beyond `reminders`/`notifications` themselves.
- **`habit_logs.log_date` and `daily_reviews.review_date` are Asia/Dhaka
  calendar dates**, stored as plain `DATE` — no timezone conversion needed
  or wanted, since a habit "day" is defined by the user's local calendar,
  not a UTC day boundary. Everything else with a time component
  (`due_at`, `fire_at`, `created_at`, `started_at`, ...) is UTC.
- **`reminders.source_type` allows `'habit'`**, but `habits` has no
  due-time/offset column in the actual schema — only `tasks` populate the
  `reminders` table today. The habit-side "streak at risk" push
  (`HabitService::rolloverStreaks()`, dispatched by `cron/run_jobs.php`'s
  `RolloverStreaks` job) is a separate direct notification, not a
  `reminders` row, because there's no natural `fire_at` for a habit.
- **`rate_limits`** uses a fixed-window design: `bucket_key` + `window_start`
  (floored to the bucket's window size) is the unique key, `hit_count`
  increments via `ON DUPLICATE KEY UPDATE`. No `blocked_until` column —
  the block *is* the remaining time in the current window.
- **Two DB users in production**: the app's runtime user gets only
  `SELECT, INSERT, UPDATE, DELETE`; a separate migrate user with DDL rights
  is used only by `database/migrate.php` (see docs/DEPLOYMENT.md).
