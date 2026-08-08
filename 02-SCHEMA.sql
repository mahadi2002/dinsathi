-- =====================================================================
-- DinSathi (দিনসাথী) — MySQL Schema
-- Charset: utf8mb4_unicode_ci throughout. Engine: InnoDB throughout.
-- All timestamps stored UTC; convert to Asia/Dhaka at the view layer.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 01_users_auth.sql
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mobile_number     VARCHAR(11) NOT NULL UNIQUE,        -- 01XXXXXXXXX
  operator          ENUM('robi','airtel') NOT NULL,
  display_name      VARCHAR(100) NULL,
  status            ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  push_quiet_start  TIME NOT NULL DEFAULT '22:00:00',
  push_quiet_end    TIME NOT NULL DEFAULT '07:00:00',
  sms_reminders_on  TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  updated_at        DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE otp_verifications (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mobile_number VARCHAR(11) NOT NULL,
  purpose       ENUM('register','login','subscribe') NOT NULL,
  otp_hash      VARCHAR(255) NOT NULL,
  expires_at    DATETIME NOT NULL,
  consumed_at   DATETIME NULL,
  attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  INDEX idx_mobile_purpose (mobile_number, purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
  id            CHAR(64) PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  admin_id      BIGINT UNSIGNED NULL,
  payload       MEDIUMTEXT NOT NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    VARCHAR(255) NULL,
  last_active   DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  expires_at    DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket_key  VARCHAR(191) NOT NULL,   -- e.g. 'otp:01712345678' or 'login:ip:1.2.3.4'
  window_start DATETIME NOT NULL,
  hit_count   INT UNSIGNED NOT NULL DEFAULT 1,
  UNIQUE KEY uq_bucket_window (bucket_key, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 02_subscriptions_billing.sql
-- ---------------------------------------------------------------------
CREATE TABLE subscriptions (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        BIGINT UNSIGNED NOT NULL,
  gateway        ENUM('mock','bdapps') NOT NULL DEFAULT 'mock',
  status         ENUM('active','unsubscribed','suspended','expired') NOT NULL DEFAULT 'active',
  daily_amount   DECIMAL(6,2) NOT NULL DEFAULT 2.78,
  started_at     DATETIME NOT NULL,
  next_charge_at DATETIME NULL,
  ended_at       DATETIME NULL,
  external_ref   VARCHAR(191) NULL,   -- BDApps subscription/transaction ref
  created_at     DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  updated_at     DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE billing_events (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subscription_id BIGINT UNSIGNED NOT NULL,
  event_type      ENUM('charge_success','charge_failed','subscribe','unsubscribe','renewal_check') NOT NULL,
  amount          DECIMAL(6,2) NULL,
  gateway_response JSON NULL,
  occurred_at     DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 03_planner_core.sql
-- ---------------------------------------------------------------------
CREATE TABLE task_lists (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  color_hex   CHAR(7) NOT NULL DEFAULT '#2E3A87',
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
  id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id  BIGINT UNSIGNED NOT NULL,
  name     VARCHAR(50) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_tag (user_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tasks (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  list_id         BIGINT UNSIGNED NOT NULL,
  title           VARCHAR(255) NOT NULL,
  notes           TEXT NULL,
  priority        ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  due_at          DATETIME NULL,          -- UTC
  completed_at    DATETIME NULL,
  is_template     TINYINT(1) NOT NULL DEFAULT 0,   -- recurring master row
  recurrence_rule VARCHAR(100) NULL,               -- e.g. 'WEEKLY:MO,WE,FR'
  parent_template_id BIGINT UNSIGNED NULL,          -- set on generated instances
  reminder_offset_min INT UNSIGNED NULL,             -- minutes before due_at
  created_at      DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  updated_at      DATETIME NOT NULL DEFAULT UTC_TIMESTAMP() ON UPDATE UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (list_id) REFERENCES task_lists(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_template_id) REFERENCES tasks(id) ON DELETE CASCADE,
  INDEX idx_user_due (user_id, due_at),
  INDEX idx_template (parent_template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_tags (
  task_id BIGINT UNSIGNED NOT NULL,
  tag_id  BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (task_id, tag_id),
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subtasks (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id      BIGINT UNSIGNED NOT NULL,
  title        VARCHAR(255) NOT NULL,
  completed_at DATETIME NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 04_habits_focus_review.sql
-- ---------------------------------------------------------------------
CREATE TABLE habits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  icon        VARCHAR(20) NULL,
  active_days VARCHAR(20) NOT NULL DEFAULT 'MO,TU,WE,TH,FR,SA,SU',
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE habit_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  habit_id    BIGINT UNSIGNED NOT NULL,
  log_date    DATE NOT NULL,          -- Asia/Dhaka calendar date, not UTC
  completed   TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
  UNIQUE KEY uq_habit_date (habit_id, log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE focus_sessions (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  task_id     BIGINT UNSIGNED NULL,
  duration_sec INT UNSIGNED NOT NULL,
  started_at  DATETIME NOT NULL,
  ended_at    DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daily_reviews (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  review_date DATE NOT NULL,
  note       TEXT NULL,
  mood       ENUM('great','good','okay','tough') NULL,
  created_at DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_date (user_id, review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 05_reminders_notifications_jobs.sql
-- ---------------------------------------------------------------------
CREATE TABLE reminders (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  source_type ENUM('task','habit') NOT NULL,
  source_id   BIGINT UNSIGNED NOT NULL,
  fire_at     DATETIME NOT NULL,       -- UTC
  status      ENUM('pending','dispatched','failed','skipped_quiet_hours') NOT NULL DEFAULT 'pending',
  push_status ENUM('n/a','sent','failed') NOT NULL DEFAULT 'n/a',
  sms_status  ENUM('n/a','sent','failed','retrying') NOT NULL DEFAULT 'n/a',
  sms_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_fire_status (fire_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  endpoint    VARCHAR(500) NOT NULL,
  p256dh_key  VARCHAR(255) NOT NULL,
  auth_key    VARCHAR(255) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_endpoint (endpoint(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sms_log (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reminder_id BIGINT UNSIGNED NULL,
  mobile_number VARCHAR(11) NOT NULL,
  message     VARCHAR(200) NOT NULL,
  gateway     ENUM('mock','provider') NOT NULL DEFAULT 'mock',
  status      ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  gateway_response JSON NULL,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  type        ENUM('reminder','streak_risk','broadcast','system') NOT NULL,
  title       VARCHAR(150) NOT NULL,
  body        VARCHAR(255) NOT NULL,
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_name    VARCHAR(100) NOT NULL,   -- DispatchReminders, GenerateRecurringInstances, etc.
  status      ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
  run_at      DATETIME NOT NULL,
  locked_at   DATETIME NULL,
  finished_at DATETIME NULL,
  error       TEXT NULL,
  INDEX idx_status_run (status, run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 06_admin_audit.sql
-- ---------------------------------------------------------------------
CREATE TABLE admin_users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('support','superadmin') NOT NULL DEFAULT 'support',
  created_at    DATETIME NOT NULL DEFAULT UTC_TIMESTAMP()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    BIGINT UNSIGNED NULL,
  action      VARCHAR(100) NOT NULL,
  target_type VARCHAR(50) NULL,
  target_id   BIGINT UNSIGNED NULL,
  detail      JSON NULL,
  created_at  DATETIME NOT NULL DEFAULT UTC_TIMESTAMP(),
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 07_seed.sql — minimal dev seed
-- ---------------------------------------------------------------------
INSERT INTO admin_users (email, password_hash, role)
VALUES ('admin@dinsathi.local', '$2y$12$REPLACE_WITH_REAL_HASH', 'superadmin');
