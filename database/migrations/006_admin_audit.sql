CREATE TABLE admin_users (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          TEXT NOT NULL DEFAULT 'support' CHECK (role IN ('support','superadmin')),
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_log (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_id    BIGINT NULL,
  action      VARCHAR(100) NOT NULL,
  target_type VARCHAR(50) NULL,
  target_id   BIGINT NULL,
  detail      JSON NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);
