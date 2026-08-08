CREATE TABLE habits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  icon        VARCHAR(20) NULL,
  active_days VARCHAR(20) NOT NULL DEFAULT 'MO,TU,WE,TH,FR,SA,SU',
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE habit_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  habit_id    BIGINT UNSIGNED NOT NULL,
  log_date    DATE NOT NULL,
  completed   TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
  UNIQUE KEY uq_habit_date (habit_id, log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE focus_sessions (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  task_id     BIGINT UNSIGNED NULL,
  duration_sec INT UNSIGNED NOT NULL,
  started_at  DATETIME NOT NULL,
  ended_at    DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daily_reviews (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  review_date DATE NOT NULL,
  note       TEXT NULL,
  mood       ENUM('great','good','okay','tough') NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_date (user_id, review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
