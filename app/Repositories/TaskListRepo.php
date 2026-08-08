<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class TaskListRepo
{
    public function forUser(int $userId): array
    {
        return Db::all('SELECT * FROM task_lists WHERE user_id = ? ORDER BY sort_order ASC, id ASC', [$userId]);
    }

    public function find(int $id, int $userId): ?array
    {
        return Db::first('SELECT * FROM task_lists WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function defaultList(int $userId): ?array
    {
        return Db::first('SELECT * FROM task_lists WHERE user_id = ? AND is_default = 1 LIMIT 1', [$userId]);
    }

    public function count(int $userId): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM task_lists WHERE user_id = ?', [$userId]);
    }

    public function create(int $userId, string $name, string $colorHex, bool $isDefault = false): int
    {
        $sort = (int) Db::value('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM task_lists WHERE user_id = ?', [$userId]);
        return Db::insert(
            'INSERT INTO task_lists (user_id, name, color_hex, is_default, sort_order) VALUES (?, ?, ?, ?, ?)',
            [$userId, $name, $colorHex, $isDefault ? 1 : 0, $sort]
        );
    }

    public function update(int $id, int $userId, string $name, string $colorHex): void
    {
        Db::exec('UPDATE task_lists SET name = ?, color_hex = ? WHERE id = ? AND user_id = ?', [$name, $colorHex, $id, $userId]);
    }

    public function delete(int $id, int $userId): void
    {
        Db::exec('DELETE FROM task_lists WHERE id = ? AND user_id = ? AND is_default = 0', [$id, $userId]);
    }
}
