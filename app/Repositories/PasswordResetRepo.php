<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class PasswordResetRepo
{
    public function create(int $userId, string $tokenHash, int $ttlSeconds): int
    {
        Db::exec('DELETE FROM password_resets WHERE user_id = ? AND consumed_at IS NULL', [$userId]);

        return Db::insert(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ? SECOND)',
            [$userId, $tokenHash, $ttlSeconds]
        );
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        return Db::first(
            "SELECT * FROM password_resets WHERE token_hash = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1",
            [$tokenHash]
        );
    }

    public function consume(int $id): void
    {
        Db::exec('UPDATE password_resets SET consumed_at = NOW() WHERE id = ?', [$id]);
    }
}
