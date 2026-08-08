# Database

Schema source of truth: `database/migrations/*.sql`, applied in numeric
order by `database/migrate.php`. Matches `02-SCHEMA.sql` exactly except
`DEFAULT UTC_TIMESTAMP()` → `DEFAULT CURRENT_TIMESTAMP` (MariaDB 10.4
rejects a bare function-call default outside parentheses on `ON UPDATE`;
the connection forces `SET time_zone = '+00:00'` everywhere, so
`CURRENT_TIMESTAMP` already evaluates in UTC — same stored value, portable
syntax).

## Tables, grouped by migration file

**001_users_auth** — `users`, `otp_verifications`, `sessions`, `rate_limits`
**002_subscriptions_billing** — `subscriptions`, `billing_events`
**003_planner_core** — `task_lists`, `tags`, `tasks`, `task_tags`, `subtasks`
**004_habits_focus_review** — `habits`, `habit_logs`, `focus_sessions`, `daily_reviews`
**005_reminders_notifications_jobs** — `reminders`, `push_subscriptions`, `sms_log`, `notifications`, `jobs`
**006_admin_audit** — `admin_users`, `audit_log`

## Notes worth knowing

- **`mobile_number` is stored in plain `VARCHAR(11)`**, not the two-secret
  encrypt+blind-index pattern used elsewhere in this workspace (PLAYBOOK.txt
  §5). This is a deliberate divergence dictated by DinSathi's own locked
  schema (`02-SCHEMA.sql`, given explicitly for this project) rather than an
  oversight — the general workspace pattern applies where a project's own
  spec is silent, and this one isn't. Numbers are still masked in every
  admin list view (`mask_msisdn()`) with the full number visible only on
  the audit-logged single-user detail page.
- **`habit_logs.log_date` and `daily_reviews.review_date` are Asia/Dhaka
  calendar dates**, stored as plain `DATE` — no timezone conversion needed
  or wanted, since a habit "day" is defined by the user's local calendar,
  not a UTC day boundary. Everything else with a time component
  (`due_at`, `fire_at`, `created_at`, `started_at`, ...) is UTC.
- **`reminders.source_type` allows `'habit'`** per the original spec intent,
  but `habits` has no due-time/offset column in the actual schema — only
  `tasks` populate the `reminders` table today. The habit-side "streak at
  risk" push (`RolloverStreaks` job) is a separate direct notification, not
  a `reminders` row, because there's no natural `fire_at` for a habit.
- **`rate_limits`** uses a fixed-window design: `bucket_key` + `window_start`
  (floored to the bucket's window size) is the unique key, `hit_count`
  increments via `ON DUPLICATE KEY UPDATE`. No `blocked_until` column —
  the block *is* the remaining time in the current window.
- **Two DB users in production**: the app's runtime user gets only
  `SELECT, INSERT, UPDATE, DELETE`; a separate migrate user with DDL rights
  is used only by `database/migrate.php` (see docs/DEPLOYMENT.md).
