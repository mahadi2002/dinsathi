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
             WHERE habit_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND completed = 1
             ORDER BY log_date DESC',
            [$habitId, $days]
        );
    }

    public function history(int $habitId, int $days = 90): array
    {
        return Db::all(
            'SELECT log_date, completed FROM habit_logs
             WHERE habit_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY log_date ASC',
            [$habitId, $days]
        );
    }
}
