-- Addendum: two-tier subscription model.
--
-- Business model change (post-launch decision): there is no free tier at
-- all — every planner feature requires an active 'planner' subscription,
-- and SMS delivery on top of that requires a second, separate
-- 'sms_reminders' subscription (push notifications stay bundled into the
-- planner plan; SMS costs real money per message, so it's priced and sold
-- separately). A user may hold zero, one, or both plans active at once —
-- `subscriptions` was one-row-per-user before; `plan` makes it one row per
-- (user, plan). Existing rows (all pre-dating this decision) are the base
-- planner plan.

ALTER TABLE subscriptions
  ADD COLUMN plan ENUM('planner','sms_reminders') NOT NULL DEFAULT 'planner' AFTER user_id,
  DROP INDEX idx_user_status,
  ADD INDEX idx_user_plan_status (user_id, plan, status);
