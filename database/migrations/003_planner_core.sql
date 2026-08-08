CREATE TABLE task_lists (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  color_hex   CHAR(7) NOT NULL DEFAULT '#2E3A87',
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
  due_at          DATETIME NULL,
  completed_at    DATETIME NULL,
  is_template     TINYINT(1) NOT NULL DEFAULT 0,
  recurrence_rule VARCHAR(100) NULL,
  parent_template_id BIGINT UNSIGNED NULL,
  reminder_offset_min INT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
