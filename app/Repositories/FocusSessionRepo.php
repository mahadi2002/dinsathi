<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class FocusSessionRepo
{
    public function create(int $userId, ?int $taskId, int $durationSec, string $startedAtUtc, string $endedAtUtc): int
    {
        return Db::insert(
            'INSERT INTO focus_sessions (user_id, task_id, duration_sec, started_at, ended_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $taskId, $durationSec, $startedAtUtc, $endedAtUtc]
        );
    }

    public function recentForUser(int $userId, int $limit = 20): array
    {
        return Db::all(
            'SELECT fs.*, t.title AS task_title FROM focus_sessions fs
             LEFT JOIN tasks t ON t.id = fs.task_id
             WHERE fs.user_id = ? ORDER BY fs.started_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public function totalSecondsBetween(int $userId, string $fromUtc, string $toUtc): int
    {
        return (int) Db::value(
            'SELECT COALESCE(SUM(duration_sec), 0) FROM focus_sessions WHERE user_id = ? AND started_at >= ? AND started_at < ?',
            [$userId, $fromUtc, $toUtc]
        );
    }

    /** Full focus-session history for a user, no limit — used by CSV export. */
    public function allForUser(int $userId): array
    {
        return Db::all(
            'SELECT fs.*, t.title AS task_title FROM focus_sessions fs
             LEFT JOIN tasks t ON t.id = fs.task_id
             WHERE fs.user_id = ? ORDER BY fs.started_at DESC',
            [$userId]
        );
    }

    public function countBetween(int $userId, string $fromUtc, string $toUtc): int
    {
        return (int) Db::value(
            'SELECT COUNT(*) FROM focus_sessions WHERE user_id = ? AND started_at >= ? AND started_at < ?',
            [$userId, $fromUtc, $toUtc]
        );
    }
}
