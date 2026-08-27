CREATE TABLE reminders (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT NOT NULL,
  source_type  TEXT NOT NULL CHECK (source_type IN ('task','habit')),
  source_id    BIGINT NOT NULL,
  fire_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status       VARCHAR(30) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','dispatched','failed','skipped_quiet_hours')),
  push_status  TEXT NOT NULL DEFAULT 'n/a' CHECK (push_status IN ('n/a','sent','failed')),
  sms_status   TEXT NOT NULL DEFAULT 'n/a' CHECK (sms_status IN ('n/a','sent','failed','retrying')),
  sms_attempts SMALLINT NOT NULL DEFAULT 0 CHECK (sms_attempts >= 0),
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_fire_status ON reminders (fire_at, status);

CREATE TABLE push_subscriptions (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT NOT NULL,
  endpoint    VARCHAR(500) NOT NULL,
  p256dh_key  VARCHAR(255) NOT NULL,
  auth_key    VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  -- 191-char index prefix on `endpoint` to stay under InnoDB's index
  -- key-length limit under COMPACT row format with utf8mb4.
  CONSTRAINT uq_endpoint UNIQUE (endpoint(191))
);

CREATE TABLE sms_log (
  id                BIGINT AUTO_INCREMENT PRIMARY KEY,
  reminder_id       BIGINT NULL,
  mobile_number     VARCHAR(11) NOT NULL,
  message           VARCHAR(200) NOT NULL,
  gateway           TEXT NOT NULL DEFAULT 'mock' CHECK (gateway IN ('mock','provider')),
  status            TEXT NOT NULL DEFAULT 'queued' CHECK (status IN ('queued','sent','failed')),
  gateway_response  JSON NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT NOT NULL,
  type        TEXT NOT NULL CHECK (type IN ('reminder','streak_risk','broadcast','system')),
  title       VARCHAR(150) NOT NULL,
  body        VARCHAR(255) NOT NULL,
  read_at     TIMESTAMP NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE jobs (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  job_name    VARCHAR(100) NOT NULL,
  status      VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','running','done','failed')),
  run_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  locked_at   TIMESTAMP NULL,
  finished_at TIMESTAMP NULL,
  error       TEXT NULL
);
CREATE INDEX idx_status_run ON jobs (status, run_at);
