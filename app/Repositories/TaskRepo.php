<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class TaskRepo
{
    public function find(int $id, int $userId): ?array
    {
        return Db::first('SELECT * FROM tasks WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function count(int $userId): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM tasks WHERE user_id = ? AND is_template = 0', [$userId]);
    }

    /** Non-template tasks whose due_at (UTC) falls in [$fromUtc, $toUtc), for a given list scope. */
    public function dueBetween(int $userId, string $fromUtc, string $toUtc): array
    {
        return Db::all(
            'SELECT t.*, l.name AS list_name, l.color_hex AS list_color
             FROM tasks t JOIN task_lists l ON l.id = t.list_id
             WHERE t.user_id = ? AND t.is_template = 0 AND t.due_at >= ? AND t.due_at < ?
             ORDER BY t.due_at ASC, FIELD(t.priority, "urgent","high","medium","low")',
            [$userId, $fromUtc, $toUtc]
        );
    }

    /** Overdue, incomplete tasks strictly before $beforeUtc. */
    public function overdueBefore(int $userId, string $beforeUtc): array
    {
        return Db::all(
            'SELECT t.*, l.name AS list_name, l.color_hex AS list_color
             FROM tasks t JOIN task_lists l ON l.id = t.list_id
             WHERE t.user_id = ? AND t.is_template = 0 AND t.completed_at IS NULL
               AND t.due_at IS NOT NULL AND t.due_at < ?
             ORDER BY t.due_at ASC',
            [$userId, $beforeUtc]
        );
    }

    public function forList(int $userId, ?int $listId, bool $includeCompleted = true): array
    {
        $sql = 'SELECT t.*, l.name AS list_name, l.color_hex AS list_color
                FROM tasks t JOIN task_lists l ON l.id = t.list_id
                WHERE t.user_id = ? AND t.is_template = 0';
        $params = [$userId];

        if ($listId !== null) {
            $sql .= ' AND t.list_id = ?';
            $params[] = $listId;
        }
        if (!$includeCompleted) {
            $sql .= ' AND t.completed_at IS NULL';
        }
        $sql .= ' ORDER BY (t.due_at IS NULL), t.due_at ASC, FIELD(t.priority,"urgent","high","medium","low")';

        return Db::all($sql, $params);
    }

    /** Combined list/tag/keyword filter for the tasks index — any of the three may be null/empty. */
    public function search(int $userId, ?int $listId, ?int $tagId, string $q): array
    {
        $sql = 'SELECT DISTINCT t.*, l.name AS list_name, l.color_hex AS list_color
                FROM tasks t
                JOIN task_lists l ON l.id = t.list_id';
        $params = [];

        if ($tagId !== null) {
            $sql .= ' JOIN task_tags tt ON tt.task_id = t.id AND tt.tag_id = ?';
            $params[] = $tagId;
        }

        $sql .= ' WHERE t.user_id = ? AND t.is_template = 0';
        $params[] = $userId;

        if ($listId !== null) {
            $sql .= ' AND t.list_id = ?';
            $params[] = $listId;
        }
        if ($q !== '') {
            $sql .= ' AND (t.title LIKE ? OR t.notes LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY (t.due_at IS NULL), t.due_at ASC, FIELD(t.priority,"urgent","high","medium","low")';

        return Db::all($sql, $params);
    }

    public function create(array $data): int
    {
        return Db::insert(
            'INSERT INTO tasks (user_id, list_id, title, notes, priority, due_at, is_template,
                                 recurrence_rule, parent_template_id, reminder_offset_min)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'], $data['list_id'], $data['title'], $data['notes'] ?? null,
                $data['priority'] ?? 'medium', $data['due_at'] ?? null, $data['is_template'] ?? 0,
                $data['recurrence_rule'] ?? null, $data['parent_template_id'] ?? null,
                $data['reminder_offset_min'] ?? null,
            ]
        );
    }

    public function update(int $id, int $userId, array $data): void
    {
        Db::exec(
            'UPDATE tasks SET title = ?, notes = ?, priority = ?, due_at = ?, list_id = ?,
                               recurrence_rule = ?, reminder_offset_min = ?
             WHERE id = ? AND user_id = ?',
            [
                $data['title'], $data['notes'] ?? null, $data['priority'] ?? 'medium', $data['due_at'] ?? null,
                $data['list_id'], $data['recurrence_rule'] ?? null, $data['reminder_offset_min'] ?? null,
                $id, $userId,
            ]
        );
    }

    public function delete(int $id, int $userId): void
    {
        Db::exec('DELETE FROM tasks WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function setCompleted(int $id, int $userId, bool $completed): void
    {
        Db::exec(
            'UPDATE tasks SET completed_at = ? WHERE id = ? AND user_id = ?',
            [$completed ? Db::nowUtc() : null, $id, $userId]
        );
    }

    /** Active recurring templates for a user (or all users when $userId is null — cron use). */
    public function templates(?int $userId = null): array
    {
        if ($userId !== null) {
            return Db::all('SELECT * FROM tasks WHERE user_id = ? AND is_template = 1', [$userId]);
        }
        return Db::all('SELECT * FROM tasks WHERE is_template = 1');
    }

    /** Existing generated instance dates (Dhaka-local 'Y-m-d', via DATE(CONVERT_TZ)) for a template. */
    public function instanceDatesForTemplate(int $templateId): array
    {
        $rows = Db::all(
            "SELECT DATE(CONVERT_TZ(due_at, '+00:00', '+06:00')) AS d FROM tasks WHERE parent_template_id = ?",
            [$templateId]
        );
        return array_map(static fn($r) => (string) $r['d'], $rows);
    }

    /** @return array{total: int, done: int} due_at counts in [$fromUtc, $toUtc) — used for both "today" and weekly/monthly insights. */
    public function countBetween(int $userId, string $fromUtc, string $toUtc): array
    {
        $total = (int) Db::value(
            'SELECT COUNT(*) FROM tasks WHERE user_id = ? AND is_template = 0 AND due_at >= ? AND due_at < ?',
            [$userId, $fromUtc, $toUtc]
        );
        $done = (int) Db::value(
            'SELECT COUNT(*) FROM tasks WHERE user_id = ? AND is_template = 0 AND due_at >= ? AND due_at < ? AND completed_at IS NOT NULL',
            [$userId, $fromUtc, $toUtc]
        );
        return ['total' => $total, 'done' => $done];
    }

    /** Tasks completed in [$fromUtc, $toUtc) by completed_at (not due_at) — a task finished this week even if it was overdue from earlier. */
    public function completedBetween(int $userId, string $fromUtc, string $toUtc): int
    {
        return (int) Db::value(
            'SELECT COUNT(*) FROM tasks WHERE user_id = ? AND is_template = 0 AND completed_at >= ? AND completed_at < ?',
            [$userId, $fromUtc, $toUtc]
        );
    }
}
