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

    public function findByMobile(string $mobile): ?array
    {
        return Db::first('SELECT * FROM users WHERE mobile_number = ?', [$mobile]);
    }

    public function create(string $mobile, string $operator): int
    {
        return Db::insert(
            'INSERT INTO users (mobile_number, operator, status) VALUES (?, ?, ?)',
            [$mobile, $operator, 'active']
        );
    }

    public function updateProfile(int $id, ?string $displayName): void
    {
        Db::exec('UPDATE users SET display_name = ? WHERE id = ?', [$displayName, $id]);
    }

    public function updateNotificationPrefs(int $id, string $quietStart, string $quietEnd, bool $smsOn): void
    {
        Db::exec(
            'UPDATE users SET push_quiet_start = ?, push_quiet_end = ?, sms_reminders_on = ? WHERE id = ?',
            [$quietStart, $quietEnd, $smsOn ? 1 : 0, $id]
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
            $total = (int) Db::value('SELECT COUNT(*) FROM users WHERE mobile_number LIKE ?', [$like]);
            $rows  = Db::all(
                'SELECT * FROM users WHERE mobile_number LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?',
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
