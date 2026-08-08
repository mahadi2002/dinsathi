<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class TagRepo
{
    public function forUser(int $userId): array
    {
        return Db::all('SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC', [$userId]);
    }

    /** Find-or-create by name, returns tag id. */
    public function findOrCreate(int $userId, string $name): int
    {
        $existing = Db::value('SELECT id FROM tags WHERE user_id = ? AND name = ?', [$userId, $name]);
        if ($existing !== null) {
            return (int) $existing;
        }
        return Db::insert('INSERT INTO tags (user_id, name) VALUES (?, ?)', [$userId, $name]);
    }

    public function syncForTask(int $taskId, array $tagIds): void
    {
        Db::exec('DELETE FROM task_tags WHERE task_id = ?', [$taskId]);
        foreach (array_unique($tagIds) as $tagId) {
            Db::exec('INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES (?, ?)', [$taskId, (int) $tagId]);
        }
    }

    public function forTask(int $taskId): array
    {
        return Db::all(
            'SELECT t.* FROM tags t JOIN task_tags tt ON tt.tag_id = t.id WHERE tt.task_id = ? ORDER BY t.name',
            [$taskId]
        );
    }
}
