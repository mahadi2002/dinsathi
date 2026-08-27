<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class HabitLogRepo
{
    public function isDoneOn(int $habitId, string $dhakaDate): bool
    {
        return Db::value(
            'SELECT completed FROM habit_logs WHERE habit_id = ? AND log_date = ?',
            [$habitId, $dhakaDate]
        ) == 1;
    }

    public function toggle(int $habitId, string $dhakaDate): bool
    {
        $existing = Db::first('SELECT id, completed FROM habit_logs WHERE habit_id = ? AND log_date = ?', [$habitId, $dhakaDate]);

        if ($existing === null) {
            Db::insert('INSERT INTO habit_logs (habit_id, log_date, completed) VALUES (?, ?, 1)', [$habitId, $dhakaDate]);
            return true;
        }

        $newState = $existing['completed'] == 1 ? 0 : 1;
        Db::exec('UPDATE habit_logs SET completed = ? WHERE id = ?', [$newState, $existing['id']]);
        return $newState === 1;
    }

    /** Recent logs for streak computation and calendar rendering, most recent first. */
    public function recent(int $habitId, int $days): array
    {
        return Db::all(
            'SELECT log_date, completed FROM habit_logs
             WHERE habit_id = ? AND log_date >= (CURRENT_DATE - INTERVAL ? DAY) AND completed = 1
             ORDER BY log_date DESC',
            [$habitId, $days]
        );
    }

    public function history(int $habitId, int $days = 90): array
    {
        return Db::all(
            'SELECT log_date, completed, quantity FROM habit_logs
             WHERE habit_id = ? AND log_date >= (CURRENT_DATE - INTERVAL ? DAY)
             ORDER BY log_date ASC',
            [$habitId, $days]
        );
    }

    /** Running quantity logged for a quantity habit on a given day (0 if nothing logged yet). */
    public function quantityOn(int $habitId, string $dhakaDate): int
    {
        return (int) (Db::value(
            'SELECT quantity FROM habit_logs WHERE habit_id = ? AND log_date = ?',
            [$habitId, $dhakaDate]
        ) ?? 0);
    }

    /** Upserts today's running quantity + the "done" flag HabitService computed against the target. */
    public function setQuantity(int $habitId, string $dhakaDate, int $quantity, bool $completed): void
    {
        $existing = Db::first('SELECT id FROM habit_logs WHERE habit_id = ? AND log_date = ?', [$habitId, $dhakaDate]);

        if ($existing === null) {
            Db::insert(
                'INSERT INTO habit_logs (habit_id, log_date, completed, quantity) VALUES (?, ?, ?, ?)',
                [$habitId, $dhakaDate, $completed ? 1 : 0, $quantity]
            );
            return;
        }

        Db::exec('UPDATE habit_logs SET quantity = ?, completed = ? WHERE id = ?', [$quantity, $completed ? 1 : 0, $existing['id']]);
    }

    /** All check-in history across every habit a user owns, most recent first — used by CSV export. */
    public function allForUser(int $userId): array
    {
        return Db::all(
            'SELECT hl.log_date, hl.completed, hl.quantity, h.name AS habit_name, h.target_quantity, h.unit
             FROM habit_logs hl
             JOIN habits h ON h.id = hl.habit_id
             WHERE h.user_id = ?
             ORDER BY hl.log_date DESC, h.name ASC',
            [$userId]
        );
    }
}
