CREATE TABLE task_lists (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT NOT NULL,
  name        VARCHAR(100) NOT NULL,
  color_hex   CHAR(7) NOT NULL DEFAULT '#2E3A87',
  is_default  SMALLINT NOT NULL DEFAULT 0,
  sort_order  INTEGER NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE tags (
  id       BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id  BIGINT NOT NULL,
  name     VARCHAR(50) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uq_user_tag UNIQUE (user_id, name)
);

CREATE TABLE tasks (
  id                   BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id              BIGINT NOT NULL,
  list_id              BIGINT NOT NULL,
  title                VARCHAR(255) NOT NULL,
  notes                TEXT NULL,
  priority             TEXT NOT NULL DEFAULT 'medium' CHECK (priority IN ('low','medium','high','urgent')),
  due_at               TIMESTAMP NULL,
  completed_at         TIMESTAMP NULL,
  is_template          SMALLINT NOT NULL DEFAULT 0,
  recurrence_rule      VARCHAR(100) NULL,
  parent_template_id   BIGINT NULL,
  reminder_offset_min  INTEGER NULL CHECK (reminder_offset_min >= 0),
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (list_id) REFERENCES task_lists(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_template_id) REFERENCES tasks(id) ON DELETE CASCADE
);
CREATE INDEX idx_user_due ON tasks (user_id, due_at);
CREATE INDEX idx_template ON tasks (parent_template_id);

CREATE TABLE task_tags (
  task_id BIGINT NOT NULL,
  tag_id  BIGINT NOT NULL,
  PRIMARY KEY (task_id, tag_id),
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE subtasks (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  task_id      BIGINT NOT NULL,
  title        VARCHAR(255) NOT NULL,
  completed_at TIMESTAMP NULL,
  sort_order   INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
