CREATE TABLE habits (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT NOT NULL,
  name        VARCHAR(100) NOT NULL,
  icon        VARCHAR(20) NULL,
  active_days VARCHAR(20) NOT NULL DEFAULT 'MO,TU,WE,TH,FR,SA,SU',
  is_archived SMALLINT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE habit_logs (
  id        BIGINT AUTO_INCREMENT PRIMARY KEY,
  habit_id  BIGINT NOT NULL,
  log_date  DATE NOT NULL,
  completed SMALLINT NOT NULL DEFAULT 1,
  FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
  CONSTRAINT uq_habit_date UNIQUE (habit_id, log_date)
);

CREATE TABLE focus_sessions (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT NOT NULL,
  task_id      BIGINT NULL,
  duration_sec INTEGER NOT NULL CHECK (duration_sec >= 0),
  started_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

CREATE TABLE daily_reviews (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT NOT NULL,
  review_date DATE NOT NULL,
  note        TEXT NULL,
  mood        TEXT NULL CHECK (mood IN ('great','good','okay','tough')),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uq_user_date UNIQUE (user_id, review_date)
);
