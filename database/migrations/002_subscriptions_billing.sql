CREATE TABLE subscriptions (
  id             BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id        BIGINT NOT NULL,
  gateway        TEXT NOT NULL DEFAULT 'mock' CHECK (gateway IN ('mock','bdapps')),
  status         VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','unsubscribed','suspended','expired')),
  daily_amount   DECIMAL(6,2) NOT NULL DEFAULT 2.78,
  started_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  next_charge_at TIMESTAMP NULL,
  ended_at       TIMESTAMP NULL,
  external_ref   VARCHAR(191) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_user_status ON subscriptions (user_id, status);

CREATE TABLE billing_events (
  id                BIGINT AUTO_INCREMENT PRIMARY KEY,
  subscription_id   BIGINT NOT NULL,
  event_type        TEXT NOT NULL CHECK (event_type IN ('charge_success','charge_failed','subscribe','unsubscribe','renewal_check')),
  amount            DECIMAL(6,2) NULL,
  gateway_response  JSON NULL,
  occurred_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);
