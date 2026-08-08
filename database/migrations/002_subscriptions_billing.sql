CREATE TABLE subscriptions (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        BIGINT UNSIGNED NOT NULL,
  gateway        ENUM('mock','bdapps') NOT NULL DEFAULT 'mock',
  status         ENUM('active','unsubscribed','suspended','expired') NOT NULL DEFAULT 'active',
  daily_amount   DECIMAL(6,2) NOT NULL DEFAULT 2.78,
  started_at     DATETIME NOT NULL,
  next_charge_at DATETIME NULL,
  ended_at       DATETIME NULL,
  external_ref   VARCHAR(191) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE billing_events (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subscription_id BIGINT UNSIGNED NOT NULL,
  event_type      ENUM('charge_success','charge_failed','subscribe','unsubscribe','renewal_check') NOT NULL,
  amount          DECIMAL(6,2) NULL,
  gateway_response JSON NULL,
  occurred_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
