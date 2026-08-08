CREATE TABLE users (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mobile_number     VARCHAR(11) NOT NULL UNIQUE,        -- 01XXXXXXXXX
  operator          ENUM('robi','airtel') NOT NULL,
  display_name      VARCHAR(100) NULL,
  status            ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  push_quiet_start  TIME NOT NULL DEFAULT '22:00:00',
  push_quiet_end    TIME NOT NULL DEFAULT '07:00:00',
  sms_reminders_on  TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE otp_verifications (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mobile_number VARCHAR(11) NOT NULL,
  purpose       ENUM('register','login','subscribe') NOT NULL,
  otp_hash      VARCHAR(255) NOT NULL,
  expires_at    DATETIME NOT NULL,
  consumed_at   DATETIME NULL,
  attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mobile_purpose (mobile_number, purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
  id            CHAR(64) PRIMARY KEY,
  user_id       BIGINT UNSIGNED NULL,
  admin_id      BIGINT UNSIGNED NULL,
  payload       MEDIUMTEXT NOT NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    VARCHAR(255) NULL,
  last_active   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at    DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket_key  VARCHAR(191) NOT NULL,
  window_start DATETIME NOT NULL,
  hit_count   INT UNSIGNED NOT NULL DEFAULT 1,
  UNIQUE KEY uq_bucket_window (bucket_key, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
