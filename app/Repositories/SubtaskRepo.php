<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class SubtaskRepo
{
    public function forTask(int $taskId): array
    {
        return Db::all('SELECT * FROM subtasks WHERE task_id = ? ORDER BY sort_order ASC, id ASC', [$taskId]);
    }

    public function create(int $taskId, string $title): int
    {
        $sort = (int) Db::value('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM subtasks WHERE task_id = ?', [$taskId]);
        return Db::insert('INSERT INTO subtasks (task_id, title, sort_order) VALUES (?, ?, ?)', [$taskId, $title, $sort]);
    }

    /** Toggle scoped through a join to tasks so a subtask can't be touched cross-user. */
    public function toggle(int $subtaskId, int $userId): bool
    {
        $row = Db::first(
            'SELECT s.id, s.completed_at FROM subtasks s JOIN tasks t ON t.id = s.task_id
             WHERE s.id = ? AND t.user_id = ?',
            [$subtaskId, $userId]
        );
        if ($row === null) {
            return false;
        }
        Db::exec(
            'UPDATE subtasks SET completed_at = ? WHERE id = ?',
            [$row['completed_at'] === null ? Db::nowUtc() : null, $subtaskId]
        );
        return true;
    }
}
