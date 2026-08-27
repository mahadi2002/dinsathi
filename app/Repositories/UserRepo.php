<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class UserRepo
{
    public function find(int $id): ?array
    {
        return Db::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return Db::first('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function create(string $email, string $passwordHash): int
    {
        return Db::insert(
            'INSERT INTO users (email, password_hash, status) VALUES (?, ?, ?)',
            [$email, $passwordHash, 'active']
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        Db::exec('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $id]);
    }

    public function updateProfile(int $id, ?string $displayName): void
    {
        Db::exec('UPDATE users SET display_name = ? WHERE id = ?', [$displayName, $id]);
    }

    public function updateNotificationPrefs(int $id, string $quietStart, string $quietEnd): void
    {
        Db::exec(
            'UPDATE users SET push_quiet_start = ?, push_quiet_end = ? WHERE id = ?',
            [$quietStart, $quietEnd, $id]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        Db::exec('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    /** @return array{data: array, total: int} */
    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $offset = max(0, $page - 1) * $perPage;

        if ($search !== '') {
            $like = '%' . $search . '%';
            $total = (int) Db::value('SELECT COUNT(*) FROM users WHERE email LIKE ?', [$like]);
            $rows  = Db::all(
                'SELECT * FROM users WHERE email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?',
                [$like, $perPage, $offset]
            );
        } else {
            $total = (int) Db::value('SELECT COUNT(*) FROM users');
            $rows  = Db::all('SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?', [$perPage, $offset]);
        }

        return ['data' => $rows, 'total' => $total];
    }

    public function count(): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM users');
    }
}
