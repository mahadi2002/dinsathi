-- Addendum: quantity-based habits.
--
-- Habits were boolean check-in only (habit_logs.completed). Adds optional
-- target_quantity/unit on habits (nullable — existing boolean habits keep
-- working unchanged when target_quantity IS NULL) and a running quantity
-- value on habit_logs, so a quantity habit ("8 glasses of water", "30 min
-- reading") only counts as done for the day once the target is met.
-- Streaks stay computed on read from habit_logs.completed exactly as
-- before — HabitService just decides *when* completed flips to 1 now.

ALTER TABLE habits
  ADD COLUMN target_quantity INT NULL,
  ADD COLUMN unit VARCHAR(20) NULL;

ALTER TABLE habit_logs
  ADD COLUMN quantity INT NULL;
