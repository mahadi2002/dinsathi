<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class NotificationRepo
{
    public function create(int $userId, string $type, string $title, string $body): int
    {
        return Db::insert(
            'INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)',
            [$userId, $type, $title, $body]
        );
    }

    public function recentForUser(int $userId, int $limit = 20): array
    {
        return Db::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ?', [$userId, $limit]);
    }

    public function unreadCount(int $userId): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public function markAllRead(int $userId): void
    {
        Db::exec('UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public function createForAllSubscribed(string $type, string $title, string $body): int
    {
        $userIds = array_column(
            Db::all("SELECT DISTINCT user_id FROM subscriptions WHERE status = 'active'"),
            'user_id'
        );
        foreach ($userIds as $uid) {
            $this->create((int) $uid, $type, $title, $body);
        }
        return count($userIds);
    }
}
