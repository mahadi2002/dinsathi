CREATE TABLE reminders (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  source_type ENUM('task','habit') NOT NULL,
  source_id   BIGINT UNSIGNED NOT NULL,
  fire_at     DATETIME NOT NULL,
  status      ENUM('pending','dispatched','failed','skipped_quiet_hours') NOT NULL DEFAULT 'pending',
  push_status ENUM('n/a','sent','failed') NOT NULL DEFAULT 'n/a',
  sms_status  ENUM('n/a','sent','failed','retrying') NOT NULL DEFAULT 'n/a',
  sms_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_fire_status (fire_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE push_subscriptions (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  endpoint    VARCHAR(500) NOT NULL,
  p256dh_key  VARCHAR(255) NOT NULL,
  auth_key    VARCHAR(255) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  type        ENUM('reminder','streak_risk','broadcast','system') NOT NULL,
  title       VARCHAR(150) NOT NULL,
  body        VARCHAR(255) NOT NULL,
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_name    VARCHAR(100) NOT NULL,
  status      ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
  run_at      DATETIME NOT NULL,
  locked_at   DATETIME NULL,
  finished_at DATETIME NULL,
  error       TEXT NULL,
  INDEX idx_status_run (status, run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
