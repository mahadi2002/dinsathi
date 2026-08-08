<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class ReminderRepo
{
    public function create(int $userId, string $sourceType, int $sourceId, string $fireAtUtc): int
    {
        return Db::insert(
            'INSERT INTO reminders (user_id, source_type, source_id, fire_at) VALUES (?, ?, ?, ?)',
            [$userId, $sourceType, $sourceId, $fireAtUtc]
        );
    }

    /** Remove not-yet-fired reminders for a source (task edited/deleted → reschedule). */
    public function cancelPendingFor(string $sourceType, int $sourceId): void
    {
        Db::exec(
            "DELETE FROM reminders WHERE source_type = ? AND source_id = ? AND status = 'pending'",
            [$sourceType, $sourceId]
        );
    }

    public function duePending(string $nowUtc): array
    {
        return Db::all(
            "SELECT * FROM reminders WHERE status = 'pending' AND fire_at <= ? ORDER BY fire_at ASC LIMIT 500",
            [$nowUtc]
        );
    }

    /** Row-locked claim so overlapping cron runs can't double-dispatch. */
    public function claim(int $id): bool
    {
        return Db::exec("UPDATE reminders SET status = 'dispatched' WHERE id = ? AND status = 'pending'", [$id]) === 1;
    }

    public function markQuietSkipped(int $id): void
    {
        Db::exec("UPDATE reminders SET status = 'skipped_quiet_hours' WHERE id = ?", [$id]);
    }

    public function requeueAfterQuietHours(int $id, string $newFireAtUtc): void
    {
        Db::exec("UPDATE reminders SET fire_at = ?, status = 'pending' WHERE id = ?", [$newFireAtUtc, $id]);
    }

    public function setPushStatus(int $id, string $status): void
    {
        Db::exec('UPDATE reminders SET push_status = ? WHERE id = ?', [$status, $id]);
    }

    public function setSmsStatus(int $id, string $status, ?int $incrementAttempts = null): void
    {
        if ($incrementAttempts) {
            Db::exec('UPDATE reminders SET sms_status = ?, sms_attempts = sms_attempts + 1 WHERE id = ?', [$status, $id]);
        } else {
            Db::exec('UPDATE reminders SET sms_status = ? WHERE id = ?', [$status, $id]);
        }
    }

    public function retryableSms(int $maxAttempts): array
    {
        return Db::all(
            "SELECT * FROM reminders WHERE sms_status = 'retrying' AND sms_attempts < ? LIMIT 200",
            [$maxAttempts]
        );
    }
}
