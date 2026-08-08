<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class PushSubscriptionRepo
{
    public function forUser(int $userId): array
    {
        return Db::all('SELECT * FROM push_subscriptions WHERE user_id = ?', [$userId]);
    }

    public function upsert(int $userId, string $endpoint, string $p256dh, string $auth): void
    {
        Db::exec(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE p256dh_key = VALUES(p256dh_key), auth_key = VALUES(auth_key), user_id = VALUES(user_id)',
            [$userId, $endpoint, $p256dh, $auth]
        );
    }

    public function deleteByEndpoint(string $endpoint): void
    {
        Db::exec('DELETE FROM push_subscriptions WHERE endpoint = ?', [$endpoint]);
    }

    public function delete(int $id): void
    {
        Db::exec('DELETE FROM push_subscriptions WHERE id = ?', [$id]);
    }

    public function allActiveUserIds(): array
    {
        return array_map('intval', array_column(Db::all('SELECT DISTINCT user_id FROM push_subscriptions'), 'user_id'));
    }
}
