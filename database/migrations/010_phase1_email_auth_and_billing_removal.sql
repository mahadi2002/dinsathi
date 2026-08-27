-- Phase 1 rebuild: email+password auth replaces phone+OTP, and the entire
-- subscription/billing model (both the base 'planner' plan and the paid
-- 'sms_reminders' add-on) is removed — this is now a free, login-only
-- hobby app with push-only reminders. No production data exists yet, so
-- this is a straight ALTER/DROP addendum rather than a data migration.

-- ── users: mobile+operator → email+password ────────────────────────────
ALTER TABLE users
  ADD COLUMN email VARCHAR(191) NULL AFTER id,
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER email;

-- Backfill is not needed (no production rows), but guards against a
-- half-seeded dev DB where a row exists without an email yet.
UPDATE users SET email = CONCAT('user-', id, '@example.invalid') WHERE email IS NULL;
UPDATE users SET password_hash = '' WHERE password_hash IS NULL;

ALTER TABLE users
  MODIFY COLUMN email VARCHAR(191) NOT NULL,
  MODIFY COLUMN password_hash VARCHAR(255) NOT NULL,
  ADD CONSTRAINT uq_users_email UNIQUE (email);

ALTER TABLE users
  DROP COLUMN mobile_number,
  DROP COLUMN operator,
  DROP COLUMN sms_reminders_on;

-- otp_verifications served phone-OTP auth only — nothing else references it.
DROP TABLE IF EXISTS otp_verifications;

-- Password reset tokens — mirrors otp_verifications' shape (hash, not the
-- raw token, is stored; TTL + consumed_at, same as the OTP table it
-- replaces functionally).
CREATE TABLE password_resets (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT NOT NULL,
  token_hash  VARCHAR(255) NOT NULL,
  expires_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  consumed_at TIMESTAMP NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_password_resets_user ON password_resets (user_id);

-- ── Subscription/billing removal — both plans ──────────────────────────
DROP TABLE IF EXISTS billing_events;
DROP TABLE IF EXISTS subscriptions;

-- ── SMS reminders removal — push-only from here on ─────────────────────
DROP TABLE IF EXISTS sms_log;

ALTER TABLE reminders
  DROP COLUMN sms_status,
  DROP COLUMN sms_attempts;
