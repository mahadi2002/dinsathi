CREATE TABLE users (
  id                BIGINT AUTO_INCREMENT PRIMARY KEY,
  mobile_number     VARCHAR(11) NOT NULL UNIQUE,        -- 01XXXXXXXXX
  operator          TEXT NOT NULL CHECK (operator IN ('robi','airtel')),
  display_name      VARCHAR(100) NULL,
  status            TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','suspended','deleted')),
  push_quiet_start  TIME NOT NULL DEFAULT '22:00:00',
  push_quiet_end    TIME NOT NULL DEFAULT '07:00:00',
  sms_reminders_on  SMALLINT NOT NULL DEFAULT 1,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE otp_verifications (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  mobile_number VARCHAR(11) NOT NULL,
  purpose       VARCHAR(20) NOT NULL CHECK (purpose IN ('register','login','subscribe')),
  otp_hash      VARCHAR(255) NOT NULL,
  expires_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  consumed_at   TIMESTAMP NULL,
  attempt_count SMALLINT NOT NULL DEFAULT 0 CHECK (attempt_count >= 0),
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_mobile_purpose ON otp_verifications (mobile_number, purpose);

CREATE TABLE sessions (
  id            CHAR(64) PRIMARY KEY,
  user_id       BIGINT NULL,
  admin_id      BIGINT NULL,
  payload       TEXT NOT NULL,
  ip_address    VARCHAR(45) NULL,
  user_agent    VARCHAR(255) NULL,
  last_active   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE rate_limits (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  bucket_key   VARCHAR(191) NOT NULL,
  window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  hit_count    INTEGER NOT NULL DEFAULT 1 CHECK (hit_count >= 0),
  CONSTRAINT uq_bucket_window UNIQUE (bucket_key, window_start)
);
